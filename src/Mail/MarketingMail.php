<?php
namespace Msdev2\Shopify\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class MarketingMail extends Mailable
{
    use Queueable, SerializesModels;

    public $subjectLine;
    public $content;
    public $shopInfo;

    public function __construct($subject, $content, $shopInfo)
    {
        $this->subjectLine = $subject;
        $this->content = $content;
        $this->shopInfo = $shopInfo;
    }

    public function build()
    {
        return $this->subject($this->subjectLine)
                    ->view('msdev2::emails.marketing', [
                        'subjectLine' => $this->subjectLine,
                        'content' => $this->content,
                        'bannerImage' => null, // Optionally add a banner image
                        'shopInfo' => $this->shopInfo,
                    ]);
    }
}