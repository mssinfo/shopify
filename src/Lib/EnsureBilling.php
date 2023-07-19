<?php

namespace Msdev2\Shopify\Lib;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Utils;
use Shopify\Clients\Graphql;
use Shopify\Context;

class EnsureBilling
{
    public const INTERVAL_ONE_TIME = "ONE_TIME";
    public const INTERVAL_EVERY_30_DAYS = "EVERY_30_DAYS";
    public const INTERVAL_ANNUAL = "ANNUAL";

    private static $RECURRING_INTERVALS = [
        self::INTERVAL_EVERY_30_DAYS, self::INTERVAL_ANNUAL
    ];
    public static $subscriptionId;
    public static $trialDays;
    /**
     * Check if the given shop has an active payment based on the configs.
     *
     * @param Shop $shop The current shop to check
     * @param array   $config  Associative array that accepts keys:
     *                         - "chargeName": string, the name of the charge
     *                         - "amount": float
     *                         - "currencyCode": string
     *                         - "interval": one of the INTERVAL_* consts
     *
     * @return array Array containing
     * - hasPayment: bool
     * - confirmationUrl: string|null
     */
    public static function check(Shop $shop, array $config): array
    {
        $confirmationUrl = null;
        self::$trialDays = $config["trialDays"];
        $appUsed = $shop->appUsedDay();
        self::$trialDays = $config["trialDays"] > $appUsed ? $config["trialDays"] - $appUsed : 0;
        $hasPayment = false;
        $confirmationUrl = self::requestPayment($shop, $config);
        return [$hasPayment, $confirmationUrl];
    }

    private static function hasActivePayment(Shop $shop, array $config): bool
    {
        if (self::isRecurring($config)) {
            return self::hasSubscription($shop, $config);
        } else {
            return self::hasOneTimePayment($shop, $config);
        }
    }

    private static function hasSubscription(Shop $shop, array $config): bool
    {
        $responseBody = self::queryOrException($shop, self::RECURRING_PURCHASES_QUERY);
        $subscriptions = $responseBody["data"]["currentAppInstallation"]["activeSubscriptions"];
        if(!empty($subscriptions)){
            self::$subscriptionId = $subscriptions[0]['id'];
            foreach ($subscriptions as $subscription) {
                if ( $subscription["name"] === $config["chargeName"] && ($shop->isTestStore() || !$subscription["test"]) ) {
                    return true;
                }
            }
        }

        return false;
    }

    private static function hasOneTimePayment(Shop $shop, array $config): bool
    {
        $purchases = null;
        $endCursor = null;
        do {
            $responseBody = self::queryOrException(
                $shop,
                [
                    "query" => self::ONE_TIME_PURCHASES_QUERY,
                    "variables" => ["endCursor" => $endCursor]
                ]
            );
            $purchases = $responseBody["data"]["currentAppInstallation"]["oneTimePurchases"];

            foreach ($purchases["edges"] as $purchase) {
                $node = $purchase["node"];
                if (
                    $node["name"] === $config["chargeName"] && ($shop->isTestStore() || !$node["test"]) &&
                    $node["status"] === "ACTIVE"
                ) {
                    return true;
                }
            }

            $endCursor = $purchases["pageInfo"]["endCursor"];
        } while ($purchases["pageInfo"]["hasNextPage"]);

        return false;
    }

    /**
     * @return string|null
     */
    private static function requestPayment(Shop $shop, array $config)
    {
        $shopName = $shop->shop;
        $host = base64_encode("$shopName/admin");
        $returnUrl = route('msdev2.shopify.plan.approve')."?shop={$shopName}&host=$host&plan=".$config["chargeName"];
        if($config["amount"]==0){
            $data["confirmationUrl"] = $returnUrl;
        }else if (self::isRecurring($config)) {
            $data = self::requestRecurringPayment($shop, $config, $returnUrl);
            $data = $data["data"]["appSubscriptionCreate"];
        } else {
            $data = self::requestOneTimePayment($shop, $config, $returnUrl);
            $data = $data["data"]["appPurchaseOneTimeCreate"];
        }

        if (!empty($data["userErrors"])) {
            abort("Error while billing the store". $data["userErrors"]);
        }

        return $data["confirmationUrl"];
    }

