<?php
namespace Msdev2\Shopify;
use Illuminate\Support\ServiceProvider;
use Msdev2\Shopify\Http\Middleware\VerifyShopify;

class ShopifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views/','msdev2');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        // $this->loadModelsFrom(__DIR__.'/Models');
        $this->mergeConfigFrom(__DIR__.'/config/msdev2_config.php','msdev2');
        $this->publishes([__DIR__.'/config/msdev2_config.php'=>config_path("msdev2_config.php")]);
        //    $this->loadTranslationsFrom(__DIR__.'/../lang', 'courier');
        $this->app['router']->aliasMiddleware('msdev2.verify.shopify', VerifyShopify::class);
    }
    public function register()
    {
        //code here to register
    }
}
