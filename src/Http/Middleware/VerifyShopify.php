<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Shopify\Utils;
use Shopify\Context;

class VerifyShopify
{
    public function __construct() {

    }
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || $request->query('shop')) {
            if($request->query('shop')){
                $shop = Utils::sanitizeShopDomain($request->query('shop'));
                if(!$shop){
                    abort(403,'Invalid shop domain');
                }
            }
            if(config('msdev2.billing')){
                $shop = mShop();
                if(!$shop || $shop->is_uninstalled == 1){
                    $shopName = mShopName();
                    if($shopName){
                        return redirect()->route('msdev2.shopify.install',['shop'=>$shopName]);
                    }
                    abort(403,'Shop not exist in request');
                }
                $charges = $shop->charges()->where('status','active')->whereNull('cancelled_on')->first();
                if(!$charges && $request->path()!='plan'){
                    return redirect(\Msdev2\Shopify\Utils::Route('msdev2.shopify.plan.index'));
                }
            }
            if(Context::$IS_EMBEDDED_APP && request()->header('sec-fetch-dest')!='iframe' && $request->server("REQUEST_METHOD")=='GET' && $request->input("host")){
                $url = Utils::getEmbeddedAppUrl($request->input("host"));
                return redirect($url.$request->server("SCRIPT_URL"));
            }
            return $next($request);
        }
        abort(403,'Shop not exist in request');
    }
}
