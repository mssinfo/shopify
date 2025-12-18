<?php

namespace Msdev2\Shopify\Traits;

use Dotenv\Dotenv;

trait LoadsModuleConfiguration
{
    /**
     * Load module-specific config file and merge with main config
     */
    protected function loadModuleConfig($modulePath)
    {
        $configPath = "{$modulePath}/config";
        
        if (!is_dir($configPath)) {
            return;
        }

        $files = glob("{$configPath}/*.php");

        foreach ($files as $file) {
            $filename = basename($file, '.php');
            $config = require $file;
            
            if (!is_array($config)) {
                continue;
            }

            // If it's msdev2.php, merge into msdev2 namespace (legacy support)
            if ($filename === 'msdev2') {
                foreach ($config as $key => $value) {
                    config(["msdev2.{$key}" => $value]);
                }
                continue;
            }

            // For other files, merge into their respective config keys
            $existingConfig = config($filename, []);
            config([$filename => array_replace_recursive($existingConfig, $config)]);
        }
    }

    /**
     * Override config values from environment variables
     */
    protected function overrideConfigFromEnv()
    {
        $getEnv = function($key) {
            return $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key) ?: null;
        };
        
        $standardMappings = [
            'DB_CONNECTION' => 'database.default',
            'DB_HOST' => 'database.connections.mysql.host',
            'DB_PORT' => 'database.connections.mysql.port',
            'DB_DATABASE' => 'database.connections.mysql.database',
            'DB_USERNAME' => 'database.connections.mysql.username',
            'DB_PASSWORD' => 'database.connections.mysql.password',
            'CACHE_STORE' => 'cache.default',
            'CACHE_PREFIX' => 'cache.prefix',
            'SESSION_DRIVER' => 'session.driver',
            'SESSION_LIFETIME' => 'session.lifetime',
            'QUEUE_CONNECTION' => 'queue.default',
            'REDIS_HOST' => 'database.redis.default.host',
            'REDIS_PASSWORD' => 'database.redis.default.password',
            'REDIS_PORT' => 'database.redis.default.port',
            'APP_NAME' => 'app.name',
            'APP_URL' => 'app.url',
            'APP_ENV' => 'app.env',
            'MAIL_MAILER' => 'mail.default',
            'MAIL_HOST' => 'mail.mailers.smtp.host',
            'MAIL_PORT' => 'mail.mailers.smtp.port',
            'MAIL_USERNAME' => 'mail.mailers.smtp.username',
            'MAIL_PASSWORD' => 'mail.mailers.smtp.password',
            'MAIL_FROM_ADDRESS' => 'mail.from.address',
            'MAIL_FROM_NAME' => 'mail.from.name',
        ];
        
        $msdev2ConfigPath = base_path('config/msdev2.php');
        $msdev2Mappings = [];
        
        if (file_exists($msdev2ConfigPath)) {
            $msdev2Config = require $msdev2ConfigPath;
            
            $configToEnvMap = [
                'shopify_api_key' => 'SHOPIFY_API_KEY',
                'shopify_api_secret' => 'SHOPIFY_API_SECRET',
                'scopes' => 'SHOPIFY_API_SCOPES',
                'app_id' => 'SHOPIFY_APP_ID',
                'api_version' => 'SHOPIFY_API_VERSION',
                'shopify_app_url' => 'SHOPIFY_APP_URL',
                'webhooks' => 'SHOPIFY_WEBHOOKS',
                'extension_name' => 'SHOPIFY_EXTENSION_NAME',
                'extension_folder_name' => 'SHOPIFY_EXTENSION_FOLDER_NAME',
                'extension_id' => 'SHOPIFY_EXTENSION_ID',
                'extension_app_name' => 'SHOPIFY_EXTENSION_APP_NAME',
                'enable_polaris' => 'SHOPIFY_ENABLE_POLARIS',
                'enable_turbolinks' => 'SHOPIFY_ENABLE_TURBOLINKS',
                'enable_alpinejs' => 'SHOPIFY_ENABLE_ALPINEJS',
                'is_embedded_app' => 'SHOPIFY_IS_EMBEDDED_APP',
                'proxy_path' => 'SHOPIFY_PROXY_PATH',
                'contact_url' => 'SHOPIFY_CONTACT_URL',
                'contact_email' => 'SHOPIFY_CONTACT_EMAIL',
                'debug' => 'MSDEV2_DEBUG',
                'billing' => 'SHOPIFY_BILLING',
                'tables' => 'SHOPIFY_DYNAMIN_CONFIG_TABLES',
                'force_https' => 'MSDEV2_FORCE_HTTPS',
                'tawk_url' => 'TAWK_URL',
                'tidio_url' => 'TIDIO_URL',
                'footer' => 'SHOPIFY_FOOTER',
                'test_stores' => 'SHOPIFY_TEST_STORES',
                'payment_provider' => 'MSDEV2_PAYMENT_PROVIDER',
                'payu' => 'PAYU_KEY',
                'stripe' => 'STRIPE_SECRET',
                'paypal' => 'PAYPAL_CLIENT_ID',
            ];
            
            foreach ($configToEnvMap as $configKey => $envKey) {
                if (array_key_exists($configKey, $msdev2Config)) {
                    $msdev2Mappings[$envKey] = "msdev2.{$configKey}";
                }
            }
        }
        
