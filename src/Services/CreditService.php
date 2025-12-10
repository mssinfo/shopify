<?php

namespace Msdev2\Shopify\Services;

use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Usage;
use Msdev2\Shopify\Services\CurrencyConverter;

class CreditService
{
    /**
     * Reset monthly free credits if month changed
     */
    public static function checkMonthlyReset(Shop $shop)
    {
        $currentMonth = date('Ym');
        $last = $shop->meta('last_reset_month');
        $defaultFree = $shop->plan()["feature"]['credit'] ?? 0;
        if ($last !== $currentMonth && $defaultFree > 0) {
            $shop->meta('free_credit_monthly_used', 0);
            $shop->meta('last_reset_month', $currentMonth);

            if ($shop->meta('free_credit_limit') === null) {
                $shop->meta('free_credit_limit', $defaultFree);
            }
        }
    }

    public static function freeLimit(Shop $shop): int
    {
        $defaultFree = $shop->plan()["feature"]['credit'] ?? 0;
        self::checkMonthlyReset($shop);
        return (int) ($shop->meta('free_credit_limit') ?? $defaultFree);
    }

    public static function freeUsed(Shop $shop): int
    {
        self::checkMonthlyReset($shop);
        return (int) ($shop->meta('free_credit_monthly_used') ?? 0);
    }

    /**
     * Get free credits remaining this month
     */
    public static function freeRemaining(Shop $shop): int
    {
        $limit = self::freeLimit($shop);
        $used  = self::freeUsed($shop);
        return max(0, $limit - $used);
    }

    /**
     * Purchased credits remaining (total purchases - total used)
     */
    public static function purchasedRemaining(Shop $shop): int
    {
        $purchased = Usage::where('shop_id', $shop->id)
            ->where('type', 'credit_purchase')
            ->sum('quantity');

        $used = Usage::where('shop_id', $shop->id)
            ->where('type', 'credit_used')
            ->sum('quantity'); // negative

        $used = abs($used);

        return max(0, (int)$purchased - (int)$used);
    }

    /**
     * Total used credits (lifetime)
     */
    public static function totalUsed(Shop $shop): int
    {
        $used = Usage::where('shop_id', $shop->id)
            ->where('type', 'credit_used')
            ->sum('quantity');

        return abs((int)$used);
    }

    /**
     * Combined credits
     */
    public static function totalRemaining(Shop $shop): int
    {
        return self::freeRemaining($shop) + self::purchasedRemaining($shop);
    }

    /**
     * Add purchased credits
     */
    public static function addPurchased(Shop $shop, int $qty, float $cost, array $meta = [])
    {
        // Determine if the payment was made in INR (shop configured currency or country)
        $shopCurrency = strtoupper($shop->detail['currency'] ?? '');
        $country = strtoupper($shop->detail['country_code'] ?? ($shop->detail['country'] ?? ''));

        $storeCost = $cost;

        if ($shopCurrency === 'INR' || $country === 'IN') {
            // Incoming $cost is likely in INR — convert back to USD for storage
            try {
                $usd = CurrencyConverter::inrToUsd((float)$cost);
                $storeCost = $usd;
                $meta['paid_currency'] = 'INR';
                $meta['paid_amount'] = $cost;
                $meta['paid_amount_usd'] = $usd;
            } catch (\Throwable $e) {
                // If conversion fails, still store INR amount but mark currency
                $meta['paid_currency'] = 'INR';
                $meta['paid_amount'] = $cost;
            }
        }

        $shop->usages()->create([
            'type' => 'credit_purchase',
            'quantity' => $qty,
            'cost' => $storeCost,
            'reference_id' => $meta['reference_id'] ?? $meta['txnid'] ?? null,
            'meta' => $meta,
        ]);
    }

    /**
     * Use ONE credit — returns TRUE if successful, FALSE if insufficient credits
     */
    public static function useOne(Shop $shop, array $meta = [], ?string $referenceId = null): bool
    {
        self::checkMonthlyReset($shop);

        // 1) Use free credits
        $freeLimit = self::freeLimit($shop);
        $freeUsed  = self::freeUsed($shop);

        if ($freeUsed < $freeLimit) {
            $shop->meta('free_credit_monthly_used', $freeUsed + 1);

            $meta['source'] = 'free';
            Usage::create([
                'shop_id' => $shop->id,
                'type' => 'credit_used',
                'quantity' => -1,
                'cost' => 0,
                'reference_id' => $referenceId,
                'meta' => $meta,
            ]);

            return true;
        }

        // 2) Purchased credits
        if (self::purchasedRemaining($shop) > 0) {
            $meta['source'] = 'paid';
            Usage::create([
                'shop_id' => $shop->id,
                'type' => 'credit_used',
                'quantity' => -1,
                'cost' => 0,
                'reference_id' => $referenceId,
                'meta' => $meta,
            ]);

            return true;
        }

        return false; // No credits left
    }

    /**
     * Simple struct for UI
     */
    public static function stats(Shop $shop): array
    {
        self::checkMonthlyReset($shop);

        $freeLimit      = self::freeLimit($shop);
        $freeUsed       = self::freeUsed($shop);
        $freeRemaining  = self::freeRemaining($shop);
        $purchRemaining = self::purchasedRemaining($shop);
        $totalRemaining = $freeRemaining + $purchRemaining;
        $totalUsed      = self::totalUsed($shop);

        // progress percent = used / (used + remaining)
        $den = $totalUsed + $totalRemaining;
        $percent = $den > 0 ? round(($totalUsed / $den) * 100) : 0;

        return [
            'free_limit'        => $freeLimit,
            'free_used'         => $freeUsed,
            'free_remaining'    => $freeRemaining,
            'purchased_remaining' => $purchRemaining,
            'total_remaining'   => $totalRemaining,
            'total_used'        => $totalUsed,
            'percent'           => $percent,
        ];
    }
}
