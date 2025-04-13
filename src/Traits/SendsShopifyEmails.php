<?php
namespace Msdev2\Shopify\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Msdev2\Shopify\Mail\ShopifyEmail;
use Msdev2\Shopify\Models\Shop;
use Illuminate\Support\Facades\Log;

trait SendsShopifyEmails
{
    public static function sendShopifyEmail(string $type,Shop $shop)
    {
        $emailContent = self::generateEmailContent($type, $shop->detail["name"], $shop);
        Log::info("send mail",["to",$shop->detail["email"], "type"=>$type]);
        Mail::to($shop->detail["email"])->send(new ShopifyEmail($type, $emailContent));
    }

    private static function generateEmailContent($type, $name, $shop)
    {
        $messages = [
            'install' => [
                'title' => "<h2 style='margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-size: 28px; font-weight: 600; color: #28a745; text-align: center;'>🎉 Welcome to <strong>".config('app.name')."</strong>!</h2>",
                'content' => "
                    <p style='font-size: 16px; color: #333; text-align: center;'>
                        Thank you for installing <strong>".config('app.name')."</strong>! We're excited to have you on board. 
                        <br><br>
                        As a special thank-you gift, we'd love for you to leave us a <strong> ⭐⭐⭐⭐⭐ 5-star rating</strong>! You'll unlock an exclusive 
                        <span style='color: #ff5722; font-weight: bold;'>5% discount</span> on our plans. ⭐
                    </p>
                ",
                'button' => "
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='".config('msdev2.shopify_app_url')."'
                        target='_blank' class='v-button v-size-width v-font-size' style='box-sizing: border-box; display: inline-block; text-decoration: none; text-size-adjust: none; text-align: center; background-color: #28a745; color: white; border-radius: 4px; width: 31%; max-width: 100%; word-break: break-word; overflow-wrap: break-word; font-size: 14px; line-height: inherit;'>
                            <span class='v-padding' style='display:block;padding:10px 20px;line-height:120%;'>
                                <span style='font-size: 14px; line-height: 16.8px;'>
                                    Leave a Review ⭐
                                </span>
                            </span>    
                        </a>
                    </div>
                "
            ],
        
            'uninstall' => [
                'title' => "<h2 style='margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-size: 28px; font-weight: 600;color: #e74c3c; text-align: center;'>😔 We’re sad to see you go...</h2>",
                'content' => "
                    <p style='font-size: 16px; color: #333; text-align: center;'>
                        We noticed that you've uninstalled <strong>".config('app.name')."</strong> from <strong>$shop->shop</strong>. We're genuinely grateful for the time you've spent with us. 
                        <br><br>
                        We'd love to hear about your experience so we can make improvements. Your feedback means the world to us! 
                        If you ever decide to return, we'll be waiting with a <strong>special discount</strong> just for you. 
                    </p>
                ",
                'button' => "
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='".route('msdev2.shopify.install', ['shop' => $shop->shop])."'
                        target='_blank' class='v-button v-size-width v-font-size' style='box-sizing: border-box; display: inline-block; text-decoration: none; text-size-adjust: none; text-align: center; background-color: #007bff; color: white; border-radius: 4px; width: 31%; max-width: 100%; word-break: break-word; overflow-wrap: break-word; font-size: 14px; line-height: inherit;'>
                            <span class='v-padding' style='display:block;padding:10px 20px;line-height:120%;'>
                                <span style='font-size: 14px; line-height: 16.8px;'>
                                    Reinstall Now 🚀
                                </span>
                            </span>    
                        </a>
                    </div>
                "
            ],
        
            'review_request' => [
                'title' => "<h2 style='margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-size: 28px; font-weight: 600;color: #f39c12; text-align: center;'>⭐ Your opinion matters!</h2>",
                'content' => "
                    <p style='font-size: 16px; color: #333; text-align: center;'>
                        Hi there! We hope you're enjoying <strong>".config('app.name')."</strong> on <strong>$shop->shop</strong>. Your feedback is invaluable and helps us improve.
                        <br><br>
                        We would be grateful if you could take a moment to leave us a quick review. Your review only takes a minute, but it means the world to us! ✨
                    </p>
                    <p style='font-size: 16px; color: #f39c12; text-align: center;'>
                        Please rate us: ⭐⭐⭐⭐⭐
                    </p>
                ",
                'button' => "
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='".config('msdev2.shopify_app_url')."'
                        target='_blank' class='v-button v-size-width v-font-size' style='box-sizing: border-box; display: inline-block; text-decoration: none; text-size-adjust: none; text-align: center; background-color: #f39c12; color: white; border-radius: 4px; width: 31%; max-width: 100%; word-break: break-word; overflow-wrap: break-word; font-size: 14px; line-height: inherit;'>
                            <span class='v-padding' style='display:block;padding:10px 20px;line-height:120%;'>
                                <span style='font-size: 14px; line-height: 16.8px;'>
                                    Write a Review ✨
                                </span>
                            </span>    
                        </a>
                    </div>
                "
            ],
        
            'app_improvement' => [
                'title' => "<h2 style='margin: 0px; line-height: 140%; text-align: center; word-wrap: break-word; font-size: 28px; font-weight: 600;color: #17a2b8; text-align: center;'>📢 Help us improve our app!</h2>",
                'content' => "
                    <p style='font-size: 16px; color: #333; text-align: center;'>
                        We’re constantly working to improve <strong>$shop->shop</strong>'s app experience. Your ideas and feedback help us determine what we develop next!
                        <br><br>
                        Is there anything you'd like to see added or improved? We’re all ears and look forward to hearing from you!
                    </p>
                    <p style='font-size: 16px; color: #17a2b8; text-align: center;'>
                        Let us know how we can make your experience even better. 💬
                    </p>
                ",
                'button' => "
                    <div style='text-align: center; margin-top: 20px;'>
                        <a href='".route('msdev2.shopify.install', ['shop' => $shop->shop])."'
                        target='_blank' class='v-button v-size-width v-font-size' style='box-sizing: border-box; display: inline-block; text-decoration: none; text-size-adjust: none; text-align: center; background-color: #17a2b8; color: white; border-radius: 4px; width: 31%; max-width: 100%; word-break: break-word; overflow-wrap: break-word; font-size: 14px; line-height: inherit;'>
                            <span class='v-padding' style='display:block;padding:10px 20px;line-height:120%;'>
                                <span style='font-size: 14px; line-height: 16.8px;'>
                                    Give Feedback 💡
                                </span>
                            </span>    
                        </a>
                    </div>
                "
            ]
        ];
        
        
    
        return $messages[$type];

    }
}
