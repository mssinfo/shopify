<?php

namespace Msdev2\Shopify\Models;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class Ticket extends Model
{
    protected $guarded = [];
    public $timestamps = true;
    /**
     * Get the Shop that owno the Ticket.php
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function Shop(): BelongsTo
    {
        return $this->belongsTo(Shop::class);
    }
}
