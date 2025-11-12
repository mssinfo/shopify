<?php

use Illuminate\Support\Facades\Route;
use Msdev2\Shopify\Http\Controllers\ShopifyController;
use Msdev2\Shopify\Http\Controllers\PlanController;
use Msdev2\Shopify\Http\Controllers\LogsController;
use Msdev2\Shopify\Http\Controllers\TicketController;
use Msdev2\Shopify\Http\Controllers\AgentController;

Route::fallback([ShopifyController::class , 'fallback'])->middleware('msdev2.shopify.installed');
Route::get("install",function(){
    return view('msdev2::install');
})->name('msdev2.install');
Route::post("shopify/webhooks/{name?}",[ShopifyController::class,'webhooksAction'])->name('msdev2.shopify.webhooks');
Route::get("authenticate",[ShopifyController::class,'install'])->name("msdev2.shopify.install");
Route::get('auth/callback', [ShopifyController::class,'generateToken'])->name("msdev2.shopify.callback");

Route::post('plan/subscribe',[PlanController::class,'planSubscribe'])->name('msdev2.shopify.plan.subscribe');
Route::get('plan/approve',[PlanController::class,'planAccept'])->name('msdev2.shopify.plan.approve');

Route::middleware(['msdev2.shopify.verify','web','msdev2.load.shop'])->group(function(){
    Route::get('plan',[PlanController::class,'plan'])->name("msdev2.shopify.plan.index");
    Route::get('help',[ShopifyController::class,'help'])->name("msdev2.shopify.help");
    Route::get('ticket',[TicketController::class,'index'])->name("msdev2.shopify.ticket");
    Route::post('ticket',[TicketController::class,'store'])->name("msdev2.shopify.saveticket");
});
Route::middleware(['web'])->group(function(){
    Route::get('agent/login',[AgentController::class,'login'])->name("msdev2.agent.login");
    Route::post('agent/login',[AgentController::class,'authenticate'])->name("msdev2.agent.dologin");
    Route::middleware(['msdev2.agent.auth'])->group(function(){
        Route::prefix("agent")->group(function(){
            Route::get('/logs',[LogsController::class,'index'])->name("msdev2.agent.logs");
            Route::get('/logs/download',[LogsController::class,'download'])->name("msdev2.agent.logs.download");
            Route::post('/logs/clear',[LogsController::class,'clear'])->name("msdev2.agent.logs.clear");
            Route::post('/logs/delete',[LogsController::class,'delete'])->name("msdev2.agent.logs.delete");
            //  Homepage Route - Redirect based on user role is in controller.
            Route::get('/', [AgentController::class,'dashboard'])->name("msdev2.agent.dashboard");
            Route::get('/shops', [AgentController::class,'shops'])->name("msdev2.agent.shops");
            // Agent shop lookup endpoints used by the dashboard autocomplete
            Route::get('/shops/search', [AgentController::class, 'shopSearch'])->name('msdev2.agent.shops.search');
            Route::get('/shops/recent', [AgentController::class, 'shopsRecent'])->name('msdev2.agent.shops.recent');
            Route::get('/shops/stats', [AgentController::class, 'shopStats'])->name('msdev2.agent.shops.stats');
            Route::get('/shops/latest/installs', [AgentController::class, 'latestInstalls'])->name('msdev2.agent.shops.latest.installs');
            Route::get('/shops/latest/uninstalls', [AgentController::class, 'latestUninstalls'])->name('msdev2.agent.shops.latest.uninstalls');
            Route::post('/shops/{id}/metadata', [AgentController::class, 'updateMetadata'])->name('msdev2.agent.shops.metadata.update');
            Route::delete('/shops/{id}/metadata/{key}', [AgentController::class, 'deleteMetadata'])->name('msdev2.agent.shops.metadata.delete');
            Route::get('/shops/recent', [AgentController::class, 'shopsRecent'])->name('msdev2.agent.shops.recent');
            Route::get('/shops/stats', [AgentController::class, 'shopStats'])->name('msdev2.agent.shops.stats');
            Route::get('/shops/{id}', [AgentController::class, 'shopDetail'])->name('msdev2.agent.shops.detail');
            // Full shop detail page for agent (UI)
            Route::get('/shops/{id}/view', [AgentController::class, 'shopView'])->name('msdev2.agent.shops.view');
            // Direct login: generate token and redirect to app root with token
            Route::get('/shops/{id}/direct', [AgentController::class, 'directLogin'])->name('msdev2.agent.shops.direct');
            Route::get('/tickets', [TicketController::class, 'tickets'])->name("msdev2.agent.tickets");
            Route::get('/tickets/resolve/{id}', [TicketController::class, 'ticketsResolve'])->name("msdev2.agent.ticket.resolve");
            Route::get('/logout', [AgentController::class,'logout'])->name("msdev2.agent.logout");
            Route::post('/shopify-graph', [AgentController::class, 'shopifyGraph'])->name("msdev2.agent.shopify.graph"); // Add your auth middleware
        });
    });
});
