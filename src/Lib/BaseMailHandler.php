<?php
namespace Msdev2\Shopify\Lib;

use Msdev2\Shopify\Traits\SendsShopifyEmails;
use Msdev2\Shopify\Jobs\SendShopifyEmailJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

abstract class BaseMailHandler
{
    use SendsShopifyEmails;

    public static function processEmail($type, $shop)
    {
        // Ensure we have a Shop model instance
        $shopModel = $shop;
        if (!is_object($shopModel) || !method_exists($shopModel, 'meta')) {
            // if passed a shop string, try to resolve model
            try {
                $shopModel = \Msdev2\Shopify\Models\Shop::where('shop', $shop)->orWhere('domain', $shop)->first();
            } catch (\Throwable $e) {
                Log::warning('processEmail: unable to resolve shop model', ['shop' => $shop]);
                return;
            }
        }
        if (!$shopModel) return;

        $metaKey = 'email_sent:' . $type;
        // If already sent, skip dispatch
        if ($shopModel->meta($metaKey) == 1) {
            return;
        }

        // Use cache lock to avoid race where many requests dispatch many jobs
        $lockKey = 'email_dispatch_lock:' . $shopModel->id . ':' . $type;
        $lock = Cache::lock($lockKey, 10);
        if (!$lock->get()) {
            // Another process is dispatching — skip
            return;
        }

        try {
            // Re-check meta under lock
            if ($shopModel->meta($metaKey) == 1) {
                return;
            }
            // Dispatch async job to send email
            dispatch(new SendShopifyEmailJob($type, $shopModel->id));
        } catch (\Throwable $e) {
            Log::error('processEmail dispatch failed: ' . $e->getMessage(), ['shop' => $shopModel->id, 'type' => $type]);
        } finally {
            try { $lock->release(); } catch (\Throwable $e) { /* ignore */ }
        }
    }
}