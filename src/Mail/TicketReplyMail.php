<?php
namespace Msdev2\Shopify\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Msdev2\Shopify\Models\Ticket;

class TicketReplyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;
    public $replyMessage;

    public function __construct(Ticket $ticket, $replyMessage)
    {
        $this->ticket = $ticket;
        $this->replyMessage = $replyMessage;
    }

    public function build()
    {
        return $this->subject("Re: [Ticket #{$this->ticket->id}] {$this->ticket->subject}")
                    ->view('msdev2::emails.ticket.reply');
    }
}