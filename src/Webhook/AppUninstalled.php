<?php
namespace Msdev2\Shopify\Webhook;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Lib\BaseMailHandler;
use Msdev2\Shopify\Utils;
use Shopify\Webhooks\Handler;

class AppUninstalled extends BaseMailHandler implements Handler
{
    public function handle(string $topic, string $shopName, array $requestBody): void
    {
        $shop = mShop($shopName);
        
        $shop->is_uninstalled = 1;
        $shop->uninstalled_at = Carbon::now();
        $shop->save();
        $charges = $shop->activeCharge;
        if($charges){
            $charges->status = 'canceled';
            $charges->cancelled_on = Carbon::now();
            $charges->description = 'Cancel due to uninstall app';
            $charges->save();
            if($charges->type == "recurring" && $charges->$charges->charge_id > 0){
                mRest($shop)->delete('recurring_application_charges/'.$charges->charge_id)->getDecodedBody();
            }
        }
        // $data = mRest($shop)->delete('api_permissions/current')->getDecodedBody();
        Log::alert("webhook response shop unsiatall",[$shop]);
        self::processEmail('uninstall', $shop);
    }
}
