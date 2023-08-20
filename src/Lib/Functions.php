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
function mShop($shopName = null, $cache = true){
    return Utils::getShop($shopName, $cache);
}
function mRest($shop = null){
    return Utils::rest($shop);
}
function mGraph($shop = null){
    return Utils::graph($shop);
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
