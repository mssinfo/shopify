<?php

namespace Msdev2\Shopify\Traits;

use Illuminate\Support\Facades\Log;

trait HasMetafields
{
    public function setMetaField(array $metaField, bool $isPrivateMeta = true): void
    {
        $shop = (is_string($this->shop) ? mShop($this->shop) : $this->shop) ?? mShop();
        if (!$shop) {
            return;
        }

        if (empty($metaField['namespace'])) {
            $metaField['namespace'] = config('msdev2.app_id');
        }

        if (empty($metaField['type'])) {
            $metaField['type'] = 'JSON';
        }

        if ($isPrivateMeta) {
            self::setPrivateMetaField($shop, $metaField);
        } else {
            self::setPublicMetaField($shop, $metaField);
        }
    }

    public function deleteMetaField(string $key, string $namespace = '', bool $isPrivateMeta = true): void
    {
        $shop = method_exists($this, 'shop') ? $this->shop : $this;
        if (!$shop) return;

        $namespace = $namespace !== '' ? $namespace : config('msdev2.app_id');

        if ($isPrivateMeta) {
            self::deletePrivateMetaField($shop, $namespace, $key);
        } else {
            self::deletePublicMetaField($shop, $namespace, $key);
        }
    }

    // ---------------- PRIVATE META ----------------

    private static function setPrivateMetaField($shop, array $metaField): void
    {
        $ownerId = self::getAppInstallationId($shop);
        if (!$ownerId) {
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
        $data = mGraph($shop)->query(["query" => $query, "variables" => $variables])->getDecodedBody();
        Log::debug("setPrivateMetaField set response: ". json_encode($data), $variables);
    }

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
        mGraph($shop)->query($query, $variables)->getDecodedBody();
    }

    private static function getAppInstallationId($shop): ?string
    {
        $cachedId = $shop->meta('_current_app_installation_id');
        if ($cachedId) return $cachedId;

        $query = <<<'GQL'
            query { currentAppInstallation { id } }
        GQL;

        $response = mGraph($shop)->query($query)->getDecodedBody();
        $id = $response['data']['currentAppInstallation']['id'] ?? null;

        if ($id) {
            $shop->meta('_current_app_installation_id', $id);
        }

        return $id;
    }

    // ---------------- PUBLIC META ----------------

    private static function setPublicMetaField($shop, array $metaField): void
    {
        
        $query = <<<'GQL'
            mutation metafieldsSet($input: [MetafieldsSetInput!]!) {
                metafieldsSet(input: $input) {
                    metafields { id namespace key }
                    userErrors { field message }
                }
            }
        GQL;

        // Shop-level metafields use `ownerId` = shop GID
        $shopGid = "gid://shopify/Shop/{$shop->shop_id}";
        $metaField['ownerId'] = $shopGid;

        $variables = ['input' => [$metaField]];
        $data = mGraph($shop)->query($query, $variables)->getDecodedBody();
        Log::debug("setPublicMetaField set response: ". json_encode($data), $variables);
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
        mGraph($shop)->query($query, $variables)->getDecodedBody();
    }
}
