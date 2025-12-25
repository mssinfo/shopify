<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Utils;
use Shopify\Utils as ShopifyUtils;
use Illuminate\Support\Facades\URL;
use Msdev2\Shopify\Lib\DbSessionStorage;

class VerifyShopify
{
    public function handle(Request $request, Closure $next)
    {
        // Forget cache values
        Cache::forget('shop');
        Cache::forget('shopName');
        $shopName = mShopName();
        // Validate shop name early to avoid malformed hosts (e.g. numeric IDs)
        if ($shopName && !$this->isValidShopName($shopName)) {
            if (config('msdev2.debug')) \Log::warning('Invalid shop name detected', ['shop' => $shopName]);
            abort(400, 'Invalid shop parameter');
        }
        if (strpos($request->getRequestUri(),"a/".config("msdev2.proxy_path")) !== false && !isset($request->shop)) {
            $shopName = $_SERVER['HTTP_HOST'] != str_replace(["https://"],"",config("app.url")) ? $_SERVER['HTTP_HOST'] : null;
        }
        // Allow auth-related requests or if shop exists
        if ($this->isAllowedRequest($request) || $shopName) {
            $shop = mShop($shopName);
            if (!$shop || $shop->is_uninstalled) {
                 if(config('msdev2.debug')) \Log::info("$shopName already uninstalled or does not exist", ['shop' => ($shop ?? null), 'request'=>$request->all(), 'server'=>$_SERVER]);
                return $this->redirectToInstall($shopName);
            }

            // Scope validation deferred to client-side to avoid extra Shopify REST calls on every request.
            // Client will request missing scopes via the Shop UI using the shopify.scopes API.

            // Billing check
            if (config('msdev2.billing')) {
                $charges = $shop->charges()
                    ->where('status', 'active')
                    ->whereNull('cancelled_on')
                    ->first();

                if (!$charges && !$request->is('plan')) {
                    return redirect(mRoute('/plan'));
                }
            }
            // Handle embedded app redirection
            if (Utils::shouldRedirectToEmbeddedApp($request)) {
                return Utils::redirectToEmbeddedApp($request);
            }

            return $next($request);
        }
        // if not in iframe, then redirect to install page
        // dd($request->header('sec-fetch-dest'));
        if ($request->header('sec-fetch-dest') !== 'iframe' || !Utils::shouldRedirectToEmbeddedApp()) {
            return redirect()->route('msdev2.install');
        }
        abort(403, 'Shop does not exist in request');
    }

    /**
     * Basic validation for shop strings to avoid numeric or clearly malformed hosts.
     */
    private function isValidShopName(?string $shopName): bool
    {
        if (empty($shopName)) return false;
        // Reject pure numeric values which get interpreted as IPv4 numeric addresses (e.g. 1269 -> 0.0.4.245)
        if (preg_match('/^\d+$/', $shopName)) return false;
        // Must contain at least one dot and be a valid hostname
        if (strpos($shopName, '.') === false) return false;
        return (bool) filter_var($shopName, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME);
    }

    private function isAllowedRequest(Request $request): bool
    {
        return Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing', '/payu/success', '/payu/failed']);
    }

    private function redirectToInstall(?string $shopName, $scopes = null)
    {
        if (is_null($shopName)) {
            \Log::warning('redirectToInstall called with null shopName in VerifyShopify middleware', ['file' => __FILE__, 'request'=>request()->all(),'server'=>$_SERVER]);
            return redirect()->route('msdev2.shopify.install');
        }
        $routeParams = ['shop' => $shopName];
        if ($scopes) {
            $routeParams['scopes'] = $scopes;
        }
        DbSessionStorage::clearCurrentSession($shopName);
        return response()->view('msdev2::iframe_redirect', [
            'url' => route('msdev2.shopify.install', $routeParams)
        ]);
    }

}
