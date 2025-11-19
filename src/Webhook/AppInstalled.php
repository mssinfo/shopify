<?php
namespace Msdev2\Shopify\Webhook;

use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Lib\BaseMailHandler;


class AppInstalled extends BaseMailHandler//implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;//, Queueable;
    protected $shop;
    public function __construct($shop) {
        $this->shop = $shop;
    }
    public function handle(): void
    {
        
        $data = $this->shop->option;
        if(!$data){
            $this->shop->option()->create([
                "whatsapp_number"=>$this->shop->detail['phone'] ?? '',
                "welcome_text"=>"Greetings and Salutations: Welcome to the group! We’re delighted to have you as part of our WhatsApp community",
                "icon_color"=>"#075E54",
                "background_color"=>"#FFFFFF",
                "position"=>"bottom-right"
            ]);
           
        }
        // self::processEmail('install', $this->shop);
    }
}
