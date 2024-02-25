<?php
namespace Msdev2\Shopify\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TicketUserEmail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $data;
    public $shop;
    public $heading;

    public function __construct($data,$shop,$heading)
    {
        $this->data = $data;
        $this->shop = $shop;
        $this->heading = $heading;
    }

    public function build()
    {
        return $this->view('msdev2::emails.ticket.user')->with([
            'name' => $this->data->name,
            'shop' => $this->shop,
            'heading' => $this->heading,
        ]);
    }
}