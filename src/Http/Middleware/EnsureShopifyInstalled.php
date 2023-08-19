<?php

namespace Msdev2\Shopify\Http\Middleware;

use Msdev2\Shopify\Lib\AuthRedirection;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Models\Shop;

class EnsureShopifyInstalled
{
    /**
     * Checks if the shop in the query arguments is currently installed.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $shopName = mShopName();
        $appInstalled = $shopName && Shop::where('shop', $shopName)->where('access_token', '<>', null)->exists();
        // $isExitingIframe = preg_match("/^ExitIframe/i", $request->path());

        return ($appInstalled) ? $next($request) : AuthRedirection::redirect($request);
    }
}
