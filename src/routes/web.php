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
});
Route::post("shopify/webhooks/{name?}",[ShopifyController::class,'webhooksAction'])->name('msdev2.shopify.webhooks');
Route::get("authenticate",[ShopifyController::class,'install'])->name("msdev2.shopify.install");
Route::get('auth/callback', [ShopifyController::class,'generateToken'])->name("msdev2.shopify.callback");

Route::post('plan/subscribe',[PlanController::class,'planSubscribe'])->name('msdev2.shopify.plan.subscribe');
Route::get('plan/approve',[PlanController::class,'planAccept'])->name('msdev2.shopify.plan.approve');

Route::middleware(['msdev2.shopify.verify','web'])->group(function(){
    Route::get('plan',[PlanController::class,'plan'])->name("msdev2.shopify.plan.index");
    Route::get('logs',[LogsController::class,'index'])->name("msdev2.shopify.logs.index");
    Route::get('help',[ShopifyController::class,'help'])->name("msdev2.shopify.help");
    Route::get('ticket',[TicketController::class,'index'])->name("msdev2.shopify.ticket");
    Route::post('ticket',[TicketController::class,'store'])->name("msdev2.shopify.saveticket");
});
Route::middleware(['web'])->group(function(){
    Route::get('agent/login',[AgentController::class,'login'])->name("msdev2.agent.login");
    Route::post('agent/login',[AgentController::class,'authenticate'])->name("msdev2.agent.dologin");
    Route::middleware(['msdev2.agent.auth'])->group(function(){
        //  Homepage Route - Redirect based on user role is in controller.
        Route::get('/agent', [AgentController::class,'dashboard'])->name("msdev2.agent.dashboard");
        Route::get('/agent/tickets', [TicketController::class, 'tickets'])->name("msdev2.agent.tickets");
        Route::get('/agent/tickets/resolve/{id}', [TicketController::class, 'ticketsResolve'])->name("msdev2.agent.ticket.resolve");
        Route::get('/agent/logout', [AgentController::class,'logout'])->name("msdev2.agent.logout");
    });
});
