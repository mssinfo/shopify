<?php
namespace Msdev2\Shopify\Webhook;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Lib\BaseMailHandler;
use Shopify\Webhooks\Handler;

class AppInstalled  extends BaseMailHandler implements Handler
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    public function handle(string $topic, string $shopName, array $requestBody): void
    {
        // $data = $shop->option;
        // if(!$data){
        //     $shop->option()->create([
        //         "whatsapp_number"=>$shop->detail['phone'] ?? '',
        //         "welcome_text"=>"Greetings and Salutations: Welcome to the group! We’re delighted to have you as part of our WhatsApp community",
        //         "icon_color"=>"#075E54",
        //         "background_color"=>"#FFFFFF",
        //         "position"=>"bottom-right"
        //     ]);
           
        // }
        $shop = mShop($shopName);
        self::processEmail('install', $shop);
    }
}
