<?php
namespace Msdev2\Shopify;
use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views/','msdev2');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->loadModelFrom(__DIR__.'/Models');
        $this->mergeConfigFrom(__DIR__.'/config/msdev2_config.php','msdev2');
        $this->publishes([__DIR__.'/config/msdev2_config.php'=>config_path("msdev2_config.php")]);
        //    $this->loadTranslationsFrom(__DIR__.'/../lang', 'courier');

    }
    public function register()
    {
        // $this->app->singleton(IShopCommand::class, function ($app) {
        //     return new ShopCommand($app->make(IShopQuery::class));
        // });
        // $this->app->register('Msdev2\Shopify\ShopifyServiceProvider');
    }
}
