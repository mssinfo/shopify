<?php

// use Illuminate\Routing\Route;

use Mraganksoni\Shopify\Http\Controllers\ShopifyController;


Route::post("install",[ShopifyController::class,'install'])->name("mraganksoni.install");
Route::get("install",function(){
    return view('mraganksoni::install');
});
Route::get('api/auth/callback', [ShopifyController::class,'generateToken'])->name("mraganksoni.callback");
