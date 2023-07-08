<?php
namespace Msdev2\Shopify;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Route;
use Msdev2\Shopify\Models\Shop;
use Shopify\Context;
use Shopify\Utils as ShopifyUtils;
use Shopify\Clients\Rest;
use Shopify\Clients\Graphql;
use Illuminate\Support\Facades\Cache;

class Utils
{
    public static function Route($path,$query = []) {
        $queryList = ShopifyUtils::getQueryParams(URL::full());
        if(isset($queryList["host"])) $query["host"] = $queryList['host'];
        else $query["host"] = base64_encode('https://'.Context::$HOST_NAME.'/admin');
        if(isset($queryList["shop"])) $query["shop"] = $queryList['shop'];
        else $query["shop"] = self::getShopName();
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
        if(Route::has($path)){
            return route($path,$query);
        }elseif (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path.'?'.$query;
        }
        $path = ltrim($path, '/');
        return 'https://'.Context::$HOST_NAME.'/'.$path.'?'.$query;
    }
    public static function getShopName(){
        $query = ShopifyUtils::getQueryParams(URL::full());
        $shopName = $query['shop'] ?? null;
        if(!$shopName && session('shopName')){
            $shopName = session('shopName');
        }
        elseif(!$shopName && request()->header('shop')){
            $shopName = request()->header('shop');
        }
        elseif(!$shopName &&  Cache::get('shopName')){
            $shopName = Cache::get('shopName');
        }
        elseif(!$shopName && request()->session){
            $shopName = Context::$SESSION_STORAGE->loadSession(request()->session)->getShop();
        }
        if(!$shopName){
            return null;
        }
        Cache::put('shopName',$shopName);
        return $shopName;
    }
    public static function getAccessToken(){
        $accessToken = null;
        if(Cache::get('accessToken')){
            $accessToken = Cache::get('accessToken');
        }
        elseif(!$accessToken && request()->session){
            $accessToken = Context::$SESSION_STORAGE->loadSession(request()->session)->getAccessToken();
        }
        if(!$accessToken){
            $shop = self::getShop();
            if($shop){
                $accessToken = $shop->access_token;
            }
        }
        if(!$accessToken){
            return null;
        }
        Cache::put('accessToken',$accessToken);
        return $accessToken;
    }
    public static function getShop($shopName = null) :Shop|null{
        $shop = null;
        if(!$shopName){
            $shopName = self::getShopName();
        }
        if($shopName){
            if(is_numeric($shopName)){
                $shop = Shop::find($shopName);
            }
            $shop = Shop::where('shop',$shopName)->first();
        }
        if($shop){
            Cache::put('accessToken',$shop->access_token);
            Cache::put('shopName',$shop->shop);
        }
        return $shop;
    }
    public static function rest(Shop $shop = null): Rest {
        if(!$shop){
            $shopName = self::getShopName();
            $accessToken = self::getAccessToken();
        }else{
            $shopName = $shop->shop;
            $accessToken = $shop->access_token;
        }
        $client = new Rest($shopName, $accessToken);
        //https://github.com/Shopify/shopify-api-php/blob/main/docs/usage/rest.md
        return $client;
    }
    public static function graph(Shop $shop = null): Graphql {
        if(!$shop){
            $shopName = self::getShopName();
            $accessToken = self::getAccessToken();
        }else{
            $shopName = $shop->shop;
            $accessToken = $shop->access_token;
        }
        $client = new Graphql($shopName, $accessToken);
        //https://github.com/Shopify/shopify-api-php/blob/main/docs/usage/graphql.md
        return $client;
    }
    /**
    * Converts URLs to hyperlinks. This is used to make links to a web page that is accessible through the user's browser
    *
    * @param $string
    *
    * @return string
    */
    public static function makeUrltoLinkFromString($string) {
        // The Regular Expression filter
        $reg_pattern = "/(((http|https|ftp|ftps)\:\/\/)|(www\.))[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\:[0-9]+)?(\/\S*)?/";
        // make the urls to hyperlinks
        return preg_replace($reg_pattern, '<a href="$0" target="_blank" rel="noopener noreferrer">$0</a>', $string);
    }
    public static function scriptTagShouldBeEnabled(Shop $shop, $published_theme, array $app_block_templates = [], array $params = []):bool
    {
        if (empty($app_block_templates)) {
            return false;
        }
        if (is_null($published_theme)) {
            return false;
        }
        $templateJSONFiles = [];
        $sectionsWithAppBlock = [];
        $main = false;
        $templateMainSections = [];
        // Setup the params
        $reqParams = array_merge(
            [
                'fields' => 'key',
            ],
            $params
        );
        // Fire the request
        $response =  mRest($shop)->get("/themes/{$published_theme}/assets", [], $reqParams);
        $assets = $response->getDecodedBody()['assets'];
        if (empty($assets)) {
            return false;
        }
        foreach ($assets as $asset) {
            foreach ($app_block_templates as $template) {
                if ($asset['key'] === "templates/{$template}.json") {
                    $templateJSONFiles[] = $asset['key'];
                }
            }
        }
        if (count($templateJSONFiles) != count($app_block_templates)) {
            return false;
        }
        foreach ($templateJSONFiles as $file) {
            $acceptsAppBlock = false;
            $reqParams = array_merge(
                [
                    'fields' => 'value',
                ],
                ['asset[key]' => $file]
            );

            // Fire the request
            $response = mRest($shop)->get("themes/{$published_theme}/assets", [], $reqParams);
            $asset = $response->getDecodedBody()['asset'];

            $json = json_decode($asset['value'], true);
            $query = 'main-';
            // Log::info(print_r($json, 1));

            if (array_key_exists('sections', (array)$json) && count($json['sections']) > 0) {
                foreach ($json['sections'] as $key => $value) {
                    if ($key === 'main' || substr($value['type'], 0, strlen($query)) === $query) {
                        $main = $value;
                        break;
                    }
                }
            }

            if ($main) {
                $mainType = $main['type'];
                if (count($assets) > 0) {
                    foreach ($assets as $asset) {
                        if ($asset['key'] === "sections/{$mainType}.liquid") {
                            $templateMainSections[] = $asset['key'];
                        }
                    }
                }
            }
        }

        if (count($templateMainSections) > 0) {
            $templateMainSections = array_unique($templateMainSections);
            foreach ($templateMainSections as $templateSection) {
                $acceptsAppBlock = false;
                $reqParams = array_merge(
                    [
                        'fields' => 'value',
                    ],
                    ['asset[key]' => $templateSection]
                );

                // Fire the request
                $response = mRest($shop)->get("/admin/themes/{$published_theme}/assets", [], $reqParams);
                $asset = $response->getDecodedBody()['asset'];

                $match = preg_match('/\{\%\s+schema\s+\%\}([\s\S]*?)\{\%\s+endschema\s+\%\}/m', $asset['value'], $matches);

                // Log::info(print_r($matches,1));
                $schema = json_decode($matches[1], true);
                // Log::info(print_r($schema,1));

                if ($schema && array_key_exists('blocks', $schema)) {
                    foreach ($schema['blocks'] as $block) {
                        if (array_key_exists('type', (array)$block) && $block['type'] === '@app') {
                            $acceptsAppBlock = true;
                        }
                    }
                    //   $acceptsAppBlock = .some((b => b.type === '@app'));
                }
                $acceptsAppBlock ? array_push($sectionsWithAppBlock, $templateSection) : null ;
            }
        }
        if (count($sectionsWithAppBlock)>0  && count($sectionsWithAppBlock) === count($templateJSONFiles)) {
            return false;
        }
        return true;
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
    public static function successResponse($data=[], $message = "Operation Done Successful", $code = "200"){
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
    public static function errorResponse($data=[], $message = "Some Error", $code = "200"){
        return response()->json([
            "status"=>"error",
            "message"=>$message,
            "data"=>$data,
            "code"=>$code
        ], $code);
    }
}