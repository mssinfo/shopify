<?php
namespace Mraganksoni\Shopify;

use Illuminate\Support\ServiceProvider;

class ShopifyServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views/','mraganksoni');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');
        $this->mergeConfigFrom(__DIR__.'/config/mraganksoni_config.php','mraganksoni');
        $this->publishes([__DIR__.'/config/mraganksoni_config.php'=>config_path("mraganksoni_config.php")]);
    }
    public function register()
    {

    }
}
