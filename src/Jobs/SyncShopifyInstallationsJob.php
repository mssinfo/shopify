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

class SyncShopifyInstallationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 300;
    protected ?string $shopName;
    protected bool $check;

    public function __construct(?string $shopName = null, bool $check = false)
    {
        $this->shopName = $shopName;
        $this->check = $check;
    }

    public function handle()
    {
        $processSingle = !empty($this->shopName);

        $processor = function($shop) {
            try{
                $query = <<<'GQL'
                    query { currentAppInstallation { id } }
                GQL;

                $response = mGraph($shop)->query(['query' => $query]);
                $decoded = method_exists($response, 'getDecodedBody') ? $response->getDecodedBody() : (is_array($response) ? $response : []);
                $installed = data_get($decoded, 'data.currentAppInstallation') ? true : false;

                if($this->check){
                    // Write a per-shop check log
                    $logPath = storage_path('logs/'.$shop->shop);
                    if(!is_dir($logPath)) mkdir($logPath, 0777, true);
                    $line = '['.Carbon::now()->toDateTimeString().'] installed: '.($installed ? 'true' : 'false')."\n";
                    file_put_contents($logPath.'/sync-check.log', $line, FILE_APPEND);
                    Log::info('Sync check', ['shop' => $shop->shop, 'installed' => $installed]);
                    return;
                }

                // If there's no explicit plan stored, ensure shop has at least a Free plan recorded in metadata
                try{
                    $currentPlan = $shop->plan();
                    if(empty($currentPlan)){
                        $shop->meta('plan', ['name' => 'Free', 'assigned_at' => Carbon::now()->toDateTimeString()]);
                        Log::info('Assigned Free plan to shop (no plan found)', ['shop' => $shop->shop]);
                    }
                }catch(\Throwable $e){
                    Log::warning('Failed to ensure default plan metadata', ['shop' => $shop->shop, 'error' => $e->getMessage()]);
                }

                if($installed){
                    if($shop->is_uninstalled){
                        // Shop was previously marked uninstalled but now reports installed — restore and fetch plan details
                        $shop->is_uninstalled = 0;
                        $shop->uninstalled_at = null;
                        $shop->save();
                        Log::info('Shop marked as reinstalled; fetching plan details', ['shop' => $shop->shop]);

                        // Attempt to fetch full recurring charge details from Shopify REST and store in metadata
                        try{
                            $resp = mRest($shop)->get('recurring_application_charges')->getDecodedBody();
                            $charges = data_get($resp, 'recurring_application_charges', $resp);
                            $active = null;
                            if(is_array($charges) || $charges instanceof \Traversable){
                                $active = collect($charges)->first(function($c){
                                    $status = strtolower(data_get($c, 'status', ''));
                                    return in_array($status, ['active','accepted']);
                                });
                            }

                            if($active){
                                // store full charge details into metadata 'plan'
                                $shop->meta('plan', $active);
                                Log::info('Updated shop plan metadata from Shopify recurring charge', ['shop' => $shop->shop, 'charge_id' => data_get($active,'id')]);
                            } else {
                                // No active recurring charge found; ensure Free plan
                                $shop->meta('plan', ['name' => 'Free', 'assigned_at' => Carbon::now()->toDateTimeString()]);
                                Log::info('No active recurring charge found; assigned Free plan', ['shop' => $shop->shop]);
                            }
                        }catch(\Throwable $e){
                            Log::warning('Failed to fetch recurring_application_charges from Shopify', ['shop' => $shop->shop, 'error' => $e->getMessage()]);
                        }
                    }
                } else {
                    if(!$shop->is_uninstalled){
                        $shop->is_uninstalled = 1;
                        $shop->uninstalled_at = Carbon::now();
                        $shop->save();

                        Log::info('Shop marked as uninstalled by sync job', ['shop' => $shop->shop]);

                        $charges = $shop->activeCharge;
                        if($charges){
                            $charges->status = 'canceled';
                            $charges->cancelled_on = Carbon::now();
                            $charges->description = 'Cancel due to uninstall (sync job)';
                            $charges->save();

                            if(isset($charges->type) && strtolower($charges->type) === 'recurring' && (!empty($charges->charge_id) && $charges->charge_id > 0)){
                                try{
                                    mRest($shop)->delete('recurring_application_charges/'.$charges->charge_id)->getDecodedBody();
                                }catch(\Throwable $e){
                                    Log::warning('Failed to cancel recurring charge via REST', ['shop' => $shop->shop, 'error' => $e->getMessage()]);
                                }
                            }
                        }

                        try{
                            \Msdev2\Shopify\Webhook\AppUninstalled::dispatch('APP_UNINSTALLED_SYNC', $shop->shop, []);
                        }catch(\Throwable $e){
                            Log::warning('Failed to dispatch AppUninstalled email', ['shop' => $shop->shop, 'error' => $e->getMessage()]);
                        }
                    }
                }
            }catch(\Throwable $e){
                Log::warning('SyncShopifyInstallationsJob: shop sync failed', ['shop' => $shop->shop ?? $this->shopName, 'error' => $e->getMessage()]);
            }
        };

        if($processSingle){
            $shop = Shop::where('shop', $this->shopName)->orWhere('id', $this->shopName)->orWhere('domain', $this->shopName)->first();
            if($shop) $processor($shop);
            return;
        }

        // Process shops in chunks to limit memory usage
        Shop::where(function($q){
            $q->where('is_uninstalled','!=',1)->orWhereNull('is_uninstalled');
        })->chunkById(100, function($shops) use ($processor){
            foreach($shops as $shop){
                $processor($shop);
            }
        });
    }
}
