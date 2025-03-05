<?php
namespace Msdev2\Shopify\Lib;

use Msdev2\Shopify\Traits\SendsShopifyEmails;

abstract class BaseMailHandler
{
    use SendsShopifyEmails;

    public static function processEmail($type, $shop)
    {
        self::sendShopifyEmail($type, $shop);
    }
}