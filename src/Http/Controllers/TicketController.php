<?php
namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use Msdev2\Shopify\Mail\TicketAdminMail;
use Msdev2\Shopify\Mail\TicketUserEmail;
use Illuminate\Support\Facades\Storage;
use Msdev2\Shopify\Models\Ticket;
use Illuminate\Support\Facades\Mail;

class TicketController extends BaseController {

    public function index(Request $request)
    {
        return view("msdev2::ticket");
    }
    public function tickets(Request $request)
    {
        $tickets = Ticket::where("status","0")->latest()->paginate("20");
        return view("msdev2::agent.ticket",compact('tickets'));
    }
    public function ticketsResolve($id){
        $ticket = Ticket::find($id);
        $ticket->status = 1;
        $ticket->save();
        return back()->with('success',['msg'=>'ticket resolve successfully']);
    }
    public function store(Request $request)
    {
        $request->validate([
            'email' => 'required',
            'subject' => 'required|max:150',
            'category' => 'required',
            'detail' => 'required|max:2000'
        ]);
        $shop = mShop();
        $filelist = [];
        if(!empty($request->input("files"))){
            foreach($request->input("files") as $file){
                list($mime, $base64Data) = explode(';', $file['source']);
                $base64Data = explode(',', $base64Data);
                // Get the file extension from the mime type
                $extension = $file["extension"];
                $decodedImage = base64_decode($base64Data[1]);
                // Generate a unique filename with the extracted extension
                $filename = '/' . mShopName() . '/' . uniqid() . '.' . $extension;
                $filelist[] = $filename;
                // Define the storage path
                $storagePath = ('public'.$filename); // You can adjust the storage path as needed
                // Save the image to the storage path
                Storage::put($storagePath, $decodedImage);
            }
        }
        $data = $shop->tickets()->create([
            'email'=>$request->email,
            'subject'=>$request->subject,
            'category'=>$request->category,
            'detail'=>$request->detail,
            'password'=>$request->password,
            'priority'=>$request->priority,
            'ip_address'=>$request->ip(),
            'files'=>implode(",",$filelist),
        ]);
        if($data){
            $input = $request->all();
            $email = config('msdev2.contact_email','mragankshekhatr@gmail.com');
            if(!empty($email)) {
                Mail::to(config('msdev2.contact_email','mragankshekhatr@gmail.com'))->queue(new TicketAdminMail($input, $shop, "New ticket Created"));
            }
            Mail::to($request->email)->queue(new TicketUserEmail("Acknowledgement of Your Ticket Creation"));
            
            return mSuccessResponse($data);
        }
        return mErrorResponse();
    }
}
?>
