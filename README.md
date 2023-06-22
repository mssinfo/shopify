# Shopify Helper 

    setup basic shopify app like install and all

### Add to config/app
    'providers' => [
        /*
         * Package Service Providers...
         */
        Msdev2\Shopify\ShopifyServiceProvider::class,
    ]

### To install shop

    [domain].install
    <a htef="{{ reoute('msdev2.install') }}">Install</a>

### Update shopify app callback url 

    [shop]/auth/callback

### Update ENV variables

    'SHOPIFY_API_KEY', '63f2fa001d************',
    'SHOPIFY_API_SECRET', '47f72686a3************',
    'SCOPES', 'read_products,write_products',
    'HOST', '[domain].com'