<?php

namespace Msdev2\Shopify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Utils as ShopifyUtils;

class LoadShopFromRequest
{
    /**
     * Handle an incoming request.
     * This middleware's job is to find a shop domain from the request
     * and make the corresponding Shop model available to the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // First, check if a more specific middleware (like EnsureShopifySession)
        // has already loaded and bound the shop. If so, our job is done.
        if (app()->bound('shopify.shop')) {
            return $next($request);
        }

        // Use your utility function to find the shop name from any source.
        $shopName = ShopifyUtils::getShopName();

        if ($shopName) {
            // If a shop name was found, retrieve the full shop model from the database.
            // We use a static cache to avoid re-querying on the same request if this runs multiple times.
            static $shop = null;
            if (is_null($shop)) {
                $shop = Shop::where('shop', $shopName)->first();
            }

            // If we found a valid shop model in our database...
            if ($shop) {
                // ...bind it to the service container as a singleton.
                // Now, any other part of the app (like your view composer)
                // can access it via app('shopify.shop').
                app()->singleton('shopify.shop', fn() => $shop);
            }
        }

        // Proceed with the request.
        return $next($request);
    }
}