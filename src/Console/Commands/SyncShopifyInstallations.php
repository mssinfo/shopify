<?php

namespace Msdev2\Shopify\Console\Commands;

use Illuminate\Console\Command;
use Msdev2\Shopify\Jobs\SyncShopifyInstallationsJob;

class SyncShopifyInstallations extends Command
{
    protected $signature = 'shopify:sync
        {--queue}
        {--shop=}
        {--check}';

    protected $description = 'Authoritative sync of all Shopify installations';

    public function handle()
    {
        if ($this->option('queue')) {
            SyncShopifyInstallationsJob::dispatch(
                $this->option('shop'),
                $this->option('check')
            );
            $this->info('Sync dispatched to queue');
            return 0;
        }

        (new SyncShopifyInstallationsJob(
            $this->option('shop'),
            $this->option('check')
        ))->handle();

        $this->info('Sync completed');
        return 0;
    }
}
