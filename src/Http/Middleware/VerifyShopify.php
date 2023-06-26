<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Msdev2\Shopify\Models\Shop;
use Shopify\Utils;
use Shopify\Context;

class VerifyShopify
{
    public function __construct() {

    }
    public function handle(Request $request, Closure $next)
    {

        // $shop = $request->session()->get('shop');
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || $request->query('shop')) {
            if($request->query('shop')){
                $shop = Utils::sanitizeShopDomain($request->query('shop'));
                $shop = Shop::where("shop",$shop)->first();
                if(!$shop){
                    abort(403,'Invalid shop domain');
                }
                session()->push('shop', $shop);
            }
            if(Context::$IS_EMBEDDED_APP && request()->header('sec-fetch-dest')!='iframe' && $request->server("REQUEST_METHOD")=='GET'){
                $url = Utils::getEmbeddedAppUrl($request->input("host"));
                // return AuthRedirection::redirect($request);
                return redirect($url.$request->server("SCRIPT_URL"));
            }
            return $next($request);
        }
        abort(403,'Shop not exist in request');
    }
}
