<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Shopify\Utils;
use Shopify\Context;
use Illuminate\Support\Arr;

class VerifyShopify
{
    public function __construct() {

    }
    public function handle(Request $request, Closure $next)
    {
        Artisan::call('cache:forget shop');
        Artisan::call('cache:forget shopname');
        $shopName = mShopName();
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || $shopName) {
            $shop = mShop($shopName);
            if(!$shop || $shop->is_uninstalled == 1 || !empty($missingScopes)){
                return redirect()->route('msdev2.shopify.install',['shop'=>$shopName]);
            }
            $missingScopes = $this->compareShopifyScopes();
            if(config('msdev2.billing')){
                $charges = $shop->charges()->where('status','active')->whereNull('cancelled_on')->first();
                if(!$charges && !$request->is('plan')){
                    return redirect(mRoute('/plan'));
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
    public function compareShopifyScopes() {
        $scopes = explode(",",config('msdev2.scopes'));
        $data = mRest()->get('/admin/oauth/access_scopes.json');
        $shopifyScopes = $data->getDecodedBody();
        $scopes = array_unique($scopes);
        if(!isset($shopifyScopes['access_scopes'])){
            return $scopes;
        }
        $grantedScopes = Arr::pluck($shopifyScopes['access_scopes'], 'handle');
        return array_diff($scopes, $grantedScopes);
    }
}
