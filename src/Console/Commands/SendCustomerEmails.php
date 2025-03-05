<?php

namespace Msdev2\Shopify\Console\Commands;

use Illuminate\Console\Command;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Lib\BaseMailHandler;

class SendCustomerEmails extends Command
{
    protected $signature = 'shop:send-customer-emails';
    protected $description = 'Send emails to shop customers based on installation time';

    public function handle()
    {
        $this->info('Processing customer emails...');

        // Fetch all shops
        $shops = Shop::all();

        foreach ($shops as $shop) {
            if ($shop->uninstall != 1) {
                $installedAt = strtotime($shop->installed_at);

                // Send review request email
                if ($shop->meta('review_request_sent') != 1 && time() - $installedAt > 86400) { // 24 hours
                    BaseMailHandler::processEmail('review_request', $shop);
                    $shop->meta('review_request_sent', 1);
                    $this->info("Review request email sent to Shop ID: {$shop->id}");
                }

                // Send app improvement email
                if ($shop->meta('app_improvement_sent') != 1 && time() - $installedAt > 172800) { // 48 hours
                    BaseMailHandler::processEmail('app_improvement', $shop);
                    $shop->meta('app_improvement_sent', 1);
                    $this->info("App improvement email sent to Shop ID: {$shop->id}");
                }
            }
        }
        $this->info('Customer emails processed successfully.');
    }
}
