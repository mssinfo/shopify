<?php
namespace Msdev2\Shopify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PlanPurchaseCompleted
{
    use Dispatchable, SerializesModels;

    public $shop;
    public $charge;
    public $name;


    public function __construct($shop, $charge = null, $name = null)
    {
        $this->shop = $shop;
        $this->charge = $charge;
        $this->name = $name;
    }
}
