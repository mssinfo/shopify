<?php
namespace Msdev2\Shopify\Console\Commands;

use Illuminate\Console\Command;
use Msdev2\Shopify\Jobs\SyncShopifyInstallationsJob;

class SyncShopifyInstallations extends Command
{
    protected $signature = 'shopify:sync
        {--dispatch : Dispatch to queue instead of running inline}
        {--shop= : Run for a specific shop (domain or id)}
        {--check : Dry run — do not modify DB, save per-shop status logs}';

    protected $description = 'Sync Shopify app installations for shops (mark uninstalled when not present in Shopify).';

    public function handle()
    {
        $shopOpt = $this->option('shop');
        $check = $this->option('check') ? true : false;

        if($this->option('dispatch')){
            SyncShopifyInstallationsJob::dispatch($shopOpt, $check);
            $this->info('SyncShopifyInstallationsJob dispatched to queue.');
            return 0;
        }

        // Run synchronously
        $job = new SyncShopifyInstallationsJob($shopOpt, $check);
        $job->handle();
        $this->info('SyncShopifyInstallationsJob completed.');
        if($check){
            $this->info('Check logs written to storage/logs/{shop}/sync-check.log for each shop.');
        }
        return 0;
    }
}
