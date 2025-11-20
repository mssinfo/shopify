<?php
namespace Msdev2\Shopify\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Msdev2\Shopify\Models\Ticket;

class TicketClosedMail extends Mailable
{
    use Queueable, SerializesModels;

    public $ticket;

    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    public function build()
    {
        return $this->subject("[Resolved] Ticket #{$this->ticket->id} has been closed")
                    ->view('msdev2::emails.ticket.closed');
    }
}