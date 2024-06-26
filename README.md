# Shopify Helper 
setup basic shopify app like install and all

`composer require msdev2/shopify`

`php artisan vendor:publish --provider="Msdev2\Shopify\ShopifyServiceProvider"`

- update menu items
- update plan
- update billing

## Add to config/app
    'providers' => [
        /*
         * Package Service Providers...
         */
        Msdev2\Shopify\ShopifyServiceProvider::class,
    ]

## To install shop
    [domain]/install -- for ui
    <a htef="{{ route('msdev2.install') }}">Install</a> -- for link of ui
    https://[domain]/authenticate?shop=[shopify_domain] -- instent redirect

## Update shopify app callback url 
    [shop]/auth/callback

## Update ENV variables
    SHOPIFY_API_KEY=63f2fa001d************
    SHOPIFY_API_SECRET=47f72686a3************
    SHOPIFY_APP_ID=548698745823
    SHOPIFY_API_SCOPES=read_products,write_products
    SHOPIFY_BILLING=true #false
    SHOPIFY_FOOTER=<div>copyright @copy; all right reserved</div>
    SHOPIFY_IS_EMBEDDED_APP=true #false //true if you want to open an app inside shopify else false
    SHOPIFY_APPBRIDGE_ENABLED=true #true
    SHOPIFY_ENABLE_ALPINEJS=true #true if you want to use vue make it false
    SHOPIFY_ENABLE_TURBOLINKS=true #true if you want to use vue make it false
    SHOPIFY_TEST_STORES=mraganksoni,msdev203
    TAWK_URL=https://https://embed.tawk.to/64cbaca1cc26a871b02d0bcf/1h6tpkmh2
    SHOPIFY_API_VERSION=2023-04

## Setup hooks
    update env
    SHOPIFY_WEBHOOKS=APP_UNINSTALLED,THEMES_PUBLISH,SHOP_UPDATE
    create hook receiver file
    app/Webhook/Handlers/AppUninstalled.php
    app/Webhook/Handlers/ShopUpdate.php
    app/Webhook/Handlers/ThemesPublish.php
    
## middleware lists
    msdev2.shopify.verify  //to verify shop exist in url
    msdev2.shopify.auth //to authenticate shopify user
    msdev2.shopify.installed //to check if shopify is installed

## setup layout for blade
css reference is https://www.uptowncss.com/

    @extends('msdev2::layout.master')
    @section('content')
    <div class="mt-20">
        //your code here
    </div>
    @endsection
    @section('scripts')
        @parent
        <script type="text/javascript">
            actions.TitleBar.create(app, { title: 'Welcome' });
        </script>
    @endsection

## helper functions
### route helper functions
    \Msdev2\Shopify\Utils::Route('home',['app'=>'home']) or mRoute('home',['app'=>'home])
    \Msdev2\Shopify\Utils::Route('home') or mRoute('home')
    \Msdev2\Shopify\Utils::Route('home.name') or mRoute('home.name')
    \Msdev2\Shopify\Utils::Route('/pagename') or mRoute('/pagename')
### other common helper functions
    \Msdev2\Shopify\Utils::$shop;
    \Msdev2\Shopify\Utils::getShop(?$shopname) or mShop(?$shopname)
    \Msdev2\Shopify\Utils::getShopName() or mShopName()
    \Msdev2\Shopify\Utils::rest(?$shop) or mRest(?$shop)
    \Msdev2\Shopify\Utils::graph(?$shop) or mGraph(?$shop)
    \Msdev2\Shopify\Utils::makeUrltoLinkFromString($string) or mUrltoLinkFromString($string)
    \Msdev2\Shopify\Utils::successResponse(?$message,?$array,?$code) or mSuccessResponse(?$message,?$array,?$code)
    \Msdev2\Shopify\Utils::errorResponse(?$message,?$array,?$code) or mErrorResponse(?$message,?$array,?$code)
    mLog($message, ?$array, ?$logLevel, ?$channel)

### for vuejs route helper  
    window.$GLOBALS.push(path)
    window.$GLOBALS.push(path, params)
    window.$GLOBALS.push(name)
    window.$GLOBALS.push(name, params)
    window.$GLOBALS.shop
    window.$GLOBALS.host
    window.$GLOBALS.csrfToken
    window.$GLOBALS.processRequest(url, data, isImageRequest) # url = 'POST /url'  data = {} isImageRequest=false
    window.$GLOBALS.showToast(msg,isError,subscribeFun,clearFun) # msg = string isError=false subscribeFun=callback function clearFun=callback function
    showToast(msg,isError,subscribeFun,clearFun) # msg = string isError=false subscribeFun=callback function clearFun=callback function

## save tabel to metafield
    class ModelName extend \Msdev2\Shopify\Models\Model
    {
        /** set true to save this data into metafield*/
        public $metaField = true;
        
    }
    
## use shopify logs
    $shop->log('message',[],'type','channel') 
    $shop->log('log added successfully',['test'=>'info'],'alert','shopify')

## load without ssl
    \vendor\shopify\shopify-api\src\Clients\HttpClientFactory.php    
    return new Client(['verify' => false]);