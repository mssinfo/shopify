<?php

use Illuminate\Support\Facades\Route;
use Msdev2\Shopify\Http\Controllers\ShopifyController;
use Msdev2\Shopify\Http\Controllers\PlanController;

Route::fallback([ShopifyController::class , 'fallback'])->middleware('msdev2.shopify.installed');
Route::get("install",function(){
    return view('msdev2::install');
});
Route::post("shopify/webhooks/{name?}",[ShopifyController::class,'webhooksAction'])->name('msdev2.shopify.webhooks');
Route::get("authenticate",[ShopifyController::class,'install'])->name("msdev2.shopify.install");
Route::get('auth/callback', [ShopifyController::class,'generateToken'])->name("msdev2.shopify.callback");

Route::post('plan/subscribe',[PlanController::class,'planSubscribe'])->name('msdev2.shopify.plan.subscribe');
Route::get('plan/approve',[PlanController::class,'planAccept'])->name('msdev2.shopify.plan.approve');

Route::middleware(['msdev2.shopify.verify','web'])->group(function(){
    Route::get('plan',[PlanController::class,'plan'])->name("msdev2.shopify.plan.index");
});
