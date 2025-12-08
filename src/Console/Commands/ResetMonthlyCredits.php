<?php

namespace Msdev2\Shopify\Console\Commands;

use Illuminate\Console\Command;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Services\CreditService;

class ResetMonthlyCredits extends Command
{
    protected $signature = 'credits:reset-monthly';
    protected $description = 'Reset monthly free credits for all shops';

    public function handle()
    {
        $this->info('Resetting monthly free credits...');

        Shop::chunk(100, function ($shops) {
            foreach ($shops as $shop) {
                CreditService::checkMonthlyReset($shop);
            }
        });

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
