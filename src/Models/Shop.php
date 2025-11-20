<?php

namespace Msdev2\Shopify\Models;

use Exception;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Shop extends Model
{
    use \App\Models\Shop, SoftDeletes;
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
        return $this->meta($key, $value);
    }
    public function getMetaData($key){
        return $this->meta($key);
    }
    /**
     * Get or set the meta data.
     *
     * @param  string|array  $key
     * @param  mixed|null  $value
     * @return mixed
     */
    public function meta($key, $value = null)
    {
        // If value is provided, we're setting the meta data
        if ($value !== null) {
            $newVal = $value;
            // Check if the value is an array, in which case we store it as JSON
            if (is_array($value)) {
                // Encode array to JSON and save
                $newVal = json_encode($newVal);
            }

            // Update or create metadata in the database
            $this->metadata()->updateOrCreate(
                ['key' => $key],
                ['value' => $newVal]
            );
            return $value;
        }

        // If no value is provided, we're getting the meta data
        $value = $this->metadata()->where('key', $key)->first();

        // If value exists and it's stored as JSON, decode it
        if ($value) {
            $decodedValue = json_decode($value->value, true);
            return json_last_error() === JSON_ERROR_NONE ? $decodedValue : $value->value;
        }

        return null; // Return null if no metadata exists for the given key
    }
    public function deleteMeta($key){
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
    public function generateLoginUrl()
    {
        $token = Str::random(48);
        
        // Store token for 5 minutes
        Cache::put('agent_direct_'.$token, ['shop' => $this->shop], 300);
        
        // Check if agent is logged in to decide on iframe redirect
        $redirectIframe = Auth::check() ? 'false' : 'true';
        
        return config('app.url') . '?shop=' . urlencode($this->shop) . '&redirect_to_iframe=' . $redirectIframe . '&token=' . $token;
    }
    public function getTotalEarningsAttribute()
    {
        return $this->charges()
            ->whereIn('status', ['active', 'one_time']) // Adjust based on your logic
            ->sum('price');
    }
}
