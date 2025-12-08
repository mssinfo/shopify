<?php

use Illuminate\Support\Facades\Route;
use Msdev2\Shopify\Http\Controllers\Admin\AuthController;
use Msdev2\Shopify\Http\Controllers\Admin\DashboardController;
use Msdev2\Shopify\Http\Controllers\Admin\EnvController;
use Msdev2\Shopify\Http\Controllers\Admin\LogController;
use Msdev2\Shopify\Http\Controllers\Admin\MarketingController;
use Msdev2\Shopify\Http\Controllers\Admin\ShopController;
use Msdev2\Shopify\Http\Controllers\Admin\TicketController as AdminTicketController;
use Msdev2\Shopify\Http\Controllers\ShopifyController;
use Msdev2\Shopify\Http\Controllers\PlanController;
use Msdev2\Shopify\Http\Controllers\UsageController;

Route::fallback([ShopifyController::class , 'fallback'])->middleware('msdev2.shopify.installed');
Route::get("install",function(){
    return view('msdev2::install');
})->name('msdev2.install');
Route::post("shopify/webhooks/{name?}",[ShopifyController::class,'webhooksAction'])->name('msdev2.shopify.webhooks');
Route::get("authenticate",[ShopifyController::class,'install'])->name("msdev2.shopify.install");
Route::get('auth/callback', [ShopifyController::class,'generateToken'])->name("msdev2.shopify.callback");

Route::post('plan/subscribe',[PlanController::class,'planSubscribe'])->name('msdev2.shopify.plan.subscribe');
Route::get('plan/approve',[PlanController::class,'planAccept'])->name('msdev2.shopify.plan.approve');

Route::post('/payu/success', [UsageController::class, 'payuSuccess'])->name('msdev2.payu.success');
Route::post('/payu/failed', [UsageController::class, 'payuFailed'])->name('msdev2.payu.failed');

Route::middleware(['msdev2.shopify.verify','msdev2.load.shop'])->group(function(){
    Route::get('plan',[PlanController::class,'plan'])->name("msdev2.shopify.plan.index");
    Route::get('help',[ShopifyController::class,'help'])->name("msdev2.shopify.help");
    Route::get('ticket',[ShopifyController::class,'ticket'])->name("msdev2.shopify.ticket");
    Route::post('ticket',[ShopifyController::class,'ticketStore'])->name("msdev2.shopify.saveticket");
    Route::get('usage',[UsageController::class,'index'])->name("msdev2.shopify.usage");
    Route::post('credits/buy', [UsageController::class, 'buyCredits'])->name('msdev2.credits.buy');
});
Route::group(['prefix' => 'admin', 'middleware' => ['web']], function () {
    // Auth
    Route::get('/', [AuthController::class, 'index'])->name('msdev2.admin.index');
    Route::get('/login', [AuthController::class, 'showLogin'])->name('msdev2.admin.login');
    Route::post('/login', [AuthController::class, 'login'])->name('msdev2.admin.login.submit');
    Route::post('/logout', [AuthController::class, 'logout'])->name('msdev2.admin.logout');

    // Protected Routes
    Route::group(['middleware' => 'msdev2.admin.auth'], function () {
        Route::post('/shopify-graph', [DashboardController::class, 'shopifyGraph'])->name("msdev2.admin.shopify.graph"); // Add your auth middleware
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
        Route::get('/api/shops/search', [ShopController::class, 'autocomplete'])->name('admin.api.shops.search');

        // Direct Login Route
        Route::get('/shops/{id}/login', [ShopController::class, 'login'])->name('admin.shops.login');
        // Shops & Specific Shop Logs
        Route::get('/shops', [ShopController::class, 'index'])->name('admin.shops');
        // Shop Detail & Actions
        Route::get('/shops/{id}', [ShopController::class, 'show'])->name('admin.shops.show');
        Route::post('/shops/{id}/metadata', [ShopController::class, 'storeMetadata'])->name('admin.shops.metadata.store');
        Route::delete('/shops/metadata/{id}', [ShopController::class, 'deleteMetadata'])->name('admin.shops.metadata.delete');
        Route::get('/shops/{id}/logs/content', [ShopController::class, 'getLogContent'])->name('admin.shops.logs.content');
        Route::get('/shops/{id}/logs/download', [ShopController::class, 'downloadLog'])->name('admin.shops.logs.download');
        Route::delete('/shops/{id}/logs/delete', [ShopController::class, 'deleteLog'])->name('admin.shops.logs.delete');
        // Tickets
        Route::get('/tickets', [AdminTicketController::class, 'index'])->name('admin.tickets');
        Route::get('/tickets/{id}', [AdminTicketController::class, 'show'])->name('admin.tickets.show');
        Route::post('/tickets/{id}/reply', [AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
        Route::post('/tickets/{id}/status', [AdminTicketController::class, 'updateStatus'])->name('admin.tickets.status');

        // System Logs
        Route::get('/logs', [LogController::class, 'index'])->name('admin.logs');
        Route::get('/log/download', [LogController::class, 'download'])->name('admin.logs.download');
        Route::get('/log/delete', [LogController::class, 'delete'])->name('admin.logs.delete');

         // 1. ENV Editor
        Route::get('/system/env', [EnvController::class, 'index'])->name('admin.env');
        Route::post('/system/env', [EnvController::class, 'update'])->name('admin.env.update');

        // 2. Bulk Marketing
        Route::get('/marketing/email', [MarketingController::class, 'index'])->name('admin.marketing');
        Route::post('/marketing/email', [MarketingController::class, 'send'])->name('admin.marketing.send');
        Route::post('/marketing/export', [MarketingController::class, 'export'])->name('admin.marketing.export');
        Route::post('/marketing/preview', [MarketingController::class, 'preview'])->name('admin.marketing.preview');
    });
});