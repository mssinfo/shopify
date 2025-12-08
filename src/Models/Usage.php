<?php

namespace Msdev2\Shopify\Models;

class Usage extends Model
{
    protected $table = 'usages';

    protected $fillable = [
        'shop_id',
        'type',
        'quantity',
        'cost',
        'reference_id',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function shop()
    {
        return $this->belongsTo(Shop::class);
    }
}
