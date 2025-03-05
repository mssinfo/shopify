<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

use Illuminate\Support\Arr;
use Msdev2\Shopify\Utils;

class VerifyShopify
{
    public function handle(Request $request, Closure $next)
    {
        // Forget cache values
        Cache::forget('shop');
        Cache::forget('shopname');
        $shopName = mShopName();
        if (strpos($request->getRequestUri(),config("msdev2.proxy_path")) !== false && !isset($request->shop)) {
            $shopName = $_SERVER['HTTP_HOST'];
        }

        // Allow auth-related requests or if shop exists
        if ($this->isAllowedRequest($request) || $shopName) {
            $shop = mShop($shopName);

            if (!$shop || $shop->is_uninstalled) {
                return $this->redirectToInstall($shopName);
            }

            // Check for missing Shopify scopes
            $missingScopes = $this->compareShopifyScopes();
            if (!empty($missingScopes)) {
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

        abort(403, 'Shop does not exist in request');
    }

    private function isAllowedRequest(Request $request): bool
    {
        return Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']);
    }

    private function compareShopifyScopes(): array
    {
        $scopes = explode(',', config('msdev2.scopes'));
        $scopes = array_unique($scopes);

        // Fetch granted scopes from Shopify
        $data = mRest()->get('/admin/oauth/access_scopes.json');
        $shopifyScopes = $data->getDecodedBody();

        if (!isset($shopifyScopes['access_scopes'])) {
            return $scopes;
        }

        $grantedScopes = Arr::pluck($shopifyScopes['access_scopes'], 'handle');

        return array_diff($scopes, $grantedScopes);
    }

    private function redirectToInstall(string $shopName, $scopes = null)
    {
        $routeParams = ['shop' => $shopName];
        if ($scopes) {
            $routeParams['scopes'] = $scopes;
        }

        return response()->view('msdev2::iframe_redirect', [
            'url' => route('msdev2.shopify.install', $routeParams)
        ]);
    }

}
