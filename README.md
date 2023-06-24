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

    'SHOPIFY_API_KEY'='63f2fa001d************',
    'SHOPIFY_API_SECRET'='47f72686a3************',
    'SCOPES'='read_products,write_products',
    'SHOPIFY_WEBHOOKS'='APP_UNINSTALLED,THEMES_PUBLISH,SHOP_UPDATE',
    'SHOPIFY_BILLING'= true, #false

### middleware lists
    msdev2.shopify.verify  //to verify shop exist in url
    msdev2.shopify.auth //to authenticate shopify user
    msdev2.shopify.installed //to check if shopify is installed