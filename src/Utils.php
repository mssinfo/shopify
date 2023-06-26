<?php
namespace Msdev2\Shopify;
use Shopify\Context;
use Shopify\Utils as ShopifyUtils;

class Utils
{
    public static function getUrl($path){
        $query = ShopifyUtils::getQueryParams(url()->full());
        if(Context::$IS_EMBEDDED_APP){
            if(request()->header('sec-fetch-dest')!='iframe'){
                return ShopifyUtils::getEmbeddedAppUrl($query['host']).$path;
            }
        }
        $query = http_build_query($query);
        return $path.'?'.$query;
    }
}
