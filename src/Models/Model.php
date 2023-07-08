<?php
namespace Msdev2\Shopify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as LAravelModel;

class Model extends LAravelModel {

    use HasFactory;
    public $metaField = false;
    protected static function boot() {
        parent::boot();
        static::created(function($model){
            self::updateMeta($model,'created');
        });
        static::updated(function($model){
            self::updateMeta($model,'update');
        });
        // static::deleting(function($model) {

        // });
    }
    private static function updateMeta($model,$type): void
    {
        if($model->metaField && $model->shop_id){
            $shop = mShop($model->shop_id);
            mRest($shop)->post('metafields.json',[
                "metafield"=>[
                    "namespace"=>config('msdev2.app_id'),
                    "key"=>$model->getTable(),
                    "value"=>json_encode($model),
                    "type"=>"json"
                ]
            ]);
        }
    }
}
