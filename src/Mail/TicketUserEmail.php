<?php
namespace Msdev2\Shopify\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketUserEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $heading;
    public $ticket;

    public function __construct($heading, $ticket)
    {
        $this->ticket = $ticket;
        $this->heading = $heading;
    }

    public function build()
    {
        return $this->subject($this->heading)->view('msdev2::emails.ticket.user')->with([
            'heading' => $this->heading,
            'ticket' => $this->ticket,
        ]);
    }
}