<?php
namespace Msdev2\Shopify\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model as LAravelModel;

class Model extends LAravelModel {

    use HasFactory;
    public $metaField = false;
    public $singleMetaField = false;
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
            $tableData = $model::where('shop_id',$model->shop_id)->get();
            mRest($shop)->post('metafields.json',[
                "metafield"=>[
                    "namespace"=>config('msdev2.app_id'),
                    "key"=>$model->getTable(),
                    "value"=>json_encode($tableData),
                    "type"=>"json"
                ]
            ]);
        }
        if($model->singleMetaField && $model->shop_id){
            $shop = mShop($model->shop_id);
            $tableData = $model::find($model->id);
            mRest($shop)->post('metafields.json',[
                "metafield"=>[
                    "namespace"=>config('msdev2.app_id'),
                    "key"=>$model->getTable()."_".$model->id,
                    "value"=>json_encode($tableData),
                    "type"=>"json"
                ]
            ]);
        }
    }
}
