<?php
namespace Msdev2\Shopify\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Lib\BaseMailHandler;

class SendShopifyEmailJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $type;
    public int $shopId;

    public function __construct(string $type, int $shopId)
    {
        $this->type = $type;
        $this->shopId = $shopId;
    }

    public function handle()
    {
        $shop = Shop::find($this->shopId);
        if (!$shop) {
            Log::warning('SendShopifyEmailJob: shop not found', ['shop_id' => $this->shopId, 'type' => $this->type]);
            return;
        }

        $metaKey = 'email_sent:' . $this->type;

        // Ensure idempotency: do not send if already marked sent
        if ($shop->meta($metaKey) == 1) {
            return;
        }

        // Acquire a short lock to avoid concurrent sends
        $lockKey = 'send_email_lock:' . $shop->id . ':' . $this->type;
        $lock = Cache::lock($lockKey, 30);
        if (!$lock->get()) {
            // another process is sending
            return;
        }

        try {
            // Double-check after acquiring lock
            if ($shop->meta($metaKey) == 1) {
                return;
            }

            BaseMailHandler::sendShopifyEmail($this->type, $shop);

            // mark sent and timestamp
            $shop->meta($metaKey, 1);
            $shop->meta($metaKey . ':at', now()->toDateTimeString());
        } catch (\Throwable $e) {
            Log::error('SendShopifyEmailJob failed: ' . $e->getMessage(), ['shop' => $shop->id, 'type' => $this->type]);
        } finally {
            try { $lock->release(); } catch (\Throwable $e) { /* ignore */ }
        }
    }
}
