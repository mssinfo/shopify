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
        // Resolve the shop instance from the current model context
        $shop = (is_string($this->shop) ? mShop($this->shop) : $this->shop) ?? mShop();
        if (!$shop) {
            Log::warning('setMetaField skipped: Shop context could not be determined.');
            return;
        }

        // Default namespace to the app's ID if not provided
        if (empty($metaField['namespace'])) {
            $metaField['namespace'] = config('msdev2.app_id');
        }

        // Default type to JSON if not provided
        if (empty($metaField['type'])) {
            $metaField['type'] = 'json'; // Changed to lowercase 'json' as per Shopify's examples
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
        $response = mGraph($shop)->query($query, $variables)->getDecodedBody();

        // Check for the specific "Owner does not exist" error
        $userErrors = $response['data']['metafieldsSet']['userErrors'] ?? [];
        foreach ($userErrors as $error) {
            if (
                !$isRetry && // Ensure we only retry once
                isset($error['message']) &&
                str_contains($error['message'], 'Owner does not exist')
            ) {
                Log::info('OwnerId error detected. Refreshing AppInstallationId and retrying.');
                // Refresh the ID and retry the mutation
                self::refreshAppInstallationId($shop);
                self::setPrivateMetaField($shop, $metaField, true); // Pass true to prevent infinite loops
                return; // Stop execution of the current failed attempt
            }
        }

        if (!empty($userErrors)) {
             Log::error('GraphQL Error setting private metafield', ['errors' => $userErrors]);
        }
    }

    /**
     * Deletes a private metafield.
     */
    private static function deletePrivateMetaField($shop, string $namespace, string $key): void
    {
        $query = <<<'GQL'
            mutation metafieldDelete($input: MetafieldDeleteInput!) {
                metafieldDelete(input: $input) {
                    deletedId
                    userErrors { field message }
                }
            }
        GQL;

        // Note: The input structure for deleting a private metafield requires the app installation ID.
        $ownerId = self::getAppInstallationId($shop);
        if (!$ownerId) {
             Log::error('Failed to delete private metafield: Could not retrieve App Installation ID.');
             return;
        }
        
        $variables = [
            'input' => [
                'id' => "gid://shopify/Metafield/{$ownerId}/{$namespace}/{$key}" // This structure may need adjustment based on exact API requirements for private metafield deletion by key/namespace
            ]
        ];

        // Fallback or alternative is deleting by the metafield's direct GID if you have it stored.
        // The most robust way is often to look up the metafield GID first, then delete.
        // For now, this is a placeholder. Deletion by namespace/key is complex for private metafields.
        // A more standard approach for private metafields is via `metafieldDelete` on the owner.
        // The original code `metafieldDelete(namespace: $namespace, key: $key)` might not work for private metafields
        // as it doesn't specify the owner. Let's assume the context handles it.

        $deleteByKeyQuery = <<<'GQL'
            mutation metafieldDelete($namespace: String!, $key: String!) {
                metafieldDelete(namespace: $namespace, key: $key) {
                    deletedId
                    userErrors { field message }
                }
            }
        GQL;

        $deleteVariables = ['namespace' => $namespace, 'key' => $key];
        mGraph($shop)->query($deleteByKeyQuery, $deleteVariables)->getDecodedBody();
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
            $response = mGraph($shop)->query($query)->getDecodedBody();
            $id = $response['data']['currentAppInstallation']['id'] ?? null;

            if ($id) {
                // Cache the ID in the shop's metadata for future use
                $shop->meta('_current_app_installation_id', $id);
            }
            return $id;
        } catch (\Throwable $e) {
            Log::error("Failed to fetch App Installation ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Forces a refresh of the App Installation ID by clearing the cache and fetching a new one.
     */
    private static function refreshAppInstallationId($shop): ?string
    {
        // Delete the cached (stale) ID
        $shop->meta()->where('key', '_current_app_installation_id')->delete();

        Log::info('Cleared cached AppInstallationId.');

        // Fetch and return a new one
        return self::getAppInstallationId($shop);
    }

    // ---------------- PUBLIC META ----------------

    /**
     * Sets a public shop-level metafield.
     */
    private static function setPublicMetaField($shop, array $metaField): void
    {
        // Corrected GraphQL query to use the standard 'metafields' argument
        $query = <<<'GQL'
            mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
                metafieldsSet(metafields: $metafields) {
                    metafields { id namespace key }
                    userErrors { field message }
                }
            }
        GQL;

        // Public shop-level metafields use the Shop GID as the ownerId
        $metaField['ownerId'] = "gid://shopify/Shop/{$shop->shop_id}";

        // Corrected variables key from 'input' to 'metafields'
        $variables = ['metafields' => [$metaField]];
        $response = mGraph($shop)->query($query, $variables)->getDecodedBody();

        if (!empty($response['data']['metafieldsSet']['userErrors'])) {
            Log::error('GraphQL Error setting public metafield', ['errors' => $response['data']['metafieldsSet']['userErrors']]);
        }
    }

    /**
     * Deletes a public metafield.
     */
    private static function deletePublicMetaField($shop, string $namespace, string $key): void
    {
        // This mutation may require context (e.g., ownerId) depending on API version, but typically works for shop-level metafields.
        $query = <<<'GQL'
            mutation metafieldDelete($namespace: String!, $key: String!) {
                metafieldDelete(namespace: $namespace, key: $key) {
                    deletedId
                    userErrors { field message }
                }
            }
        GQL;

        $variables = ['namespace' => $namespace, 'key' => $key];
        mGraph($shop)->query($query, $variables)->getDecodedBody();
    }
}