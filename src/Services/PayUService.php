<?php

namespace Msdev2\Shopify\Services;

use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Services\CreditService;
use Msdev2\Shopify\Services\CurrencyConverter;

class PayUService
{
    public static function createPayment(Shop $shop, $qty, $cost)
    {
        $MERCHANT_KEY = config('msdev2.payu.key');
        $SALT         = config('msdev2.payu.salt');
        $PAYU_URL     = config('msdev2.payu.url');

        $txnid = uniqid("tx_");

        $amount = $cost;
        // Convert USD -> INR when merchant/shop is in India
        $country = strtoupper($shop->detail['country_code'] ?? ($shop->detail['country'] ?? ''));
        if ($country === 'IN' || strtoupper($shop->detail['currency'] ?? '') === 'INR') {
            $amount = CurrencyConverter::usdToInr((float)$cost);
        }
        $firstname = $shop->shop;
        $email = $shop->detail['email'] ?? "merchant@example.com";
        $productinfo = "Credit Purchase: {$qty}";

        // Hash sha512(key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT)


        $hashString = $MERCHANT_KEY.'|'.$txnid.'|'.$amount.'|'.$productinfo.'|'.$firstname.'|'.$email.'|'.$shop->id.'|'.$qty.'|||||||||'.$SALT;
        $hash = strtolower(hash('sha512', $hashString));

        return [
            "action"      => $PAYU_URL,
            "key"         => $MERCHANT_KEY,
            "txnid"       => $txnid,
            "amount"      => $amount,
            "firstname"   => $firstname,
            "email"       => $email,
            "phone"       => "0000000000",
            "productinfo" => $productinfo,
            "hash"        => $hash,
            "surl"        => route('msdev2.payu.success', ['shop' => $shop->shop]),
            "furl"        => route('msdev2.payu.failed', ['shop' => $shop->shop]),
            "udf1"        => $shop->id,
            "udf2"        => $qty,
        ];
    }

    /**
     * Handle PayU success callback
     */
    public static function handleSuccess($req)
    {
        if ($req->status !== "success") {
            return ['success' => false];
        }

        $shop = Shop::find($req->udf1);
        $qty  = $req->udf2;
        $cost = $req->amount;

        // Add credits
        CreditService::addPurchased($shop, $qty, $cost, [
            'source' => 'payu',
            'txnid' => $req->txnid
        ]);

        return ['success' => true, 'shop' => $shop];
    }
}
