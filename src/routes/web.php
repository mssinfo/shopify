<?php

// use Illuminate\Routing\Route;

Route::group(['namespace'=>'Msdev2\Shopify\Http\Controllers\ShopifyController'], function () {
    Route::post("install",'ShopifyController@install')->name("msdev2.install");
    Route::get("install",function(){
        return view('msdev2::install');
    });
    Route::get('auth/callback', 'ShopifyController@generateToken')->name("msdev2.callback");
});

