<?php

use Msdev2\Shopify\Utils;

function mRoute($path,$param = []){
    return Utils::Route($path,$param);
}
function mShopname(){
    return Utils::getShopName();
}
function mShop($shopName = null){
    return Utils::getShop($shopName);
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
