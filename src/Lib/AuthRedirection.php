<?php

namespace Msdev2\Shopify\Lib;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Shopify\Context;
use Shopify\Utils;

class AuthRedirection
{
    public static function redirect(Request $request, bool $isOnline = false): ?RedirectResponse
    {
        if(!$request->query("shop")){
            return null;
        }
        $shop = Utils::sanitizeShopDomain($request->query("shop"));
        if (Context::$IS_EMBEDDED_APP && $request->query("embedded", false) === "1") {
            $redirectUrl = self::clientSideRedirectUrl($shop, $request->query());
        } else {
            Artisan::call('cache:forget shop');
            Artisan::call('cache:forget shopname');
            $shop = $request->shop;
            $api_key = config('msdev2.shopify_api_key');
            $scopes = config('msdev2.scopes');
            $redirect_uri = route("msdev2.shopify.callback");
            $shop = Utils::sanitizeShopDomain($shop);
            if(!$shop){
                return redirect()->back()->withErrors(['msg'=>'invalid domain']);
            }
            $redirectUrl = "https://" . $shop . "/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . urlencode($redirect_uri);
            Log::info("install app on ".$redirectUrl);
        }
        return redirect($redirectUrl);
    }


    private static function clientSideRedirectUrl($shop, array $query): string
    {
        $appHost = Context::$HOST_NAME;
        $redirectUri = urlencode("$appHost/api/auth?shop=$shop");

        $queryString = http_build_query(array_merge($query, ["redirectUri" => $redirectUri]));
        return "/ExitIframe?$queryString";
    }
}
