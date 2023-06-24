<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Msdev2\Shopify\Models\Shop;

class VerifyShopify
{
    public function __construct() {
        
    }
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || isset($request->shop)) {
            $shop = Shop::where("shop")->first();
            if(!$shop){
                abort(403,'invalid shop domain');        
            }
            return $next($request);
        }
        abort(403,'shop not exist in request');
    }
}