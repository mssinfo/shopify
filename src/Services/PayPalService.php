<?php

namespace Msdev2\Shopify\Services;

use Illuminate\Support\Facades\Http;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Services\CurrencyConverter;

class PayPalService
{
    protected static function apiBase()
    {
        return config('msdev2.paypal.mode') === 'live' ? 'https://api-m.paypal.com' : 'https://api-m.sandbox.paypal.com';
    }

    protected static function getAccessToken()
    {
        $clientId = config('msdev2.paypal.client_id');
        $secret = config('msdev2.paypal.secret');
        $tokenUrl = self::apiBase() . '/v1/oauth2/token';
        $response = Http::asForm()->withBasicAuth($clientId, $secret)->post($tokenUrl, ['grant_type' => 'client_credentials']);
        if ($response->failed()) {
            \Log::error('PayPal token request failed', ['status' => $response->status(), 'body' => $response->body()]);
            return null;
        }
        $data = $response->json();
        return $data['access_token'] ?? null;
    }

    public static function createOrder(Shop $shop, $qty, $cost)
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'unable_to_get_token'];
        }

        $currency = config('msdev2.paypal.currency', 'USD');
        // If shop country is India or currency configured as INR, convert USD amount to INR
        $country = strtoupper($shop->detail['country_code'] ?? ($shop->detail['country'] ?? ''));
        if ($country === 'IN' || strtoupper($shop->detail['currency'] ?? '') === 'INR') {
            $currency = 'INR';
            $cost = CurrencyConverter::usdToInr((float)$cost);
        }
        $returnUrl = route('msdev2.paypal.success', ['shop' => $shop->shop]);
        $cancelUrl = route('msdev2.paypal.cancel', ['shop' => $shop->shop]);

        $body = [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'amount' => [
                    'currency_code' => $currency,
                    'value' => number_format((float)$cost, 2, '.', ''),
                ],
                'custom_id' => (string)$shop->id,
                'description' => "Credits x{$qty}"
            ]],
            'application_context' => [
                'return_url' => $returnUrl,
                'cancel_url' => $cancelUrl,
            ],
        ];

        $url = self::apiBase() . '/v2/checkout/orders';
        $response = Http::withToken($accessToken)->post($url, $body);
        if ($response->failed()) {
            \Log::error('PayPal create order failed', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'error' => $response->json() ?? $response->body()];
        }

        $data = $response->json();
        // Find approve link
        $approve = null;
        if (!empty($data['links'])) {
            foreach ($data['links'] as $link) {
                if (!empty($link['rel']) && $link['rel'] === 'approve') {
                    $approve = $link['href'];
                    break;
                }
            }
        }

        if (!$approve) {
            \Log::warning('PayPal order created but approve link missing', ['data' => $data]);
            return ['success' => false, 'error' => 'no_approve_link', 'data' => $data];
        }

        return ['success' => true, 'approve_url' => $approve, 'order' => $data];
    }

    public static function captureOrder($orderId)
    {
        $accessToken = self::getAccessToken();
        if (!$accessToken) {
            return ['success' => false, 'error' => 'unable_to_get_token'];
        }

        $url = self::apiBase() . "/v2/checkout/orders/{$orderId}/capture";
        $response = Http::withToken($accessToken)->post($url);
        if ($response->failed()) {
            \Log::error('PayPal capture failed', ['status' => $response->status(), 'body' => $response->body()]);
            return ['success' => false, 'error' => $response->json() ?? $response->body()];
        }

        $data = $response->json();
        return ['success' => true, 'data' => $data];
    }
}
