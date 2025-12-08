<?php

namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Services\CreditService;
use Msdev2\Shopify\Services\UsageBillingService;
use Msdev2\Shopify\Services\PayUService;

class UsageController extends BaseController
{
    public function index(Request $request)
    {
        $shop    = mShop();
        $stats   = CreditService::stats($shop);
        $history = \Msdev2\Shopify\Models\Usage::where('shop_id', $shop->id)
            ->latest()
            ->paginate(20);

        return view('msdev2::usage', compact('shop', 'stats', 'history'));
    }
    public function buyCredits(Request $request)
    {
        $shop = mShop();

        $qty  = (int)$request->qty;
        $cost = (float)$request->cost;

        // Try Shopify usage billing first
        $result = UsageBillingService::bill(
            $shop,
            'credit_purchase',
            $qty,
            $cost,
            "Purchased {$qty} credits"
        );

        if ($result['success']) {
            // UsageBillingService::bill already logs the DB record (with reference_id),
            // so avoid duplicating the purchase record here.
            return ['status' => 'success', 'method' => 'shopify', 'record' => $result['record'] ?? null];
        }

        // FALLBACK — PayU
        $form = PayUService::createPayment($shop, $qty, $cost);

        return [
            'status' => 'fallback_payu',
            'form' => $form
        ];
    }

    public function payuSuccess(Request $req)
    {
        $result = PayUService::handleSuccess($req);

        if (!$result['success']) {
            return view('msdev2::payu.popup-close', ['status' => 'failed']); // still close popup
        }

        return view('msdev2::payu.popup-close', ['status' => 'success']);
    }

    public function payuFailed()
    {
        return view('msdev2::payu.popup-close', ['status' => 'failed']);
    }
}
