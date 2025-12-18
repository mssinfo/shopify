<?php
namespace Msdev2\Shopify\Webhook;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Lib\BaseMailHandler;
use Msdev2\Shopify\Utils;
use Shopify\Webhooks\Handler;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AppUninstalled extends BaseMailHandler implements Handler
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    public function handle(string $topic, string $shopName, array $requestBody): void
    {
        $shop = mShop($shopName);

        if (!$shop) {
            Log::warning('AppUninstalled webhook: shop not found', ['shop' => $shopName, 'requestBody' => $requestBody, 'server' => $_SERVER]);
            return;
        }
        
        $shop->is_uninstalled = 1;
        $shop->uninstalled_at = Carbon::now();
        $shop->save();
        $charges = $shop->activeCharge;
        if($charges){
            $charges->status = 'cancelled';
            $charges->cancelled_on = Carbon::now();
            $charges->description = 'Cancel due to uninstall app';
            $charges->save();
            if (isset($charges->type) && strtolower($charges->type) === 'recurring' && !empty($charges->charge_id) && $charges->charge_id > 0) {
                try {
                    mRest($shop)->delete('recurring_application_charges/'.$charges->charge_id)->getDecodedBody();
                } catch (\Throwable $e) {
                    Log::warning('Failed to cancel recurring charge via REST (webhook): ' . $e->getMessage(), ['shop' => $shop->shop, 'charge_id' => $charges->charge_id]);
                }
            }
        }
        // $data = mRest($shop)->delete('api_permissions/current')->getDecodedBody();
        if(config('msdev2.debug')) Log::alert("webhook response shop unsiatall",[$shop]);
        self::processEmail('uninstall', $shop);
    }
}
