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
use Shopify\Utils;
class VerifyShopify
{
    public function __construct() {
        
    }
    public function handle(Request $request, Closure $next)
    {
        $host = $request->query('host');
        $shop = Utils::sanitizeShopDomain($request->query('shop'));
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || isset($shop)) {
            if(isset($shop)){
                $shop = Shop::where("shop",$shop)->first();
                if(!$shop){
                    abort(403,'Invalid shop domain');        
                }
            }
            return $next($request);
        }
        abort(403,'Shop not exist in request');
    }
}