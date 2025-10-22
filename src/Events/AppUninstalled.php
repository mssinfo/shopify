<?php
namespace Msdev2\Shopify\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AppUninstalled
{
    use Dispatchable, SerializesModels;

    public $shopName;

    public function __construct($shopName)
    {
        $this->shopName = $shopName;
    }
}
