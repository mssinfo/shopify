<?php
namespace Msdev2\Shopify;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Msdev2\Shopify\Models\Shop;
use Shopify\Context;
use Shopify\Utils as ShopifyUtils;

class Utils
{
    public static function getUrl($path) {
        $queryList = ShopifyUtils::getQueryParams(URL::full());
        if(isset($queryList["host"])) $query["host"] = $queryList['host'];
        else $query["host"] = base64_encode(config('app.url').'/admin');
        if(isset($queryList["shop"])) $query["shop"] = $queryList['shop'];
        if(isset($queryList["embedded"])) $query["embedded"] = $queryList['embedded'];
        if(isset($queryList["hmac"])) $query["hmac"] = $queryList['hmac'];
        if(isset($queryList["locale"])) $query["locale"] = $queryList['locale'];
        if(isset($queryList["session"])) $query["session"] = $queryList['session'];
        if(isset($queryList["timestamp"])) $query["timestamp"] = $queryList['timestamp'];
        if(Context::$IS_EMBEDDED_APP){
            if(request()->header('sec-fetch-dest')!='iframe'){
                return ShopifyUtils::getEmbeddedAppUrl($query['host']).$path;
            }
        }
        $query = http_build_query($query);
        return $path.'?'.$query;
    }
    public static function getShop() :Shop|null{
        $shopName = null;
        $query = ShopifyUtils::getQueryParams(URL::full());
        $shopName = $query['shop'] ?? null;
        if(!$shopName){
            $shopName = session('shop') ?? null;
        }
        if(!$shopName){
            $shopName = request()->header('shop') ?? null;
        }
        if($shopName){
            return Shop::where('shop',$shopName)->first();
        }
        return null;
    }
    /**
    * Converts URLs to hyperlinks. This is used to make links to a web page that is accessible through the user's browser
    *
    * @param $string
    *
    * @return string
    */
    function makeUrltoLink($string) {
        // The Regular Expression filter
        $reg_pattern = "/(((http|https|ftp|ftps)\:\/\/)|(www\.))[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\:[0-9]+)?(\/\S*)?/";
        // make the urls to hyperlinks
        return preg_replace($reg_pattern, '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', $string);
    }
    /**
    * Create a success response. This is a convenience function to create a success response with a JSON payload.
    *
    * @param $data
    * @param $message
    * @param $code
    *
    * @return JsonResponse
    */
    function successResponse($data=[], $message = "Operation Done Successful", $code = "200"){
        return response()->json([
            "status"=>"success",
            "message"=>$message,
            "data"=>$data,
            "code"=>$code
        ], $code);
    }

    /**
    * Create an error response. This is a convenience function to make it easier to use in tests. The data and code are passed as arguments to the response function
    *
    * @param $data
    * @param $message
    * @param $code
    *
    * @return JsonResponse
    */
    function errorResponse($data=[], $message = "Some Error", $code = "200"){
        return response()->json([
            "status"=>"error",
            "message"=>$message,
            "data"=>$data,
            "code"=>$code
        ], $code);
    }
}
