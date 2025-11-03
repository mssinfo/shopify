<?php
namespace Msdev2\Shopify\Http\Controllers;

use Msdev2\Shopify\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Msdev2\Shopify\Models\Charge;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Ticket;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class AgentController extends BaseController{
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

    /**
     * Search shops for autocomplete (q parameter)
     */
    public function shopSearch(Request $request)
    {
        $q = $request->query('q', '');
        $items = [];
        if (!empty($q)) {
            $items = Shop::where('shop', 'like', "%{$q}%")
                ->orWhere('domain', 'like', "%{$q}%")
                ->select('id', 'shop', 'domain')
                ->limit(10)
                ->get();
        }
        return response()->json($items);
    }

    /**
     * Return shop detail by id
     */
    public function shopDetail($id)
    {
        $shop = Shop::with('activeCharge')->find($id);
        if (!$shop) {
            return response()->json(['error' => 'Not found'], 404);
        }
        return response()->json($shop);
    }

    /**
     * Show full shop detail page for agents.
     */
    public function shopView($id)
    {
        $shop = Shop::with(['charges' => function($q){ $q->orderBy('activated_on', 'desc'); }, 'metadata'])->find($id);
        if (!$shop) {
            // Return a 404 so callers and middleware get appropriate behavior
            abort(404, 'Shop not found');
        }

        $charges = $shop->charges()->orderBy('activated_on','desc')->get();
        $metadata = $shop->metadata()->get();
        // Pass shop under a different name to avoid collision with global view composer 'shop'
        return view('msdev2::agent.shop_detail', [
            'shopDetail' => $shop,
            'charges' => $charges,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Generate a one-time token and redirect to app root including token.
     */
    public function directLogin($id)
    {
        $shop = Shop::find($id);
        if (!$shop) {
            return redirect()->route('msdev2.agent.dashboard')->withErrors(['shop' => 'Shop not found']);
        }
        $token = Str::random(48);
        // store token mapping to shop identifier for a short time (5 minutes)
        Cache::put('agent_direct_'.$token, ['shop' => $shop->shop], 300);
        // Allow embedded iframe redirect if an agent is currently logged in
        $redirectIframe = auth()->check() ? 'false' : 'true';
        $target = config('app.url') . '?shop=' . urlencode($shop->shop) . '&redirect_to_iframe=' . $redirectIframe . '&token=' . $token;
        return redirect($target);
    }
    public function shopifyGraph(Request $request)
    {
        $shop = mShop();
        $graph = mGraph($shop);
        $query = $request->input('query', null);
        $variables = $request->input('variables', []);
        if (!$query) {
            return mErrorResponse([], 'No query provided', 400);
        }
        try {
            $response = $graph->query($query, $variables);
            return $response->getDecodedBody();
        } catch (\Exception $e) {
            return mErrorResponse([], 'Error executing query: ' . $e->getMessage(), 500);
        }
    }
}
