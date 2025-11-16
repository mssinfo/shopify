<?php

namespace Msdev2\Shopify\Traits;

use Illuminate\Support\Facades\Log;

trait HasMetafields
{
    /**
     * Set a public or private metafield.
     */
    public function setMetaField(array $metaField, bool $isPrivateMeta = true): void
    {
        $shop = (is_string($this->shop) ? mShop($this->shop) : $this->shop) ?? mShop();
        if (!$shop) {
            Log::warning('setMetaField skipped: Shop context could not be determined.');
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
     * Delete a public or private metafield.
     */
    public function deleteMetaField(string $key, string $namespace = '', bool $isPrivateMeta = true): void
    {
        $shop = method_exists($this, 'shop') ? $this->shop : $this;
        if (!$shop) {
            Log::warning('deleteMetaField skipped: Shop context could not be determined.');
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
     * Sets a private metafield with a retry mechanism for ownerId errors.
     */
    private static function setPrivateMetaField($shop, array $metaField, bool $isRetry = false): void
    {
        $ownerId = self::getAppInstallationId($shop);
        if (!$ownerId) {
            Log::error('Failed to set private metafield: Could not retrieve App Installation ID.');
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
        
        // CORRECTED: Pass query and variables in a single associative array
        $response = mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();

        // Check for the specific "Owner does not exist" error to trigger a retry
        $userErrors = $response['data']['metafieldsSet']['userErrors'] ?? [];
        foreach ($userErrors as $error) {
            if (
                !$isRetry && // Ensure we only retry once
                isset($error['message']) &&
                (str_contains($error['message'], 'Owner does not exist') || str_contains($error['message'], 'ApiPermission metafields can only be created'))
            ) {
                Log::info('OwnerId error detected. Refreshing AppInstallationId and retrying.', ['shop' => $shop->shop]);
                self::refreshAppInstallationId($shop);
                self::setPrivateMetaField($shop, $metaField, true); // Pass true to prevent infinite loops
                return;
            }
        }

        if (!empty($userErrors)) {
             Log::error('GraphQL Error setting private metafield', ['errors' => $userErrors, 'shop' => $shop->shop]);
        }
    }

    /**
     * Deletes a private metafield.
     */
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
        
        // CORRECTED: Pass query and variables in a single associative array
        mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();
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
            // CORRECTED: Pass query in a single associative array
            $response = mGraph($shop)->query(['query' => $query])->getDecodedBody();
            $id = $response['data']['currentAppInstallation']['id'] ?? null;

            if ($id) {
                $shop->meta('_current_app_installation_id', $id);
            }
            return $id;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch App Installation ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Forces a refresh of the App Installation ID.
     */
    private static function refreshAppInstallationId($shop): ?string
    {
        $shop->meta()->where('key', '_current_app_installation_id')->delete();
        Log::info('Cleared cached AppInstallationId.', ['shop' => $shop->shop]);
        return self::getAppInstallationId($shop);
    }

    // ---------------- PUBLIC META ----------------

    /**
     * Sets a public shop-level metafield.
     */
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
        
        // CORRECTED: Pass query and variables in a single associative array
        $response = mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();

        if (!empty($response['data']['metafieldsSet']['userErrors'])) {
            Log::error('GraphQL Error setting public metafield', ['errors' => $response['data']['metafieldsSet']['userErrors']]);
        }
    }

    /**
     * Deletes a public metafield.
     */
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
        
        // CORRECTED: Pass query and variables in a single associative array
        mGraph($shop)->query(['query' => $query, 'variables' => $variables])->getDecodedBody();
    }
}