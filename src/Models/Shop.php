<?php

namespace Msdev2\Shopify\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class Shop extends Model
{
    use \App\Models\Shop;
    use SoftDeletes;
    protected $guarded = [];
    public $timestamps = true;

    protected $casts = [
        'detail' => 'array',
    ];
    /**
     * Get all of the tickets for the Shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }
    /**
     * Get all of the metadata for the Shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function metadata(): HasMany
    {
        return $this->hasMany(Metadata::class);
    }
    /**
     * Get all of the charges for the Shop
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function charges(): HasMany
    {
        return $this->hasMany(Charge::class);
    }
    public function activeCharge(): HasOne {
        return $this->hasOne(Charge::class)->where('status','active')->whereNull('cancelled_on');
    }
    public function plan($name = null) {
        $name = $name ?? ($this->activeCharge->name ?? null);

        if($name){
            $key = $name;
            if(!is_numeric($name)){
                $key = array_search($name, array_column(config('msdev2.plan'), 'chargeName'));
            }
            return config('msdev2.plan')[$key] ?? [];
        }
        return [];
    }
    public function appUsedDay(): int {
        if($this->charges()){
            $firstTTimeCharge = $this->charges()->first();
            if($firstTTimeCharge){
                $date = \Carbon\Carbon::parse($firstTTimeCharge->activated_on);
                $now = \Carbon\Carbon::now();
                return $date->diffInDays($now);
            }
        }
        return 0;
    }
    public function setMetaData($key, $value) {
        return $this->metadata()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }
    public function getMetaData($key){
        $value = $this->metadata()->where('key', $key)->first();
        if($value){
            return $value->value;
        }
        return null;
    }
    public function deleteMetaData($key){
        return $this->metadata()->where('key', $key)->delete();
    }
    public function isTestStore() : bool {
        if(app()->environment() === 'production') return false;
        $domain = explode('.',$this->shop);
        $testStore = explode(',',config('msdev2.test_stores'));
        if(in_array($domain[0],$testStore)){
            return true;
        }
        return false;
    }
    public static function log($message, $ary = [], $logLevel = 'info', $channel = 'local')
    {
        mLog($message, $ary, $logLevel, $channel);
    }
}
