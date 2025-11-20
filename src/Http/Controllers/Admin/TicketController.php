<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Msdev2\Shopify\Http\Controllers\BaseController;
use Msdev2\Shopify\Models\Ticket;
use Msdev2\Shopify\Mail\TicketReplyMail; // Ensure these Mailable classes exist
use Msdev2\Shopify\Mail\TicketClosedMail;

class TicketController extends BaseController
{
    public function index()
    {
        // Eager load shop to prevent N+1
        $tickets = Ticket::with('shop')->orderBy('status', 'asc')->latest()->paginate(20);
        return view('msdev2::admin.tickets.index', compact('tickets'));
    }

    public function show($id)
    {
        $ticket = Ticket::with('shop')->findOrFail($id);
        
        // Decode files if they are JSON, otherwise assume comma-separated
        $files = [];
        if (!empty($ticket->files)) {
            $json = json_decode($ticket->files, true);
            $files = (json_last_error() === JSON_ERROR_NONE) ? $json : explode(',', $ticket->files);
        }

        return view('msdev2::admin.tickets.show', compact('ticket', 'files'));
    }

    public function reply(Request $request, $id)
    {
        $request->validate(['message' => 'required|string']);
        $ticket = Ticket::findOrFail($id);
        
        // 1. Send Email
        if (!empty($ticket->email)) {
            try {
                Mail::to($ticket->email)->send(new TicketReplyMail($ticket, $request->message));
            } catch (\Exception $e) {
                return back()->with('error', 'Error sending email: ' . $e->getMessage());
            }
        }

        // 2. Update Status to In Progress
        if ($ticket->status == 0) {
            $ticket->status = 1;
            $ticket->save();
        }

        return back()->with('success', 'Reply sent successfully.');
    }

    public function updateStatus(Request $request, $id)
    {
        $ticket = Ticket::findOrFail($id);
        $ticket->status = $request->status;
        $ticket->save();

        // Send closed notification
        if ($request->status == 2 && !empty($ticket->email)) {
            try {
                Mail::to($ticket->email)->send(new TicketClosedMail($ticket));
            } catch (\Exception $e) {}
        }

        return back()->with('success', 'Status updated.');
    }
}