<?php
namespace Msdev2\Shopify\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Carbon;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Utils as ShopifyUtils;
use Msdev2\Shopify\Events\PlanPurchaseCompleted;
use Shopify\Context;
use Shopify\Webhooks\Registry;
use Shopify\Auth\Session;
use Msdev2\Shopify\Lib\DbSessionStorage;
use Msdev2\Shopify\Traits\LoadsModuleConfiguration;

class HandleShopInstalledJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    use LoadsModuleConfiguration;

    public $shopId;
    public ?string $shopName;
    public array $payload;
    public ?string $host;
    public int $attempt;

    public function __construct($shopId = null, ?string $shopName = null, array $payload = [], ?string $host = null, int $attempt = 0)
    {
        $this->shopId = $shopId;
        $this->shopName = $shopName;
        $this->payload = $payload;
        $this->host = $host;
        $this->attempt = $attempt;
    }

    public function handle()
    {
        // Ensure module-specific configuration is loaded when job runs in queue workers
        try {
            $this->loadModuleFromHost($this->host ?? ($this->payload['host'] ?? null));
        } catch (\Throwable $e) {
            Log::warning('HandleShopInstalledJob: failed to load module config', ['error' => $e->getMessage(), 'host' => $this->host]);
        }

        // Try to locate the shop by id first, then by shop domain/name
        $shop = null;
        try {
            if (!empty($this->shopId)) {
                $shop = Shop::find($this->shopId);
            }
            if (!$shop && !empty($this->shopName)) {
                $shop = Shop::where('shop', $this->shopName)->orWhere('domain', $this->shopName)->first();
            }
            if (!$shop && !empty($this->payload['shop'])) {
                $shop = Shop::where('shop', $this->payload['shop'])->orWhere('domain', $this->payload['shop'])->first();
            }
        } catch (\Throwable $e) {
            Log::warning('HandleShopInstalledJob: error while searching for shop', ['shop_id' => $this->shopId, 'shop_name' => $this->shopName, 'error' => $e->getMessage(), 'payload' => $this->payload, 'host' => $this->host]);
        }

        if (!$shop) {
            Log::warning('HandleShopInstalledJob: shop not found', ['shop_id' => $this->shopId, 'shop_name' => $this->shopName, 'payload' => $this->payload, 'attempt' => $this->attempt, 'host' => $this->host, 'database' => config('database.connections.' . config('database.default'))]);
            // Retry up to 3 attempts with a 5 minute delay
            if ($this->attempt < 3) {
                try {
                    $nextAttempt = $this->attempt + 1;
                    self::dispatch($this->shopId, $this->shopName, $this->payload, $this->host, $nextAttempt)->delay(Carbon::now()->addMinutes(5));
                    Log::info('HandleShopInstalledJob: re-dispatched job to retry shop lookup', ['shop_id' => $this->shopId, 'shop_name' => $this->shopName, 'next_attempt' => $nextAttempt, 'host' => $this->host]);
                } catch (\Throwable $e) {
                    Log::error('HandleShopInstalledJob: failed to dispatch retry', ['shop_id' => $this->shopId, 'error' => $e->getMessage(), 'host' => $this->host]);
                }
            } else {
                Log::warning('HandleShopInstalledJob: max retry reached, aborting', ['shop_id' => $this->shopId, 'shop_name' => $this->shopName, 'attempts' => $this->attempt, 'host' => $this->host]);
            }

            return;
        }

        try {
            if (config('msdev2.webhooks')) {
                try {
                    $webhooks = explode(',', config('msdev2.webhooks'));
                    foreach ($webhooks as $webhook) {
                        Registry::register('/shopify/webhooks', $webhook, $shop->shop, $shop->access_token);
                    }
                } catch (\Throwable $th) {
                    Log::error('Webhook registration failed (job)', ['shop' => $shop->shop, 'error' => $th->getMessage(), 'host' => $this->host]);
                }
            }

            Context::initialize(
                apiKey: config('msdev2.shopify_api_key'),
                apiSecretKey: config('msdev2.shopify_api_secret'),
                scopes: config('msdev2.scopes'),
                hostName: $this->host,
                sessionStorage: new DbSessionStorage(),
                apiVersion: config('msdev2.api_version'),
                isEmbeddedApp: config('msdev2.is_embedded_app'),
                isPrivateApp: config('msdev2.is_private_app', false),
            );

            $session = ShopifyUtils::getSession($shop->shop);
            if ($session) {
                $sessionStore = new Session($session, $shop->shop, true, \Ramsey\Uuid\Nonstandard\Uuid::uuid4()->toString());
                $sessionStore->setScope(Context::$SCOPES->toString());
                $sessionStore->setAccessToken($shop->access_token);
                $sessionStore->setExpires(strtotime('+1 day'));
                Context::$SESSION_STORAGE->storeSession($sessionStore);
            }

            // Fetch shop details via REST and persist
            try {
                $response = ShopifyUtils::rest($shop)->get('shop');
                $data = $response->getDecodedBody();
                if (isset($data['shop'])) {
                    $shop->detail = $data['shop'];
                    $shop->domain = $data['shop']['domain'] ?? $shop->shop;
                    $shop->save();
                } else {
                    Log::warning('HandleShopInstalledJob: shop details missing from REST response', ['shop' => $shop->shop, 'host' => $this->host]);
                }
            } catch (\Throwable $e) {
                Log::warning('HandleShopInstalledJob: failed fetching shop details', ['shop' => $shop->shop, 'error' => $e->getMessage(), 'host' => $this->host]);
            }

            // Attempt to call AppInstalled handlers (app modules, then framework)
            $classWebhook = "\\App\\Webhook\\Handlers\\AppInstalled";
            if (class_exists($classWebhook)) {
                try {
                    $handler = new $classWebhook();
                    if (method_exists($handler, 'handle')) {
                        $handler->handle('app/installed', $shop->shop ?? $shop, $this->payload);
                    }
                } catch (\Throwable $e) {
                    Log::warning('Failed to run AppInstalled handler (app): ' . $e->getMessage(), ['host' => $this->host]);
                }
            } else {
                $classWebhook = "\\Msdev2\\Shopify\\Webhook\\AppInstalled";
                if (class_exists($classWebhook)) {
                    try {
                        $handler = new $classWebhook();
                        if (method_exists($handler, 'handle')) {
                            $handler->handle('app/installed', $shop->shop ?? $shop, $this->payload);
                        }
                    } catch (\Throwable $e) {
                        Log::warning('Failed to run AppInstalled handler (framework): ' . $e->getMessage(), ['host' => $this->host]);
                    }
                }
            }

            // Billing: attempt to auto-assign free plan if configured; if not configured, create a default Free charge
            if (config('msdev2.billing') && !$shop->activeCharge) {
                $planList = config('msdev2.plan', []);
                $freePlan = null;
                foreach ($planList as $p) {
                    if (isset($p['amount']) && (float)$p['amount'] === 0.0) {
                        $freePlan = $p;
                        break;
                    }
                }

                if ($freePlan) {
                    $planType = 'free';
                    $billingOn = Carbon::now();
                    $trialDay = ($freePlan['trialDays'] ?? 0) > $shop->appUsedDay() ? ($freePlan['trialDays'] - $shop->appUsedDay()) : 0;

                    $shop->charges()->create([
                        'charge_id' => 0,
                        'name' => $freePlan['chargeName'],
                        'test' => !(app()->environment() === 'production'),
                        'status' => 'active',
                        'type' => $planType,
                        'price' => $freePlan['amount'],
                        'interval' => $freePlan['interval'] ?? 'ONE_TIME',
                        'capped_amount' => $freePlan['cappedAmount'] ?? 0,
                        'trial_days' => $trialDay,
                        'billing_on' => $billingOn,
                        'activated_on' => Carbon::now(),
                        'trial_ends_on' => Carbon::now()->addDays($trialDay),
                    ]);

                    if (class_exists(PlanPurchaseCompleted::class)) {
                        PlanPurchaseCompleted::dispatch($shop, null, $freePlan['chargeName']);
                    }
                } else {
                    // No free plan configured — create a default Free charge record so the shop has access
                    try {
                        $shop->charges()->create([
                            'charge_id' => 0,
                            'name' => 'Free',
                            'test' => !(app()->environment() === 'production'),
                            'status' => 'active',
                            'type' => 'free',
                            'price' => 0,
                            'interval' => 'ONE_TIME',
                            'capped_amount' => 0,
                            'trial_days' => 0,
                            'billing_on' => Carbon::now(),
                            'activated_on' => Carbon::now(),
                            'trial_ends_on' => Carbon::now(),
                        ]);
                        $shop->meta('plan', ['name' => 'Free', 'assigned_at' => Carbon::now()->toDateTimeString()]);
                    } catch (\Throwable $e) {
                        Log::warning('Failed to create default Free charge (job): ' . $e->getMessage(), ['shop' => $shop->shop, 'host' => $this->host]);
                    }
                }
            }

        } catch (\Throwable $e) {
            Log::error('HandleShopInstalledJob failed: ' . $e->getMessage(), ['shop' => $shop->shop, 'error' => $e, 'host' => $this->host]);
        }
    }
}
