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

class AppSubscriptionsUpdate extends BaseMailHandler implements Handler
{
    use Dispatchable, InteractsWithQueue, SerializesModels;
    public function handle(string $topic, string $shopName, array $requestBody): void
    {
        Log::warning('AppSubscriptionsUpdate webhook: shop not found', ['shop' => $shopName, 'requestBody' => $requestBody, 'server' => $_SERVER]);
    }
}