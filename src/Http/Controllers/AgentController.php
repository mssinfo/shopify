<?php
namespace Msdev2\Shopify\Http\Controllers;

use Msdev2\Shopify\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Msdev2\Shopify\Models\Charge;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Ticket;

class AgentController extends Controller{
    public function login() {
        return view("msdev2::agent.login");
    }
    /**
     * Authenticate the user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function authenticate(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');
        $check = Auth::attempt($credentials);
        if($check)
        {
            // dd($check,Auth::user());
            $request->session()->regenerate();
            return redirect()->route('msdev2.agent.dashboard')->withSuccess('You have successfully logged in!');
        }
        // $pass = \Illuminate\Support\Facades\Hash::make("123456");
        // \Msdev2\Shopify\Models\User::create([ 'name'=> "admin", 'first_name'=> "admin", 'email'=> "admin@codevibez.com",'password'=> $pass, 'token'=> rand(99999999,999999994)]);
        return back()->withErrors([
            'email' => 'Your provided credentials do not match in our records.',
        ])->onlyInput('email');

    }
     /**
     * Display a dashboard to authenticated users.
     *
     * @return \Illuminate\Http\Response
     */
    public function dashboard()
    {
        if(Auth::check())
        {
            $total = [
                "shop" => Shop::count(),
                "ticket" => [
                    "resolve" => Ticket::where("status",1)->count(),
                    "pending" => Ticket::where("status",0)->count()
                ],
            ];
            $changes = Charge::select('name', DB::raw('COUNT(*) as count'))->where("status","active")->groupBy('name')->get();
            
            return view('msdev2::agent.dashboard',compact('total','changes'));
        }

        return redirect()->route('msdev2.agent.login')->withErrors([
            'email' => 'Please login to access the dashboard.',
        ])->onlyInput('email');
    }

    /**
     * Log out the user from application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('msdev2.agent.login')
            ->withSuccess('You have logged out successfully!');;
    }
}
