<?php

namespace Msdev2\Shopify\Lib;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Shopify\Auth\OAuth;
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
            $redirectUrl = self::serverSideRedirectUrl($shop, $isOnline);
        }
        return redirect($redirectUrl);
    }

    private static function serverSideRedirectUrl(string $shop, bool $isOnline): string
    {
        return OAuth::begin(
            $shop,
            '/auth/callback',
            $isOnline,
            [CookieHandler::class, 'saveShopifyCookie'],
        );
    }

    private static function clientSideRedirectUrl($shop, array $query): string
    {
        $appHost = Context::$HOST_NAME;
        $redirectUri = urlencode("$appHost/api/auth?shop=$shop");

        $queryString = http_build_query(array_merge($query, ["redirectUri" => $redirectUri]));
        return "/ExitIframe?$queryString";
    }
}
