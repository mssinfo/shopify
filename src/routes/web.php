<?php

use Illuminate\Support\Facades\Route;
use Msdev2\Shopify\Http\Controllers\ShopifyController;

Route::fallback([ShopifyController::class , 'fallback'])->middleware('msdev2.shopify.installed');
Route::get("install",function(){
    return view('msdev2::install');
});
Route::get("authenticate",[ShopifyController::class,'install'])->name("msdev2.shopify.install");
Route::middleware(['msdev2.shopify.verify','web','msdev2.shopify.auth'])->group(function(){
    Route::get('auth/callback', [ShopifyController::class,'generateToken'])->name("msdev2.shopify.callback");
    Route::get('plan',function(){
        return view('msdev2::plan');
    })->name("msdev2.shopify.plan");
    Route::post('plan',[ShopifyController::class,'planSubscribe'])->name('msdev2.shopify.plan.subscribe');
});

