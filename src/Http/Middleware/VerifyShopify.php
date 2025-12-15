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
                 if(config('msdev2.debug')) \Log::info("$shopName already uninstalled or does not exist", ['shop' => ($shop ?? null)]);
                return $this->redirectToInstall($shopName);
            }

            // Check for missing Shopify scopes
            $missingScopes = $this->compareShopifyScopes();
            if (!empty($missingScopes)) {
                // Normalize scopes for logging as well
                $requiredScopes = array_values(array_unique(array_filter(array_map('trim', explode(',', config('msdev2.scopes'))))));
                 if(config('msdev2.debug')) \Log::info("{$shop->shop} missing scopes: ", ['missing_scopes' => $missingScopes, 'shop' => $shop->shop, 'required_scopes' => $requiredScopes]);
                return $this->redirectToInstall($shopName, implode(', ', $missingScopes));
            }

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

    private function compareShopifyScopes(): array
    {
        // Normalize configured scopes: split, trim whitespace, remove empty values and duplicates
        $scopes = array_map('trim', explode(',', config('msdev2.scopes')));
        $scopes = array_filter($scopes); // remove any empty strings
        $scopes = array_unique($scopes);

        // Cache granted scopes per-shop to avoid calling Shopify on every request.
        $shopName = mShopName() ?: 'global';
        $cacheKey = 'shopify.access_scopes:' . $shopName;

        $grantedScopes = Cache::get($cacheKey);

        // If the same check happens twice within a short window (2 minutes),
        // invalidate the cached scopes so we fetch fresh values from Shopify.
        $timeKey = $cacheKey . ':last_checked';
        $lastChecked = Cache::get($timeKey);
        if ($lastChecked !== null && (time() - (int)$lastChecked) < 120) {
            if (config('msdev2.debug'))  \Log::info('compareShopifyScopes: recent check detected, invalidating scopes cache', ['shop' => $shopName, 'last_checked' => $lastChecked]);
            Cache::forget($cacheKey);
            $grantedScopes = null;
        }

        if ($grantedScopes === null) {
            // Fetch granted scopes from Shopify
            $data = mRest()->get('/admin/oauth/access_scopes.json');    
            $shopifyScopes = $data->getDecodedBody();
            if(config('msdev2.debug')) \Log::alert("check shopify database scops",['shopifyScopes'=>$shopifyScopes]);
            if (!isset($shopifyScopes['access_scopes'])) {
                // If we couldn't fetch scopes, treat as all missing (forces re-auth)
                return $scopes;
            }

            $grantedScopes = Arr::pluck($shopifyScopes['access_scopes'], 'handle');
            // Cache for 24 hours (86400 seconds)
            try {
                Cache::put($cacheKey, $grantedScopes, 86400);
                // Record when we last refreshed/checked scopes for this shop
                Cache::put($timeKey, time(), 86400);
            } catch (\Throwable $e) {
                // If cache fails for any reason, continue without breaking flow
                 if(config('msdev2.debug')) \Log::warning('Failed to cache shopify access scopes: ' . $e->getMessage(), ['shop' => $shopName]);
            }
        }

        // Ensure we update the last-checked timestamp even when returning cached scopes
        try {
            Cache::put($timeKey, time(), 86400);
        } catch (\Throwable $e) {
            // ignore cache write failures for timestamp
        }

        return array_diff($scopes, $grantedScopes ?? []);
    }

    private function redirectToInstall(string $shopName, $scopes = null)
    {
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
