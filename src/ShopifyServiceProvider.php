<?php
namespace Msdev2\Shopify;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Msdev2\Shopify\Http\Middleware\Authenticate;
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
        $this->publishes([
            __DIR__.'/resources/assets/js' => public_path('msdev2'),
            __DIR__.'/resources/assets/css' => public_path('msdev2'),
            __DIR__.'/resources/assets/images/' => public_path('msdev2').'/images',
            __DIR__.'/resources/assets/fonts/' => public_path('msdev2').'/fonts'
        ], 'public');
        //    $this->loadTranslationsFrom(__DIR__.'/../lang', 'courier');
        $this->app['router']->aliasMiddleware('msdev2.shopify.verify', VerifyShopify::class);
        $this->app['router']->aliasMiddleware('msdev2.shopify.auth', EnsureShopifySession::class);
        $this->app['router']->aliasMiddleware('msdev2.shopify.installed', EnsureShopifyInstalled::class);
        $this->app['router']->aliasMiddleware('msdev2.agent.auth', Authenticate::class);
        $host = config('app.url');
        $customDomain = env('SHOP_CUSTOM_DOMAIN', null);
        Context::initialize(
            config('msdev2.shopify_api_key'),
            config('msdev2.shopify_api_secret'),
            config('msdev2.scopes'),
            $host,
            new DbSessionStorage(),
            config('msdev2.api_version'),
            config('msdev2.is_embedded_app'),
            false,
            null,
            '',
            null,
            (array)$customDomain,
        );

        $shopName = Utils::getShopName();
        $accessToken = Utils::getAccessToken();
        $session = Utils::getSession($shopName);
        $shop = Utils::getShop();
        Utils::setShopData($shop);
        view()->composer('*', function ($view) use ($shop) {
            $view->with('shop', $shop);
        });
        if($shopName && $shopName!=null && $accessToken && $accessToken!=null && $session && $session!=null){
            $sessionStore = new Session($session, $shopName, true, Uuid::uuid4()->toString());
            $sessionStore->setScope(Context::$SCOPES->toString());
            $sessionStore->setAccessToken($accessToken);
            $sessionStore->setExpires(strtotime('+1 day'));
            Context::$SESSION_STORAGE->storeSession($sessionStore);
        }
    }
    public function register()
    {
        $this->commands([
            \Msdev2\Shopify\Console\Commands\SendCustomerEmails::class,
        ]);
        require_once __DIR__.'/Lib/Functions.php';
    }
}
