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
                $installedAt = strtotime($shop->created_at);

                // Get the timestamp of the last sent email for both review request and app improvement
                $lastReviewRequestSent = $shop->meta('review_request_sent_at');
                $lastAppImprovementSent = $shop->meta('app_improvement_sent_at');

                // Calculate the time difference for each email type
                $timeSinceLastReview = $lastReviewRequestSent ? time() - strtotime($lastReviewRequestSent) : PHP_INT_MAX;
                $timeSinceLastAppImprovement = $lastAppImprovementSent ? time() - strtotime($lastAppImprovementSent) : PHP_INT_MAX;

                // Send review request email if 24 hours have passed since the last one
                if ($shop->meta('review_request_sent') != 1 && $timeSinceLastReview > 86400 && time() - $installedAt > 86400) { // 24 hours
                    BaseMailHandler::processEmail('review_request', $shop);
                    $shop->meta('review_request_sent', 1);
                    $shop->meta('review_request_sent_at', now()->toDateTimeString());
                    $this->info("Review request email sent to Shop ID: {$shop->id}");
                }

                // Send app improvement email only if 24 hours have passed since the review request email was sent
                if ($shop->meta('app_improvement_sent') != 1 && $timeSinceLastAppImprovement > 86400 && time() - $installedAt > 172800) { // 48 hours
                    BaseMailHandler::processEmail('app_improvement', $shop);
                    $shop->meta('app_improvement_sent', 1);
                    $shop->meta('app_improvement_sent_at', now()->toDateTimeString());
                    $this->info("App improvement email sent to Shop ID: {$shop->id}");
                }
            }
        }
        $this->info('Customer emails processed successfully.');
    }
}
