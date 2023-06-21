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
        $this->mergeConfigFrom(__DIR__.'/config/msdev2_config.php','msdev2');
        $this->publishes([__DIR__.'/config/msdev2_config.php'=>config_path("msdev2_config.php")]);
    }
    public function register()
    {
        #add code here
    }
}
