<?php

namespace Msdev2\Shopify\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Metadata extends Model
{
    protected $guarded = [];
    public $timestamps = true;
    public $metaField = true;
    /**
     * Get the shop that owns the Metadata
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