        $envToConfigMap = array_merge($standardMappings, $msdev2Mappings);

        $envToConfigMap['STRIPE_SECRET'] = 'msdev2.stripe.secret';
        $envToConfigMap['STRIPE_KEY'] = 'msdev2.stripe.publishable';
        $envToConfigMap['STRIPE_CURRENCY'] = 'msdev2.stripe.currency';
        $envToConfigMap['PAYU_KEY'] = 'msdev2.payu.key';
        $envToConfigMap['PAYU_SALT'] = 'msdev2.payu.salt';
        $envToConfigMap['PAYU_URL'] = 'msdev2.payu.url';
        $envToConfigMap['PAYPAL_CLIENT_ID'] = 'msdev2.paypal.client_id';
        $envToConfigMap['PAYPAL_SECRET'] = 'msdev2.paypal.secret';
        $envToConfigMap['PAYPAL_MODE'] = 'msdev2.paypal.mode';
        $envToConfigMap['PAYPAL_CURRENCY'] = 'msdev2.paypal.currency';
        
        foreach ($envToConfigMap as $envKey => $configKey) {
            $value = $getEnv($envKey);
            if ($value !== null) {
                if (in_array($envKey, ['APP_DEBUG', 'MSDEV2_DEBUG', 'SHOPIFY_BILLING', 'SHOPIFY_IS_EMBEDDED_APP', 'SHOPIFY_ENABLE_POLARIS', 'SHOPIFY_ENABLE_TURBOLINKS', 'SHOPIFY_ENABLE_ALPINEJS', 'MSDEV2_FORCE_HTTPS', 'SHOPIFY_APPBRIDGE_ENABLED'])) {
                    $value = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                }
                config([$configKey => $value]);
            }
        }
    }

    /**
     * Load module environment variables
     */
    protected function loadModuleEnv($modulePath)
    {
        $moduleEnvFile = "{$modulePath}/.env";
        if (file_exists($moduleEnvFile)) {
            $dotenv = Dotenv::createMutable($modulePath);
            $dotenv->safeLoad();
            $this->overrideConfigFromEnv();
        }
    }

    /**
     * Load module configuration by inspecting a host string.
     * Returns the detected module name or null when none found.
     */
    public function loadModuleFromHost(?string $host): ?string
    {
        if (empty($host)) {
            return null;
        }

        $candidate = $host;
        if (!str_contains($candidate, '.') && strpos($candidate, '/') !== false) {
            $parts = explode('/', $candidate);
            $candidate = $parts[0];
        }

        if (!str_contains($candidate, '.')) {
            $decoded = @base64_decode($candidate, true);
            if ($decoded !== false && str_contains($decoded, '.')) {
                $candidate = $decoded;
            }
        }

        if (str_contains($candidate, '/')) {
            $candidate = parse_url($candidate, PHP_URL_HOST) ?: explode('/', $candidate)[0];
        }

        $parts = explode('.', $candidate);
        if (empty($parts)) {
            return null;
        }

        $sub = $parts[0];
        $modulePath = base_path("modules/{$sub}");

        if (!is_dir($modulePath)) {
            return null;
        }

        config(['tenant.module' => $sub]);
        $this->loadModuleEnv($modulePath);
        $this->loadModuleConfig($modulePath);

        if (app()->has('db')) {
            app()['db']->purge();
        }

        return $sub;
    }
}
