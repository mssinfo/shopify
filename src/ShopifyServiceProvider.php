<?php

namespace Msdev2\Shopify;

use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Msdev2\Shopify\Http\Middleware\Authenticate;
use Msdev2\Shopify\Http\Middleware\EnsureShopifyInstalled;
use Msdev2\Shopify\Http\Middleware\EnsureShopifySession;
use Msdev2\Shopify\Http\Middleware\VerifyShopify;
use Msdev2\Shopify\Lib\DbSessionStorage;
use Shopify\Context;

class ShopifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge config from the package
        $this->mergeConfigFrom(__DIR__.'/config/msdev2.php', 'msdev2');

        // Register package commands
        $this->commands([
            \Msdev2\Shopify\Console\Commands\SendCustomerEmails::class,
            \Msdev2\Shopify\Console\Commands\CreateAgent::class,
        ]);

        // Bind the session storage as a singleton. This ensures the same instance is used throughout the app.
        $this->app->singleton(DbSessionStorage::class, function ($app) {
            return new DbSessionStorage();
        });

        // Load helper functions
        require_once __DIR__.'/Lib/Functions.php';
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Force HTTPS scheme if configured and in a secure environment
        if (config('msdev2.force_https', true) && $this->isSecureConnection()) {
            URL::forceScheme('https');
        }

        // Register middleware aliases
        $this->registerMiddleware();

        // Load package resources
        $this->loadRoutesFrom(__DIR__.'/routes/web.php');
        $this->loadViewsFrom(__DIR__.'/views/', 'msdev2');
        $this->loadMigrationsFrom(__DIR__.'/database/migrations');

        // Define assets and config for publishing
        $this->definePublishing();

        // Register anonymous Blade components from the package so <x-admin-area> works
        try {
            Blade::component('msdev2::components.admin-area', 'admin-area');
        } catch (\Throwable $e) {
            // ignore if Blade not available at this point
        }

        // Check whether package public assets have been published (used to decide route registration)
        $published = file_exists(public_path('msdev2/js/shopify.js'));

        // If public assets are not published, register a route to serve them from the package folder.
        if (!$published) {
            try {
                Route::get('msdev2/{path}', function ($path) {
                    $file = __DIR__ . '/../public/' . $path;
                    if (!file_exists($file)) {
                        abort(404);
                    }
                    $mime = mime_content_type($file) ?: 'application/octet-stream';
                    return response()->file($file, ['Content-Type' => $mime]);
                })->where('path', '.*')->name('msdev2.assets');
            } catch (\Throwable $e) {
                // ignore route registration errors
            }
        }

        // Initialize the Shopify API Context
        $this->initializeShopifyContext();

        // Share the shop data with all views, but only if it's been bound by middleware
        view()->composer('*', function ($view) {
            $view->with('shop', mShop());
        });
    }

    /**
     * Check if the connection is secure.
     *
     * @return bool
     */
    private function isSecureConnection(): bool
    {
        return request()->secure()
            || request()->header('X-Forwarded-Proto') === 'https'
            || $this->app->environment('production');
    }

    /**
     * Register the middleware aliases.
     *
     * @return void
     */
    private function registerMiddleware(): void
    {
        $router = $this->app['router'];
        $router->aliasMiddleware('msdev2.shopify.verify', VerifyShopify::class);
        $router->aliasMiddleware('msdev2.shopify.auth', EnsureShopifySession::class);
        $router->aliasMiddleware('msdev2.shopify.installed', EnsureShopifyInstalled::class);
        $router->aliasMiddleware('msdev2.agent.auth', Authenticate::class);
        $router->aliasMiddleware('msdev2.validate.agent.token', \Msdev2\Shopify\Http\Middleware\ValidateAgentToken::class);
        $router->aliasMiddleware('msdev2.load.shop', \Msdev2\Shopify\Http\Middleware\LoadShopFromRequest::class);
        // Ensure our token validator runs early for web requests
        if (method_exists($router, 'pushMiddlewareToGroup')) {
            $router->pushMiddlewareToGroup('web', \Msdev2\Shopify\Http\Middleware\ValidateAgentToken::class);
        }
    }

    /**
     * Define the assets and configuration that can be published.
     *
     * @return void
     */
    private function definePublishing(): void
    {
        // Publish config file
        $this->publishes([
            __DIR__.'/config/msdev2.php' => config_path("msdev2.php")
        ], 'config');

        // Publish public assets
        $this->publishes([
            __DIR__.'/../public' => public_path('msdev2'),
        ], 'public');
    }

    /**
     * Initialize the global Shopify context.
     *
     * @return void
     */
    private function initializeShopifyContext(): void
    {
        Context::initialize(
            apiKey:           config('msdev2.shopify_api_key'),
            apiSecretKey:     config('msdev2.shopify_api_secret'),
            scopes:           config('msdev2.scopes'),
            hostName:         config('app.url'),
            sessionStorage:   $this->app->make(DbSessionStorage::class),
            apiVersion:       config('msdev2.api_version'),
            isEmbeddedApp:    config('msdev2.is_embedded_app', true),
            isPrivateApp:     config('msdev2.is_private_app', false),
            customShopDomains: (array) config('msdev2.shop_custom_domain', null)
        );
    }
}