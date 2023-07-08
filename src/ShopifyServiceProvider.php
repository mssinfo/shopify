<?php
namespace Msdev2\Shopify;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Msdev2\Shopify\Http\Middleware\EnsureShopifyInstalled;
use Msdev2\Shopify\Http\Middleware\VerifyShopify;
use Msdev2\Shopify\Http\Middleware\EnsureShopifySession;
use Msdev2\Shopify\Lib\DbSessionStorage;
use Shopify\ApiVersion;
use Shopify\Auth\Session;
use Shopify\Context;
use Ramsey\Uuid\Uuid;

class ShopifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views/','msdev2');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        // $this->loadModelsFrom(__DIR__.'/Models');
        $this->mergeConfigFrom(__DIR__.'/config/msdev2.php','msdev2');
        $this->publishes([__DIR__.'/config/msdev2.php'=>config_path("msdev2.php")]);
        //    $this->loadTranslationsFrom(__DIR__.'/../lang', 'courier');
        $this->app['router']->aliasMiddleware('msdev2.shopify.verify', VerifyShopify::class);
        $this->app['router']->aliasMiddleware('msdev2.shopify.auth', EnsureShopifySession::class);
        $this->app['router']->aliasMiddleware('msdev2.shopify.installed', EnsureShopifyInstalled::class);
        $host = str_replace('https://', '', config('app.url'));
        $customDomain = env('SHOP_CUSTOM_DOMAIN', null);
        Context::initialize(
            config('msdev2.shopify_api_key'),
            config('msdev2.shopify_api_secret'),
            config('msdev2.scopes'),
            $host,
            new DbSessionStorage(),
            ApiVersion::JANUARY_2023,
            config('msdev2.is_embedded_app'),
            false,
            null,
            '',
            null,
            (array)$customDomain,
        );
        $shoName = Utils::getShopName();
        $accessToken = Utils::getAccessToken();
        if($shoName && $accessToken){
            if(!session('shopName'))session(['shopName'=>$shoName]);
            $offlineSession = new Session(request()->session ?? 'offline_'.$shoName, $shoName, false, Uuid::uuid4()->toString());
            $offlineSession->setScope(Context::$SCOPES->toString());
            $offlineSession->setAccessToken($accessToken);
            $offlineSession->setExpires(strtotime('+1 day'));
            Context::$SESSION_STORAGE->storeSession($offlineSession);
        }
        URL::forceRootUrl("https://$host");
        URL::forceScheme('https');
    }
    public function register()
    {
        require_once __DIR__.'/Lib/Functions.php';
    }
}
