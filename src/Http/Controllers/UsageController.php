<?php

namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Services\CreditService;
use Msdev2\Shopify\Services\UsageBillingService;
use Msdev2\Shopify\Services\PayUService;
use Msdev2\Shopify\Services\StripeService;
use Msdev2\Shopify\Services\PayPalService;

class UsageController extends BaseController
{
    public function index(Request $request)
    {
        $shop    = mShop();
        $stats   = CreditService::stats($shop);
        $history = \Msdev2\Shopify\Models\Usage::where('shop_id', $shop->id)
            ->latest()
            ->paginate(20);
        $plan = $shop->plan();
        return view('msdev2::usage', compact('shop', 'stats', 'history', 'plan'));
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

        if (!empty($result['success'])) {
            return ['status' => 'success', 'method' => 'shopify', 'record' => $result['record'] ?? null];
        }

        // FALLBACK — choose provider by config
        $provider = ($shop->detail['currency'] ?? '') === 'INR' || ($shop->detail['currency'] ?? '') === 'PKR' ? 'payu' : 'paypal';
        if ($provider === 'stripe') {
            // Create a signed, temporary popup URL so the top-level popup can load without a session cookie
            $params = [
                'shop' => $shop->shop,
                'qty' => $qty,
                'cost' => $cost,
            ];
            $popupUrl = URL::temporarySignedRoute('msdev2.stripe.popup_public', now()->addMinutes(10), $params);

            return [
                'status' => 'fallback_stripe',
                'popup_url' => $popupUrl,
            ];
        }

        if ($provider === 'paypal') {
            $pp = PayPalService::createOrder($shop, $qty, $cost);
            if (!empty($pp['success']) && !empty($pp['approve_url'])) {
                return [
                    'status' => 'fallback_paypal',
                    'approve_url' => $pp['approve_url'],
                ];
            }
             if(config('msdev2.debug')) \Log::warning('PayPal fallback error while creating order', ['shop' => $shop->shop, 'error' => $pp['error'] ?? null]);
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

        if (empty($result['success'])) {
            return view('msdev2::payu.popup-close', ['status' => 'failed']);
        }

        return view('msdev2::payu.popup-close', ['status' => 'success']);
    }

    public function payuFailed()
    {
        return view('msdev2::payu.popup-close', ['status' => 'failed']);
    }

    public function stripeSuccess(Request $request)
    {
        $sessionId = $request->query('session_id');
        $result = StripeService::handleSuccess($sessionId);

        if (!empty($result['success'])) {
            return view('msdev2::stripe.popup-close', ['status' => 'success']);
        }
        return view('msdev2::stripe.popup-close', ['status' => 'failed']);
    }

    public function stripeConfirm(Request $request)
    {
        $paymentIntentId = $request->input('payment_intent_id');
        if (!$paymentIntentId) {
            return ['success' => false, 'message' => 'missing_payment_intent_id'];
        }

        $result = StripeService::handlePaymentIntentSuccess($paymentIntentId);
        if (!empty($result['success'])) {
            return ['success' => true];
        }
        return ['success' => false, 'message' => $result['message'] ?? 'failed'];
    }

    public function stripeCancel()
    {
        return view('msdev2::stripe.popup-close', ['status' => 'failed']);
    }
    // Render popup page which will mount Stripe Elements and create the PaymentIntent via AJAX
    public function stripePopup(Request $request)
    {
        $shop = mShop();
        $qty = (int)$request->query('qty');
        $cost = (float)$request->query('cost');
        $name = $request->query('name');
        $email = $request->query('email');

        // Determine display currency/amount (convert to INR for India)
        $displayCurrency = 'USD';
        $displayCost = $cost;
        $country = $shop->detail['country_code'] ?? ($shop->detail['country'] ?? null);
        if (strtoupper($country) === 'IN') {
            // Convert USD -> INR for display
            $displayCost = \Msdev2\Shopify\Services\CurrencyConverter::usdToInr($cost);
            $displayCurrency = 'INR';
        }

        return view('msdev2::stripe.popup', compact('shop', 'qty', 'cost', 'name', 'email', 'displayCurrency', 'displayCost'));
    }

    // Public signed popup (no session required) - displays the same popup but requires a valid signature
    public function stripePopupPublic(Request $request)
    {
        if (! $request->hasValidSignature()) {
            abort(403, 'Invalid or expired link');
        }

        $shopHandle = $request->query('shop');
        $shop = Shop::where('shop', $shopHandle)->first();
        if (! $shop) {
            abort(404, 'Shop not found');
        }

        $qty = (int)$request->query('qty');
        $cost = (float)$request->query('cost');

        // Determine display currency/amount (convert to INR for India)
        $displayCurrency = 'USD';
        $displayCost = $cost;
        $country = $shop->detail['country_code'] ?? ($shop->detail['country'] ?? null);
        if (strtoupper($country) === 'IN') {
            $displayCost = \Msdev2\Shopify\Services\CurrencyConverter::usdToInr($cost);
            $displayCurrency = 'INR';
        }

        // Pass the signed query so the AJAX call can validate it server-side
        $signed_query = $request->getQueryString();

        return view('msdev2::stripe.popup', compact('shop', 'qty', 'cost', 'displayCurrency', 'displayCost'))->with('signed_query', $signed_query);
    }

    // Public create intent endpoint - expects 'signed_query' in POST body and validates signature
    public function stripeCreateIntentPublic(Request $request)
    {
        $signed = $request->input('signed_query');
        if (! $signed) {
            return ['success' => false, 'error' => 'missing_signature'];
        }

        // Recreate the signed request URL using the app URL and validate signature
        $appUrl = rtrim(config('app.url') ?: env('APP_URL', ''), '/');
        $full = $appUrl . '/stripe/popup_public?' . $signed;
        $fake = \Illuminate\Http\Request::create($full);
        // First try validating against configured APP_URL
        if (\Illuminate\Support\Facades\URL::hasValidSignature($fake)) {
            $shopHandle = $fake->query('shop');
        } else {
            // Fallback: try validating against the current request origin (scheme+host)
            $origin = rtrim($request->getSchemeAndHttpHost(), '/');
            $altFull = $origin . '/stripe/popup_public?' . $signed;
            $altFake = \Illuminate\Http\Request::create($altFull);
            if (\Illuminate\Support\Facades\URL::hasValidSignature($altFake)) {
                $shopHandle = $altFake->query('shop');
            } else {
                 if(config('msdev2.debug')) \Log::warning('Invalid signed query for stripeCreateIntentPublic', ['app_full' => $full, 'alt_full' => $altFull]);
                return ['success' => false, 'error' => 'invalid_signature', 'debug' => ['app_full' => $full, 'alt_full' => $altFull]];
            }
        }
        $shop = Shop::where('shop', $shopHandle)->first();
        if (! $shop) {
            return ['success' => false, 'error' => 'shop_not_found'];
        }

        $qty = (int)$request->input('qty');
        $cost = (float)$request->input('cost');
        $name = $request->input('name');
        $email = $request->input('email');

        $res = StripeService::createPayment($shop, $qty, $cost, $name, $email, false);
        if (!empty($res['success']) && !empty($res['client_secret'])) {
            return ['success' => true, 'client_secret' => $res['client_secret']];
        }

         if(config('msdev2.debug')) \Log::warning('Stripe create intent public failed', ['shop' => $shop->id, 'response' => $res]);
        return ['success' => false, 'error' => $res['error'] ?? 'failed', 'debug' => $res];
    }

    // AJAX endpoint used by the popup to create PaymentIntent and return client_secret
    public function stripeCreateIntent(Request $request)
    {
        $shop = mShop();
        $qty = (int)$request->input('qty');
        $cost = (float)$request->input('cost');
        $name = $request->input('name');
        $email = $request->input('email');

        $res = StripeService::createPayment($shop, $qty, $cost, $name, $email, false);

        if (!empty($res['success']) && !empty($res['client_secret'])) {
            return ['success' => true, 'client_secret' => $res['client_secret']];
        }

        // Log full response for debugging (avoid leaking secrets in logs)
         if(config('msdev2.debug')) \Log::warning('Stripe create intent failed', ['shop' => $shop->id, 'response' => $res]);

        // Derive a user-friendly error message
        $errMsg = null;
        if (is_array($res)) {
            if (!empty($res['error'])) {
                $errMsg = is_string($res['error']) ? $res['error'] : json_encode($res['error']);
            } elseif (!empty($res['message'])) {
                $errMsg = $res['message'];
            } else {
                $errMsg = json_encode($res);
            }
        } else {
            $errMsg = is_string($res) ? $res : json_encode($res);
        }

        return ['success' => false, 'error' => $errMsg, 'debug' => $res];
    }

    public function paypalSuccess(Request $request)
    {
        $token = $request->query('token');
        if (!$token) {
            return view('msdev2::payu.popup-close', ['status' => 'failed']);
        }

        $result = \Msdev2\Shopify\Services\PayPalService::captureOrder($token);
        if (!empty($result['success'])) {
            $data = $result['data'] ?? [];
            $order = $data;
            $shopId = null; $qty = 0; $cost = 0;
            if (!empty($order['purchase_units'][0]['custom_id'])) {
                $shopId = $order['purchase_units'][0]['custom_id'];
            }
            if (!empty($order['purchase_units'][0]['description'])) {
                if (preg_match('/x(\d+)/', $order['purchase_units'][0]['description'], $m)) {
                    $qty = (int)$m[1];
                }
            }
            if (!empty($order['purchase_units'][0]['payments']['captures'][0]['amount']['value'])) {
                $cost = (float)$order['purchase_units'][0]['payments']['captures'][0]['amount']['value'];
            }

            if ($shopId && $qty > 0) {
                $shop = \Msdev2\Shopify\Models\Shop::find($shopId);
                if ($shop) {
                    \Msdev2\Shopify\Services\CreditService::addPurchased($shop, $qty, $cost, [
                        'source' => 'paypal',
                        'order_id' => $token,
                    ]);
                }
            }
            return view('msdev2::paypal.popup-close', ['status' => 'success']);
        }
        return view('msdev2::paypal.popup-close', ['status' => 'failed']);
    }

    public function paypalCancel()
    {
        return view('msdev2::paypal.popup-close', ['status' => 'failed']);
    }
}
