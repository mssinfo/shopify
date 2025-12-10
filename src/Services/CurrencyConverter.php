<?php

namespace Msdev2\Shopify\Services;

use Illuminate\Support\Facades\Cache;

class CurrencyConverter
{
    // Default fallback rate (USD -> INR)
    protected static function fallbackRate(): float
    {
        return 82.0;
    }

    // Cache key for USD->INR rate
    protected static function rateCacheKey(): string
    {
        return 'exchange_rate_usd_inr';
    }

    // TTL in seconds for exchange rate cache (configurable via msdev2.currency.ttl)
    protected static function cacheTtl(): int
    {
        // Default to 1 day
        return (int) (config('msdev2.currency.ttl') ?? 86400);
    }

    // Fetch rate from exchangerate.host (returns float rate or fallback)
    protected static function fetchUsdToInrRate(): float
    {
        try {
            $url = 'https://api.exchangerate.host/latest?base=USD&symbols=INR';
            $json = @file_get_contents($url);
            $data = $json ? json_decode($json, true) : null;
            if (!empty($data['rates']['INR'])) {
                return (float) $data['rates']['INR'];
            }
        } catch (\Throwable $e) {
            // ignore and fallback
        }
        return self::fallbackRate();
    }

    // Get cached USD->INR rate
    protected static function getUsdToInrRate(): float
    {
        return Cache::remember(self::rateCacheKey(), self::cacheTtl(), function () {
            return self::fetchUsdToInrRate();
        });
    }

    /**
     * Convert USD amount to INR using cached exchange rate with fallback.
     * Returns rounded amount with 2 decimals.
     */
    public static function usdToInr(float $amount): float
    {
        $rate = self::getUsdToInrRate();
        return round($amount * $rate, 2);
    }

    /**
     * Convert INR amount to USD using cached exchange rate with fallback.
     * Returns rounded amount with 2 decimals.
     */
    public static function inrToUsd(float $amount): float
    {
        $rate = self::getUsdToInrRate();
        if ($rate <= 0) {
            $rate = self::fallbackRate();
        }
        return round($amount / $rate, 2);
    }
}
