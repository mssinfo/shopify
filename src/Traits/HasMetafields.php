<?php

namespace Msdev2\Shopify\Traits;

use Illuminate\Support\Facades\Log;

trait HasMetafields
{
    /**
     * Sets a public or private metafield.
     */
    public function setMetaField(array $metaField, bool $isPrivateMeta = true): void
    {
        $shop = (is_string($this->shop) ? mShop($this->shop) : $this->shop) ?? mShop();
        if (!$shop) {
            if(config('msdev2.debug')) Log::warning('setMetaField skipped: Shop context could not be determined.');
            return;
        }

        if (empty($metaField['namespace'])) {
            $metaField['namespace'] = config('msdev2.app_id');
        }

        if (empty($metaField['type'])) {
            $metaField['type'] = 'json';
        }

        if ($isPrivateMeta) {
            self::setPrivateMetaField($shop, $metaField);
        } else {
            self::setPublicMetaField($shop, $metaField);
        }
    }

    /**
     * Deletes a public or private metafield.
     */
    public function deleteMetaField(string $key, string $namespace = '', bool $isPrivateMeta = true): void
    {
        $shop = method_exists($this, 'shop') ? $this->shop : $this;
        if (!$shop) {
            if(config('msdev2.debug')) Log::warning('deleteMetaField skipped: Shop context could not be determined.');
            return;
        }

        $namespace = $namespace !== '' ? $namespace : config('msdev2.app_id');

        if ($isPrivateMeta) {
            self::deletePrivateMetaField($shop, $namespace, $key);
        } else {
            self::deletePublicMetaField($shop, $namespace, $key);
        }
    }

    // ---------------- PRIVATE META ----------------

    /**
     * Sets a private metafield with a self-healing retry mechanism for stale AppInstallationID errors.
     *
     * @param mixed $shop The shop object.
     * @param array $metaField The metafield data.
     * @param bool $isRetry A flag to prevent infinite retry loops.
     */
    private static function setPrivateMetaField($shop, array $metaField, bool $isRetry = false): void
    {
        $ownerId = self::getAppInstallationId($shop);
        if (!$ownerId) {
            Log::error('Failed to set private metafield: Could not retrieve App Installation ID.', ['shop' => $shop->shop]);
            return;
        }

        $metaField['ownerId'] = $ownerId;

        $query = <<<'GQL'
            mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
                metafieldsSet(metafields: $metafields) {
                    metafields { id namespace key type value }
                    userErrors { field message }
                }
            }
        GQL;

        $variables = ['metafields' => [$metaField]];
        $response = mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();

        $userErrors = $response['data']['metafieldsSet']['userErrors'] ?? [];
        $hasOwnerError = false;

        foreach ($userErrors as $error) {
            if (isset($error['message']) && (str_contains($error['message'], 'Owner does not exist') || str_contains($error['message'], 'ApiPermission metafields can only be created'))) {
                $hasOwnerError = true;
                break;
            }
        }

        // CORE LOGIC: If an owner error is found and we haven't retried yet,
        // refresh the ID and try the entire function again.
        if ($hasOwnerError && !$isRetry) {
            if(config('msdev2.debug')) {
                Log::warning('Stale AppInstallationID detected. Refreshing and retrying.', [
                    'shop' => $shop->shop,
                    'stale_ownerId' => $ownerId
                ]);
            }

            // This performs the "delete and save again" for the ID.
            self::refreshAppInstallationId($shop);

            // Retry the mutation. The 'true' flag prevents an infinite loop.
            self::setPrivateMetaField($shop, $metaField, true);
            return;
        }

        if (!empty($userErrors)) {
            Log::error('GraphQL Error setting private metafield', ['errors' => $userErrors, 'shop' => $shop->shop]);
        }
    }

    /**
     * Retrieves the current app installation ID, using a cached value if available.
     */
    private static function getAppInstallationId($shop): ?string
    {
        $cachedId = $shop->meta('_current_app_installation_id');
        if ($cachedId) {
            return $cachedId;
        }

        $query = <<<'GQL'
            query { currentAppInstallation { id } }
        GQL;

        try {
            $response = mGraph($shop)->query(['query' => $query])->getDecodedBody();
            $id = $response['data']['currentAppInstallation']['id'] ?? null;

            if ($id) {
                // Cache the new ID in the shop's metadata for future use
                $shop->meta('_current_app_installation_id', $id);
            }
            return $id;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch App Installation ID: " . $e->getMessage(), ['shop' => $shop->shop]);
            return null;
        }
    }

    /**
     * THIS IS THE "DELETE AND SAVE AGAIN" LOGIC FOR THE ID.
     * It deletes the cached `_current_app_installation_id` and fetches a fresh one.
     */
    private static function refreshAppInstallationId($shop): void
    {
        // "Delete metafield _current_app_installation_id"
        $shop->deleteMeta('_current_app_installation_id');

        // If your model uses relationship caching, clear it to force a fresh DB query.
        if (method_exists($shop, 'unsetRelation')) {
            $shop->unsetRelation('meta');
        }
        if(config('msdev2.debug')) Log::info('Cleared cached AppInstallationId.', ['shop' => $shop->shop]);

        // "Save again": This fetches and caches the new ID.
        self::getAppInstallationId($shop);
    }

    // All other methods remain for completeness
    private static function deletePrivateMetaField($shop, string $namespace, string $key): void
    {
        $query = <<<'GQL'
            mutation metafieldDelete($namespace: String!, $key: String!) {
                metafieldDelete(namespace: $namespace, key: $key) {
                    deletedId
                    userErrors { field message }
                }
            }
        GQL;
        $variables = ['namespace' => $namespace, 'key' => $key];
        mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();
    }

    private static function setPublicMetaField($shop, array $metaField): void
    {
        $query = <<<'GQL'
            mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
                metafieldsSet(metafields: $metafields) {
                    metafields { id namespace key }
                    userErrors { field message }
                }
            }
        GQL;
        $metaField['ownerId'] = "gid://shopify/Shop/{$shop->shop_id}";
        $variables = ['metafields' => [$metaField]];
        mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();
    }

    private static function deletePublicMetaField($shop, string $namespace, string $key): void
    {
        $query = <<<'GQL'
            mutation metafieldDelete($namespace: String!, $key: String!) {
                metafieldDelete(namespace: $namespace, key: $key) {
                    deletedId
                    userErrors { field message }
                }
            }
        GQL;
        $variables = ['namespace' => $namespace, 'key' => $key];
        mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();
    }
}