    private static function requestRecurringPayment(Shop $shop, array $config, string $returnUrl): array
    {

        return self::queryOrException(
            $shop,
            [
                "query" => self::RECURRING_PURCHASE_MUTATION,
                "variables" => [
                    "name" => $config["chargeName"],
                    "lineItems" => [
                        "plan" => [
                            "appRecurringPricingDetails" => [
                                "interval" => $config["interval"],
                                "price" => ["amount" => $config["amount"], "currencyCode" => $config["currencyCode"]],
                            ],
                        ],
                    ],
                    "returnUrl" => $returnUrl,
                    "test" => $shop->isTestStore(),
                    "trialDays" => self::$trialDays
                ],
            ]
        );
    }

    private static function requestOneTimePayment(Shop $shop, array $config, string $returnUrl): array
    {
        return self::queryOrException(
            $shop,
            [
                "query" => self::ONE_TIME_PURCHASE_MUTATION,
                "variables" => [
                    "name" => $config["chargeName"],
                    "price" => ["amount" => $config["amount"], "currencyCode" => $config["currencyCode"]],
                    "returnUrl" => $returnUrl,
                    "test" => $shop->isTestStore(),
                    "trialDays" => self::$trialDays
                ],
            ]
        );
    }
    public static function requestCancelSubscription(Shop $shop, $id){
        return self::queryOrException(
            $shop,
            [
                "query" => self::CANCEL_PURCHASE_MUTATION,
                "variables" => [
                    "id" => $id
                ],
            ]
        );
    }

    private static function isRecurring(array $config): bool
    {
        return in_array($config["interval"], self::$RECURRING_INTERVALS);
    }

    /**
     * @param string|array $query
     */
    private static function queryOrException(Shop $shop, $query): array
    {
        $client = new Graphql($shop->shop, $shop->access_token);

        $response = $client->query($query);
        $responseBody = $response->getDecodedBody();
        if (!empty($responseBody["errors"])) {
            throw new Exception("Error while billing the store", $responseBody["errors"]);
        }

        return $responseBody;
    }

    private const RECURRING_PURCHASES_QUERY = <<<'QUERY'
    query appSubscription {
        currentAppInstallation {
            activeSubscriptions {
                id, name, test
            }
        }
    }
    QUERY;

    private const ONE_TIME_PURCHASES_QUERY = <<<'QUERY'
    query appPurchases($endCursor: String) {
        currentAppInstallation {
            oneTimePurchases(first: 250, sortKey: CREATED_AT, after: $endCursor) {
                edges {
                    node {
                        id, name, test, status
                    }
                }
                pageInfo {
                    hasNextPage, endCursor
                }
            }
        }
    }
    QUERY;

    private const RECURRING_PURCHASE_MUTATION = <<<'QUERY'
    mutation createPaymentMutation(
        $name: String!
        $lineItems: [AppSubscriptionLineItemInput!]!
        $returnUrl: URL!
        $test: Boolean,
        $trialDays: Int
    ) {
        appSubscriptionCreate(
            name: $name
            lineItems: $lineItems
            returnUrl: $returnUrl
            test: $test,
            trialDays: $trialDays
        ) {
            confirmationUrl
            userErrors {
                field, message
            }
        }
    }
    QUERY;

    private const ONE_TIME_PURCHASE_MUTATION = <<<'QUERY'
    mutation createPaymentMutation(
        $name: String!
        $price: MoneyInput!
        $returnUrl: URL!
        $test: Boolean,
        $trialDays: Int
    ) {
        appPurchaseOneTimeCreate(
            name: $name
            price: $price
            returnUrl: $returnUrl
            test: $test
            trialDays: $trialDays
        ) {
            confirmationUrl
            userErrors {
                field, message
            }
        }
    }
    QUERY;


    private const CANCEL_PURCHASE_MUTATION = <<<'QUERY'
    mutation appSubscriptionCancel(
        $id: ID!
    ) {
        appSubscriptionCancel(
            id: $id
        ) {
            appSubscription {
                status
            }
            userErrors {
                field, message
            }
        }
    }
    QUERY;
}
