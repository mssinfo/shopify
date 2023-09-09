<?php
namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;

class TicketController extends Controller {
    public function index(Request $request)
    {
        return view("msdev2::ticket");
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
            return mSuccessResponse($data);
        }
        return mErrorResponse();
    }
}
?>
