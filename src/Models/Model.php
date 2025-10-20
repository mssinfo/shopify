<?php
namespace Msdev2\Shopify\Models;

use Msdev2\Shopify\Traits\HasMetafields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as LAravelModel;
use Illuminate\Support\Facades\Log;

class Model extends LAravelModel {

    use HasFactory, HasMetafields;
    public $metaField = false;
    public $singleMetaField = false;
    public $metaPublic = false;
    protected static function boot()
    {
        parent::boot();

        static::created(function ($model) {
            $model->syncMeta($model,'create');
        });

        static::updated(function ($model) {
            $model->syncMeta($model,'update');
        });

        static::deleting(function ($model) {
            $model->syncMeta($model,'delete');
        });
    }

    /**
     * Automatically sync metafields for any model
     */
    protected function syncMeta($model, string $action): void
    {
        try {
            if (!$model->shop_id || (!$model->metaField && !$model->singleMetaField)) {
                Log::warning(($model->metaField?'yes ':'no ').'-'.($model->singleMetaField?'yes ':'no ').':($model->metaField) No shop_id found for metafield sync', ['model' => static::class]);
                return;
            }

            // Special handling: if the underlying model being synced is the Metadata model
            // we want to publish a single public app metafield key named 'settings' that
            // contains only keys that start with 'frontend_' as a flat JSON object.
            // This avoids creating many individual metafields for each metadata row.
            // Use a runtime-safe check to detect the project's Metadata model
            if (class_basename($model) === 'Metadata') {
                // Use the model's relationship to the shop when available
                $shop = $model->shop ?? mShop($model->shop_id);

                // Collect all metadata rows for this shop that begin with 'frontend_'
                $rows = $shop->metadata()->get();
                $frontend = [];
                foreach ($rows as $row) {
                    if (is_string($row->key) && str_starts_with($row->key, 'frontend_')) {
                        $val = $row->value;
                        $decoded = json_decode($val, true);
                        $frontend[$row->key] = json_last_error() === JSON_ERROR_NONE ? $decoded : $val;
                    }
                }

                if ($action === 'delete') {
                    // If this delete removed only a single metadata row, ensure we only remove
                    // that key from the published settings object. The $frontend object above
                    // already reflects current DB state (delete fired after DB delete), so
                    // simply write the updated object or delete the metafield if empty.
                    if (empty($frontend)) {
                        $shop->deleteMetaField('settings', config('msdev2.app_id'), false);
                    } else {
                        $shop->setMetaField([
                            'namespace' => config('msdev2.app_id'),
                            'key'       => 'settings',
                            'value'     => json_encode($frontend),
                            'type'      => 'json',
                        ], false);
                    }
                    return;
                }

                // For create/update, write the settings metafield with all frontend_ keys
                $shop->setMetaField([
                    'namespace' => config('msdev2.app_id'),
                    'key'       => 'settings',
                    'value'     => json_encode($frontend),
                    'type'      => 'json',
                ], false); // make public
                return;
            }

            // If a Charge changed, update the published plan_feature metafield so
            // storefront blocks can read the current plan features.
            if (class_basename($model) === 'Charge') {
                $shop = $model->shop ?? mShop($model->shop_id);
                try {
                    $plan = $shop->plan();
                    $features = $plan['feature'] ?? $plan;
                    $shop->setMetaField([
                        'namespace' => config('msdev2.app_id'),
                        'key'       => 'plan_feature',
                        'value'     => json_encode($features),
                        'type'      => 'json',
                    ], false);
                } catch (\Throwable $e) {
                    Log::error('Failed to write plan_feature metafield: '.$e->getMessage());
                }
                return;
            }

            $shop = $model->shop ?? mShop($model->shop_id);
            if ($model->singleMetaField) {
                $key = "{$model->getTable()}_{$model->id}";
                if ($action === 'delete') {
                    $shop->deleteMetaField($key, config('msdev2.app_id'));
                } else {
                    $data = $model->fresh();
                    $value = json_encode($data);
                    $shop->setMetaField([
                        'namespace' => config('msdev2.app_id'),
                        'key'       => $key,
                        'value'     => $value,
                        'type'      => 'json',
                    ], !$model->metaPublic);
                }
            }else{
                $data = $model::where('shop_id', $model->shop_id)->get();
                $key = $model->getTable();
                $value = json_encode($data);
                $shop->setMetaField([
                    'namespace' => config('msdev2.app_id'),
                    'key'       => $key,
                    'value'     => $value,
                    'type'      => 'json',
                ], !$model->metaPublic);
            }
        } catch (\Throwable $e) {
            Log::error("Failed metafield sync [{$action}]: {$e->getMessage()}", [
                'model' => get_class($model),
                'id'    => $model->id ?? null,
            ]);
        }
    }

}
