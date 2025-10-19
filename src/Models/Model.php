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
