<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Msdev2\Shopify\Models\Shop;
use Shopify\Utils;
use Shopify\Auth\OAuth;

use Shopify\Auth\Session;

class VerifyShopify
{
    public function __construct() {
        
    }
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || $request->query('shop')) {
            $host = $request->query('host');
            $shop = Utils::sanitizeShopDomain($request->query('shop'));
            if(isset($shop)){
                $shop = Shop::where("shop",$shop)->first();
                if(!$shop){
                    abort(403,'Invalid shop domain');        
                }
                $sessionId = Str::random(16);
                new Session($sessionId, $shop, true, '3600');
            }
            return $next($request);
        }
        abort(403,'Shop not exist in request');
    }
}