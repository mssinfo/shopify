<?php
namespace Msdev2\Shopify\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ShopifyEmail extends Mailable
{
    use Queueable, SerializesModels;

    public $type;
    public $content;

    public function __construct($type, $content)
    {
        $this->type = $type;
        $this->content = $content;
    }

    public function build()
    {
        $subject = $this->getSubject();
        return $this->subject($subject)
                    ->view('msdev2::emails.shopify.generic')
                    ->with(['content' => $this->content]);
    }

    private function getSubject()
    {
        $subjects = [
            'install' => "🎉 Welcome to ".config("app.name")." App!",
            'uninstall' => "💔 We’d Love to Have You Back!",
            'review_request' => "⭐ Can You Review Our App?",
            'app_improvement' => "📢 Help Us Improve Our App!"
        ];
        
        return $subjects[$this->type] ?? "Thank You for Using Our App ".config("app.name");
    }
}
