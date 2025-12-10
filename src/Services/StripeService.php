<?php

namespace Msdev2\Shopify\Services;

use Illuminate\Support\Facades\Http;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Services\CurrencyConverter;

class StripeService
{
    public static function createPayment(Shop $shop, $qty, $cost, $name = null, $email = null, $useCheckout = true)
    {
        $secret = config('msdev2.stripe.secret');
        // fallback to environment variable if config not populated (module .env may not be loaded)
        if (empty($secret)) {
            $secret = getenv('STRIPE_SECRET') ?: getenv('STRIPE_KEY') ?: env('STRIPE_SECRET');
            if (!empty($secret)) {
                \Log::warning('Using STRIPE secret from environment fallback for StripeService.createPayment');
            }
        }
        $currency = config('msdev2.stripe.currency', 'USD');

        // If shop is in India (or currency requested is INR), convert USD cost to INR
        if (strtoupper($detail['country_code'] ?? ($detail['country'] ?? '')) === 'IN' || strtoupper(($detail['currency'] ?? '') ) === 'INR') {
            $cost = CurrencyConverter::usdToInr((float)$cost);
            // ensure currency variable reflects INR
            $currency = 'INR';
        }

        // Stripe expects amount in smallest currency unit (cents/paise)
        $amountCents = (int) round($cost * 100);

        // Pull details from shop but allow overriding with provided name/email
        $detail = $shop->detail;
        $email = $email ?: ($detail['email'] ?? null);
        $name = $name ?: ($detail['name'] ?? null);

        // If shop country requires INR (India/Pakistan), adapt currency
        if (($detail['country_code'] ?? '') === 'IN' || ($detail['country_code'] ?? '') === 'PK') {
            $currency = 'INR';
        }

        // If requested, create a Checkout Session (top-level) so embedded apps avoid Permissions-Policy issues
        if ($useCheckout) {
            $successUrl = route('msdev2.stripe.success', ['shop' => $shop->shop]) . '?session_id={CHECKOUT_SESSION_ID}';
            $cancelUrl = route('msdev2.stripe.cancel', ['shop' => $shop->shop]);

            $params = [
                'payment_method_types[]' => 'card',
                'mode' => 'payment',
                'customer_email' => $email,
                'line_items[0][price_data][currency]' => strtolower($currency),
                'line_items[0][price_data][product_data][name]' => "Credits x{$qty}",
                'line_items[0][price_data][unit_amount]' => $amountCents,
                'line_items[0][quantity]' => 1,
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'metadata[shop_id]' => $shop->id,
                'metadata[qty]' => $qty,
                'metadata[cost]' => $cost,
            ];

            // include static US billing address if present (helps for some gateways)
            $params['billing_address_collection'] = 'required';
            $params['payment_intent_data[receipt_email]'] = $email;

            $response = Http::asForm()->withToken($secret)->post('https://api.stripe.com/v1/checkout/sessions', $params);

            if ($response->failed()) {
                \Log::error('Stripe create session failed', ['status' => $response->status(), 'body' => $response->body()]);
                return ['success' => false, 'error' => $response->body()];
            }

            $body = $response->json();
            // Stripe usually returns a 'url' for Checkout Session. If not present, treat as failure.
            if (empty($body['url'])) {
                \Log::warning('Stripe checkout session created but no url returned', ['body' => $body]);
                return ['success' => false, 'error' => $body];
            }

            return ['success' => true, 'checkout_url' => $body['url'], 'session' => $body];
        }

        // Fallback: create a Customer + PaymentIntent for client-side confirmation
        try {
            // Always use US static address as requested for Customer
            $customerParams = [
                'name' => $name,
                'email' => $email,
                'address[line1]' => '123 Export Street',
                'address[city]' => 'New York',
                'address[state]' => 'NY',
                'address[postal_code]' => '10001',
                'address[country]' => 'US',
                'metadata[shop_id]' => $shop->id,
            ];

            $custResp = Http::asForm()->withToken($secret)->post('https://api.stripe.com/v1/customers', $customerParams);
            if ($custResp->failed()) {
                \Log::error('Stripe create customer failed', ['status' => $custResp->status(), 'body' => $custResp->body()]);
                return ['success' => false, 'error' => $custResp->body()];
            }
            $customerBody = $custResp->json();
            $customerId = $customerBody['id'] ?? null;
            if (!$customerId) {
                \Log::error('Stripe customer created but id missing', ['body' => $customerBody]);
                return ['success' => false, 'error' => $customerBody];
            }

            // Create PaymentIntent for client-side confirmation
            $piParams = [
                'amount' => $amountCents,
                'currency' => strtolower($currency),
                'customer' => $customerId,
                'payment_method_types[]' => 'card',
                'description' => "Credits x{$qty}",
                'metadata[shop_id]' => $shop->id,
                'metadata[qty]' => $qty,
                'metadata[cost]' => $cost,
                'receipt_email' => $email,
            ];

            $piResp = Http::asForm()->withToken($secret)->post('https://api.stripe.com/v1/payment_intents', $piParams);
            if ($piResp->failed()) {
                \Log::error('Stripe create payment_intent failed', ['status' => $piResp->status(), 'body' => $piResp->body()]);
                return ['success' => false, 'error' => $piResp->body()];
            }

            $piBody = $piResp->json();
            // client_secret required by Stripe.js to confirm/complete the payment
            if (empty($piBody['client_secret'])) {
                \Log::warning('PaymentIntent created but no client_secret returned', ['body' => $piBody]);
                return ['success' => false, 'error' => $piBody];
            }

            return [
                'success' => true,
                'client_secret' => $piBody['client_secret'],
                'payment_intent' => $piBody,
                'customer' => $customerBody,
            ];

        } catch (\Throwable $e) {
            \Log::error('Stripe create flow exception', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
    
    public static function handleSuccess(string $sessionId)
    {
        $secret = config('msdev2.stripe.secret');
        if (empty($secret)) {
            $secret = getenv('STRIPE_SECRET') ?: getenv('STRIPE_KEY') ?: env('STRIPE_SECRET');
            if (!empty($secret)) {
                \Log::warning('Using STRIPE secret from environment fallback for StripeService.handleSuccess');
            }
        }
        if (!$sessionId) return ['success' => false, 'message' => 'no_session'];

        $response = Http::withToken($secret)->get('https://api.stripe.com/v1/checkout/sessions/' . $sessionId);
        if ($response->failed()) {
            return ['success' => false, 'message' => 'failed_fetch', 'error' => $response->body()];
        }

        $session = $response->json();
        // Check payment status
        if (isset($session['payment_status']) && $session['payment_status'] === 'paid') {
            $metadata = $session['metadata'] ?? [];
            $shopId = $metadata['shop_id'] ?? null;
            $qty = (int) ($metadata['qty'] ?? 0);
            $cost = (float) ($metadata['cost'] ?? 0);

            if ($shopId && $qty > 0) {
                $shop = \Msdev2\Shopify\Models\Shop::find($shopId);
                if ($shop) {
                    \Msdev2\Shopify\Services\CreditService::addPurchased($shop, $qty, $cost, [
                        'source' => 'stripe',
                        'session_id' => $sessionId,
                    ]);
                    return ['success' => true, 'shop' => $shop];
                }
            }
            return ['success' => false, 'message' => 'invalid_metadata', 'session' => $session];
        }

        return ['success' => false, 'message' => 'not_paid', 'session' => $session];
    }

    /**
     * Handle PaymentIntent success: fetch PaymentIntent and credit shop if succeeded
     */
    public static function handlePaymentIntentSuccess(string $paymentIntentId)
    {
        $secret = config('msdev2.stripe.secret');
        if (empty($secret)) {
            $secret = getenv('STRIPE_SECRET') ?: getenv('STRIPE_KEY') ?: env('STRIPE_SECRET');
            if (!empty($secret)) {
                \Log::warning('Using STRIPE secret from environment fallback for StripeService.handlePaymentIntentSuccess');
            }
        }
        if (!$paymentIntentId) return ['success' => false, 'message' => 'no_payment_intent'];

        $response = Http::withToken($secret)->get('https://api.stripe.com/v1/payment_intents/' . $paymentIntentId);
        if ($response->failed()) {
            return ['success' => false, 'message' => 'failed_fetch', 'error' => $response->body()];
        }

        $pi = $response->json();
        if (isset($pi['status']) && ($pi['status'] === 'succeeded' || $pi['status'] === 'requires_capture')) {
            $metadata = $pi['metadata'] ?? [];
            $shopId = $metadata['shop_id'] ?? null;
            $qty = (int) ($metadata['qty'] ?? 0);
            $cost = (float) ($metadata['cost'] ?? 0);

            if ($shopId && $qty > 0) {
                $shop = \Msdev2\Shopify\Models\Shop::find($shopId);
                if ($shop) {
                    \Msdev2\Shopify\Services\CreditService::addPurchased($shop, $qty, $cost, [
                        'source' => 'stripe',
                        'payment_intent_id' => $paymentIntentId,
                    ]);
                    return ['success' => true, 'shop' => $shop];
                }
            }
            return ['success' => false, 'message' => 'invalid_metadata', 'payment_intent' => $pi];
        }

        return ['success' => false, 'message' => 'not_succeeded', 'payment_intent' => $pi];
    }
}
