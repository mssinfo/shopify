<?php

namespace Msdev2\Shopify\Jobs;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Charge;
use Shopify\Webhooks\Registry;
use Msdev2\Shopify\Traits\LoadsModuleConfiguration;

class SyncShopifyInstallationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use LoadsModuleConfiguration;

    public $timeout = 300;
    public $tries   = 3;
    public $backoff = [60, 300];

    protected ?string $shopFilter;
    protected bool $check;

    protected array $webhookList = [
        'APP_UNINSTALLED',
        'SHOP_UPDATE',
        'ORDERS_PAID',
    ];

    public function __construct(?string $shop = null, bool $check = false)
    {
        $this->shopFilter = $shop;
        $this->check = $check;
    }

    public function handle()
    {
        // We iterate through DB shops. 
        // Note: If a shop is in Shopify but NOT in this DB, it won't be synced 
        // unless you manually add the 'shop' domain to the table first.
        Shop::when($this->shopFilter, function ($q, $shopFilter) {
            $q->where(function ($q) use ($shopFilter) {
                $q->where('shop', $shopFilter)->orWhere('id', $shopFilter);
            });
        })
        ->chunkById(50, function ($shops) {
            foreach ($shops as $shop) {
                $this->syncShopSafely($shop);
            }
        });
    }

    protected function syncShopSafely(Shop $shop): void
    {
        try {
            $this->loadModuleFromHost($shop->shop);
            $changes = [];
            
            $this->syncShop($shop, $changes);

            // ONLY log if there are actual changes
            if (!empty($changes)) {
                Log::info("SHOP_SYNC_UPDATED: {$shop->shop}", [
                    'shop' => $shop->shop,
                    'changes' => $changes
                ]);
            }
        } catch (\Throwable $e) {
            if ($this->isInvalidTokenError($e)) {
                $this->markUninstalled($shop, 'invalid_token', $changes);
            } else {
                Log::error("Sync Error [{$shop->shop}]: " . $e->getMessage());
            }
        }
    }

    protected function syncShop(Shop $shop, array &$changes): void
    {
        $data = $this->fetchShopifyData($shop);
        $installation = data_get($data, 'currentAppInstallation');

        // 1. Handle Uninstalls
        if (!$installation) {
            $this->markUninstalled($shop, 'app_not_found', $changes);
            return;
        }

        // 2. Sync Webhooks (Logs ONLY if mismatch found)
        $this->checkAndRegisterWebhooks($shop, $changes);

        // 3. Sync Billing
        $activeSub = collect(data_get($installation, 'activeSubscriptions', []))
            ->where('status', 'ACTIVE')
            ->first();

        if ($activeSub) {
            $this->applyPaidPlan($shop, $activeSub, $changes);
        } else {
            $this->applyFreePlan($shop, $changes);
        }
    }

    protected function fetchShopifyData(Shop $shop): array
    {
        $query = <<<'GQL'
        query GetSyncData {
          currentAppInstallation {
            activeSubscriptions {
              id name status test trialDays createdAt currentPeriodEnd
              lineItems {
                plan {
                  pricingDetails {
                    __typename
                    ... on AppRecurringPricing { price { amount } interval }
                  }
                }
              }
            }
          }
        }
        GQL;

        $resp = mGraph($shop)->query(['query' => $query])->getDecodedBody();
        return $resp['data'] ?? [];
    }

    protected function checkAndRegisterWebhooks(Shop $shop, array &$changes): void
    {
        try {
            $resp = mRest($shop)->get('webhooks.json')->getDecodedBody();
            $existing = array_map(fn ($w) => strtolower($w['topic']), $resp['webhooks'] ?? []);

            foreach ($this->webhookList as $topic) {
                $shopifyTopic = strtolower(str_replace('_', '/', $topic));

                if (!in_array($shopifyTopic, $existing)) {
                    // Mismatch found! Log and fix.
                    $status = Registry::register(
                        route('msdev2.shopify.webhooks'),
                        \Shopify\Webhooks\Topics::{$topic},
                        $shop->shop,
                        $shop->access_token
                    );
                    
                    $changes[] = "webhook_fixed:$topic";
                }
            }
        } catch (\Throwable $e) {
            $changes[] = "webhook_error:" . $e->getMessage();
        }
    }

    protected function applyPaidPlan(Shop $shop, array $sub, array &$changes): void
    {
        // Restoration Check
        if ($shop->is_uninstalled) {
            $shop->update(['is_uninstalled' => 0, 'uninstalled_at' => null]);
            $changes[] = 'status:restored';
        }

        $price = (float) data_get($sub, 'lineItems.0.plan.pricingDetails.price.amount', 0);
        $cleanId = (int) last(explode('/', $sub['id']));

        $charge = Charge::updateOrCreate(
            ['shop_id' => $shop->id, 'charge_id' => $cleanId],
            [
                'name' => $sub['name'],
                'status' => 'active',
                'type' => 'recurring',
                'price' => $price,
                'activated_on' => Carbon::parse($sub['createdAt']),
            ]
        );

        if ($charge->wasRecentlyCreated || $charge->wasChanged()) {
            $changes[] = "plan_updated:{$sub['name']}";
        }
    }

    protected function applyFreePlan(Shop $shop, array &$changes): void
    {
        if ($shop->is_uninstalled) {
            $shop->update(['is_uninstalled' => 0, 'uninstalled_at' => null]);
            $changes[] = 'status:restored';
        }

        // Logic to ensure no active charges exist in DB if Shopify says they are free
        $updated = Charge::where('shop_id', $shop->id)
            ->where('status', 'active')
            ->where('charge_id', '!=', 0)
            ->update(['status' => 'cancelled', 'cancelled_on' => Carbon::now()]);

        if ($updated) $changes[] = 'plan_downgraded_to_free';
    }

    protected function markUninstalled(Shop $shop, string $reason, array &$changes): void
    {
        if (!$shop->is_uninstalled) {
            $shop->update(['is_uninstalled' => 1, 'uninstalled_at' => Carbon::now()]);
            $changes[] = "status:uninstalled_due_to_$reason";
            
            Charge::where('shop_id', $shop->id)->update(['status' => 'cancelled']);
        }
    }

    protected function isInvalidTokenError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());
        return str_contains($msg, '401') || str_contains($msg, '403') || str_contains($msg, 'access token');
    }
}