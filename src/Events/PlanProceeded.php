<?php
namespace Msdev2\Shopify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanProceeded
{
    use Dispatchable, SerializesModels;

    public $shop;
    public $plan;

    public function __construct($shop, $plan = null)
    {
        $this->shop = $shop;
        $this->plan = $plan;
    }
}
