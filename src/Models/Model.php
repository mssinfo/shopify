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
                // For create/update, write the settings metafield with all frontend_ keys
                $shop->setMetaField([
                    'namespace' => config('msdev2.app_id'),
                    'key'       => 'settings',
                    'value'     => json_encode($frontend),
                    'type'      => 'json',
                ], !$model->metaPublic); // make public
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
                'class' => class_basename($model),
                'id'    => $model->id ?? null,
            ]);
        }
    }

}
