<?php

use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Utils;

function mRoute($path,$param = []){
    return Utils::Route($path,$param);
}
function mShopName(){
    return Utils::getShopName();
}
function mAccessToken(){
    return Utils::getAccessToken();
}
function mShop($shopName = null){
    return Utils::getShop($shopName);
}
function mRest($shop = null){
    $client = Utils::rest($shop);
    if (class_exists(\Msdev2\Shopify\Lib\LoggingHttpClientProxy::class)) {
        return new \Msdev2\Shopify\Lib\LoggingHttpClientProxy($client, 'rest');
    }
    return $client;
}
function mGraph($shop = null){
    $client = Utils::graph($shop);
    if (class_exists(\Msdev2\Shopify\Lib\LoggingHttpClientProxy::class)) {
        return new \Msdev2\Shopify\Lib\LoggingHttpClientProxy($client, 'graphql');
    }
    return $client;
}
function mUrltoLinkFromString($string){
    return Utils::makeUrltoLinkFromString($string);
}
function mSuccessResponse($data=[], $message = "Operation Done Successful", $code = "200"){
    return Utils::successResponse($data,$message,$code);
}
function mErrorResponse($data=[], $message = "Some Error", $code = "200"){
    return Utils::errorResponse($data,$message,$code);
}
function mLog($message, $ary = [], $logLevel = 'info', $channel = 'local') {
    if(!mShopName()){
        Log::$logLevel($message, $ary);
        return false;
    }
    $logPath = storage_path('logs/'.mShopName());
    if(!empty($ary))$message .= json_encode($ary);
    $logMessage = "[" . date('Y-m-d H:i:s') . "] $channel." . strtoupper($logLevel) . ": " . $message . PHP_EOL;
    if (!is_dir($logPath)) {
        mkdir($logPath, 0777, true);
    }
    $fileName = '/'.date("Y-m-d").'.log';
    file_put_contents($logPath.$fileName, $logMessage, FILE_APPEND);
}
