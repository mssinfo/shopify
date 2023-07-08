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
    <a htef="{{ reoute('msdev2.install') }}">Install</a> -- for link of ui
    https://[domain]/authenticate?shop=[shopify_domain] -- instent redirect

## Update shopify app callback url 
    [shop]/auth/callback

## Update ENV variables
    SHOPIFY_API_KEY=63f2fa001d************
    SHOPIFY_API_SECRET=47f72686a3************
    SHOPIFY_APP_ID=548698745823
    SHOPIFY_API_SCOPES=read_products,write_products
    SHOPIFY_WEBHOOKS=APP_UNINSTALLED,THEMES_PUBLISH,SHOP_UPDATE
    SHOPIFY_BILLING=true, #false
    SHOPIFY_FOOTER=<div>copyright @copy; all right reserved</div>
    SHOPIFY_IS_EMBEDDED_APP=true, #false //true if you want to open an app inside shopify else false
    SHOPIFY_APPBRIDGE_ENABLED=true #true
    SHOPIFY_TEST_STORES=mraganksoni,msdev203

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
    \Msdev2\Shopify\Utils::getShopName() or mShopName()
    \Msdev2\Shopify\Utils::getShop(?$shopname) or mShop(?$shopname)
    \Msdev2\Shopify\Utils::rest(?$shop) or mRest(?$shop)
    \Msdev2\Shopify\Utils::graph(?$shop) or mGraph(?$shop)
    \Msdev2\Shopify\Utils::makeUrltoLinkFromString($string) or mUrltoLinkFromString($string)
    \Msdev2\Shopify\Utils::successResponse(?$message,?$array,?$code) or mSuccessResponse(?$message,?$array,?$code)
    \Msdev2\Shopify\Utils::errorResponse(?$message,?$array,?$code) or mErrorResponse(?$message,?$array,?$code)


## save tabel to metafield
    class ModelName extend \Msdev2\Shopify\Models\Model
    {
        /** set true to save this data into metafield*/
        public $metaField = true;
        
    }
    

