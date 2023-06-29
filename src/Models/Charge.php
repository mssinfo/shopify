<?php

namespace Msdev2\Shopify\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Charge extends Model
{

    protected $guarded = [];
    public $timestamps = true;
    /**
     * Get the shop that owns the Plan
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }

}
