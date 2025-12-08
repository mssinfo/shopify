<?php

namespace Msdev2\Shopify\Services;

use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Usage;

class UsageBillingService
{
    /**
     * Log usage only (no billing)
     */
    public static function log(Shop $shop, $type, $qty = 1, $meta = [])
    {
        return $shop->usages()->create([
            'type'     => $type,
            'quantity' => $qty,
            'cost'     => 0,
            'meta'     => $meta,
        ]);
    }

    /**
     * Create Shopify usage charge via GraphQL + log in DB
     */
    public static function bill(Shop $shop, $type, $qty, $cost, $description, $meta = [])
    {
        if($shop->meta('_use_payu_only')){
            // Forced to use PayU only
            return [
                'success' => false,
                'fallback_payu' => true,  // Tell UI to open PayU
                'errors' => ['Forced to use PayU only']
            ];
        }
        try {

            // Attempt normal Shopify usage billing
            $lineItemId = self::getSubscriptionLineItemId($shop);

            $mutation = <<<'GQL'
                mutation appUsageRecordCreate(
                $subscriptionLineItemId: ID!
                $description: String!
                $price: MoneyInput!
                ) {
                appUsageRecordCreate(
                    subscriptionLineItemId: $subscriptionLineItemId
                    description: $description
                    price: $price
                ) {
                    appUsageRecord {
                    id
                    description
                    createdAt
                    }
                    userErrors {
                    field
                    message
                    }
                }
                }
            GQL;

            $response = mGraph($shop)->query([
                "query" => $mutation,
                "variables" => [
                    "subscriptionLineItemId" => $lineItemId,
                    "description"            => $description,
                    "price" => [
                        "amount"       => (string)$cost,
                        "currencyCode" => "USD",
                    ]
                ]
            ]);

            $body = $response->getDecodedBody();

            // Shopify returned userErrors (billing failed)
            if (!empty($body['data']['appUsageRecordCreate']['userErrors'])) {
                \Log::warning("Shopify Usage Billing Failed", $body);
                $shop->meta('_use_payu_only', true);
                return [
                    'success' => false,
                    'fallback_payu' => true,   // Tell UI to open PayU
                    'errors' => $body['data']['appUsageRecordCreate']['userErrors']
                ];
            }

            // Billing success
            $usageRecord = $body['data']['appUsageRecordCreate']['appUsageRecord'];

            // Log in DB
            
            $record = $shop->usages()->create([
                'type'         => $type,
                'quantity'     => $qty,
                'cost'         => $cost,
                'reference_id' => $usageRecord['id'] ?? null,
                'meta'         => $meta,
            ]);
            $shop->meta('_use_payu_only', false);
            return [
                'success' => true,
                'fallback_payu' => false,
                'record' => $record
            ];

        } catch (\Throwable $e) {

            \Log::error("Usage Billing Exception", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $shop->meta('_use_payu_only', true);
            return [
                'success' => false,
                'fallback_payu' => true,  // Auto fallback enabled
                'errors' => [$e->getMessage()]
            ];
        }
    }

    public static function getSubscriptionLineItemId(Shop $shop)
    {
        // If exists in meta → use it
        $existing = $shop->meta('_subscription_line_item_id');
        if ($existing) {
            return $existing;
        }

        // else fetch from Shopify
        $chargeId = $shop->lastCharge->charge_id;

        $query = <<<'GQL'
            query getSubscription($id: ID!) {
            node(id: $id) {
                ... on AppSubscription {
                id
                lineItems {
                    id
                }
                }
            }
            }
        GQL;

        $response = mGraph($shop)->query([
            "query" => $query,
            "variables" => [
                "id" => "gid://shopify/AppSubscription/{$chargeId}",
            ]
        ]);

        $body = $response->getDecodedBody();

        $lineItemId = data_get($body, "data.node.lineItems.0.id");

        if (!$lineItemId) {
            \Log::error("FAILED TO FETCH lineItemId", $body);
            throw new \Exception("Cannot fetch subscriptionLineItemId from Shopify.");
        }

        // Save to meta
        $shop->meta('_subscription_line_item_id', $lineItemId);

        return $lineItemI;
    }

}
