<?php
namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class TicketController extends Controller {
    public function index(Request $request)
    {
        return view("msdev2::ticket");
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'data.email' => 'required',
            'data.subject' => 'required|max:150',
            'data.category' => 'required',
            'data.detail' => 'required|max:2000'
        ]);
        
    }
}
?>
