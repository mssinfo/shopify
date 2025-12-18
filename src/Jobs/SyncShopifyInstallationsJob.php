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

    /* ================= QUEUE SAFETY ================= */

    public $timeout = 300;
    public $tries   = 3;
    public $backoff = [60, 300];

    protected ?string $shopFilter;
    protected bool $check;

    /* ================= PLAN PRICE FALLBACK ================= */

    protected array $planPrices = [
        'STARTER'  => 1.99,
        'PRO'      => 4.99,
        'BUSINESS' => 9.99,
    ];

    /* ================= REQUIRED WEBHOOKS ================= */

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

    /* ================= ENTRY ================= */

    public function handle()
    {
        Shop::when($this->shopFilter, function ($q) {
                $q->where('shop', $this->shopFilter)
                  ->orWhere('id', $this->shopFilter)
                  ->orWhere('domain', $this->shopFilter);
            })
            ->chunkById(50, function ($shops) {
                foreach ($shops as $shop) {
                    try { $this->loadModuleFromHost($shop->shop); } catch (\Throwable $e) { Log::warning('SyncShopifyInstallationsJob: failed to load module config', ['error'=>$e->getMessage(), 'shop'=>$shop->shop]); }
                    $this->syncShopSafely($shop);
                }
            });
    }

    /* ================= SAFETY WRAPPER ================= */

    protected function syncShopSafely(Shop $shop): void
    {
        $changes = [];

        try {
            $this->syncShop($shop, $changes);
        } catch (\Throwable $e) {
            // NEVER break whole job
            Log::warning('Shop sync skipped (unexpected error)', [
                'shop' => $shop->shop,
                'error' => $e->getMessage(),
            ]);
            return;
        }

        // 🔕 Log ONLY if something changed
        if (!empty($changes)) {
            Log::info(
                $this->check ? 'SYNC CHECK → CHANGES' : 'SYNC UPDATED',
                ['shop' => $shop->shop, 'changes' => $changes]
            );
        }
    }

    /* ================= CORE LOGIC ================= */

    protected function syncShop(Shop $shop, array &$changes): void
    {
        try {
            $installed = $this->isInstalledOnShopify($shop);
        } catch (\Throwable $e) {

            if ($this->isInvalidTokenError($e)) {
                $this->markUninstalled($shop, 'invalid_access_token', $changes);
            }

            // cURL / network → skip
            return;
        }

        if (!$installed) {
            $this->markUninstalled($shop, 'app_not_installed', $changes);
            return;
        }

        // Webhooks only for valid installs
        $this->checkAndRegisterWebhooks($shop, $changes);

        // Paid or Free
        $charge = $this->fetchActiveRecurringCharge($shop);

        if ($charge) {
            $this->applyPaidPlan($shop, $charge, $changes);
        } else {
            $this->applyFreePlan($shop, $changes);
        }
    }

    /* ================= SHOPIFY ================= */

    protected function isInstalledOnShopify(Shop $shop): bool
    {
        $query = <<<'GQL'
        query {
          currentAppInstallation {
            id
          }
        }
        GQL;

        $resp = mGraph($shop)->query(['query' => $query])->getDecodedBody();
        return (bool) data_get($resp, 'data.currentAppInstallation');
    }

    protected function fetchActiveRecurringCharge(Shop $shop): ?array
    {
        try {
            $resp = mRest($shop)->get('recurring_application_charges')->getDecodedBody();
            foreach (data_get($resp, 'recurring_application_charges', []) as $charge) {
                if (in_array(strtolower($charge['status']), ['active', 'accepted'])) {
                    return $charge;
                }
            }
        } catch (\Throwable $e) {
            if ($this->isInvalidTokenError($e)) {
                throw $e;
            }
        }
        return null;
    }

    /* ================= WEBHOOK SYNC ================= */

    protected function checkAndRegisterWebhooks(Shop $shop, array &$changes): void
    {
        try {
            $resp = mRest($shop)->get('webhooks.json')->getDecodedBody();
            $existing = array_map(fn ($w) => strtolower($w['topic']), $resp['webhooks'] ?? []);

            foreach ($this->webhookList as $topic) {
                $shopifyTopic = strtolower(str_replace('_', '/', $topic));

                if (in_array($shopifyTopic, $existing)) {
                    continue;
                }

                if ($this->check) {
                    $changes[] = "would_subscribe_webhook:$topic";
                    continue;
                }

                $status = Registry::register(
                    route('msdev2.shopify.webhooks'),
                    \Shopify\Webhooks\Topics::{$topic},
                    $shop->shop,
                    $shop->access_token
                );

                $changes[] = $status->isSuccess()
                    ? "webhook_subscribed:$topic"
                    : "webhook_failed:$topic";
            }
        } catch (\Throwable $e) {
            // Never uninstall for webhook/network issues
        }
    }

    /* ================= PRICE ================= */

    protected function resolvePrice(array $charge): float
    {
        if (!empty($charge['price']) && (float)$charge['price'] > 0) {
            return (float) $charge['price'];
        }

        return $this->planPrices[strtoupper(trim($charge['name'] ?? ''))] ?? 0.0;
    }

    /* ================= APPLY STATES ================= */

    protected function applyPaidPlan(Shop $shop, array $charge, array &$changes): void
    {
        if ($this->check) {
            $changes[] = 'would_apply_paid_plan';
            return;
        }

        if ($shop->is_uninstalled) {
            $shop->update(['is_uninstalled' => 0, 'uninstalled_at' => null]);
            $changes[] = 'shop_restored';
        }

        $price = $this->resolvePrice($charge);

        $model = Charge::updateOrCreate(
            ['shop_id' => $shop->id, 'charge_id' => $charge['id']],
            [
                'name' => $charge['name'] ?? null,
                'status' => strtolower($charge['status']),
                'type' => 'recurring',
                'test' => (int)($charge['test'] ?? false),
                'price' => $price,
                'interval' => $charge['interval'] ?? 'EVERY_30_DAYS',
                'trial_days' => $charge['trial_days'] ?? null,
                'billing_on' => isset($charge['billing_on']) ? Carbon::parse($charge['billing_on']) : null,
                'trial_ends_on' => isset($charge['trial_ends_on']) ? Carbon::parse($charge['trial_ends_on']) : null,
                'activated_on' => Carbon::now(),
            ]
        );

        if ($model->wasRecentlyCreated || $model->wasChanged()) {
            $changes[] = 'charge_synced';
        }

        $meta = ['type' => 'paid', 'name' => $charge['name'], 'price' => $price];
        if ($shop->meta('plan') !== $meta) {
            $shop->meta('plan', $meta);
            $changes[] = 'plan_meta_updated';
        }
    }

    protected function applyFreePlan(Shop $shop, array &$changes): void
    {
        if ($this->check) {
            $changes[] = 'would_apply_free_plan';
            return;
        }

        if ($shop->is_uninstalled) {
            $shop->update(['is_uninstalled' => 0, 'uninstalled_at' => null]);
            $changes[] = 'shop_restored';
        }

        $model = Charge::updateOrCreate(
            ['shop_id' => $shop->id, 'charge_id' => 0],
            ['name' => 'Free', 'status' => 'active', 'type' => 'free', 'price' => 0]
        );

        if ($model->wasRecentlyCreated || $model->wasChanged()) {
            $changes[] = 'free_charge_synced';
        }

        if ($shop->meta('plan') !== ['type' => 'free']) {
            $shop->meta('plan', ['type' => 'free']);
            $changes[] = 'plan_meta_updated';
        }
    }

    protected function markUninstalled(Shop $shop, string $reason, array &$changes): void
    {
        if ($this->check) {
            $changes[] = "would_mark_uninstalled:$reason";
            return;
        }

        if (!$shop->is_uninstalled) {
            $shop->update(['is_uninstalled' => 1, 'uninstalled_at' => Carbon::now()]);
            $changes[] = 'shop_uninstalled';
        }

        $count = Charge::where('shop_id', $shop->id)
            ->whereIn('status', ['active', 'accepted'])
            ->update([
                'status' => 'cancelled',
                'cancelled_on' => Carbon::now(),
            ]);

        if ($count > 0) {
            $changes[] = 'charges_cancelled';
        }
    }

    /* ================= ERROR CLASSIFICATION ================= */

    protected function isInvalidTokenError(\Throwable $e): bool
    {
        $msg = strtolower($e->getMessage());

        return str_contains($msg, 'invalid api key')
            || str_contains($msg, 'invalid access token')
            || str_contains($msg, 'invalid api key or access token')
            || str_contains($msg, '401')
            || str_contains($msg, '403');
    }

    public function failed(\Throwable $e): void
    {
        Log::critical('SyncShopifyInstallationsJob FAILED permanently', [
            'error' => $e->getMessage(),
        ]);
    }
}
