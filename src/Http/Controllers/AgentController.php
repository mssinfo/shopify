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
        $plan = $request->query('plan', null);
        $status = $request->query('status', null); // installed | uninstalled
        $limit = (int) $request->query('limit', 10);
        $page = (int) $request->query('page', 1);

        // If simple autocomplete (small limit and no page) return compact results
        $isAutocomplete = $limit <= 10 && !$request->has('page');

        // Build base query honoring status
        if ($status === 'uninstalled') {
            $query = Shop::onlyTrashed()->with('activeCharge');
        } else {
            $query = Shop::with('activeCharge');
            if ($status === 'installed') {
                $query->whereNull('deleted_at');
            }
        }

        if (!empty($q)) {
            $query->where(function($qb) use ($q){
                $qb->where('shop', 'like', "%{$q}%")
                   ->orWhere('domain', 'like', "%{$q}%")
                   ->orWhere('id', $q);
            });
        }
        if ($plan) {
            // filter by active charge name
            $query->whereHas('activeCharge', function($qb) use ($plan){
                $qb->where('name', $plan);
            });
        }

        if ($isAutocomplete) {
            $items = $query->select('id', 'shop', 'domain')->limit($limit)->get();
            return response()->json($items);
        }

        // Paginated / detailed listing for shops page
        $total = $query->count();
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $rows = $query->orderBy('created_at','desc')->skip($offset)->take($limit)->get();

        $items = $rows->map(function($s){
            $isUninstalled = ((int)($s->is_uninstalled ?? 0) === 1) || !empty($s->deleted_at);
            return [
                'id' => $s->id,
                'name' => $s->shop,
                'shop' => $s->shop,
                'domain' => $s->domain,
                'installed_at' => $s->created_at,
                // unify uninstalled flag from is_uninstalled or soft-deletes
                'uninstalled' => $isUninstalled,
                'uninstalled_at' => $s->uninstalled_at ?? null,
                'deleted_at' => $s->deleted_at ?? null,
                'is_uninstalled' => (int)($s->is_uninstalled ?? 0),
                'plan' => $s->activeCharge->name ?? null,
            ];
        });

        return response()->json([
            'items' => $items,
            'total' => $total,
            'per_page' => $limit,
            'current_page' => $page,
            'last_page' => $limit ? intval(ceil($total / $limit)) : 1,
        ]);
    }

    /**
     * Render shops list page for agents
     */
    public function shops(Request $request)
    {
        $plans = Charge::select('name')->distinct()->pluck('name')->filter()->values();
        return view('msdev2::agent.shops', compact('plans'));
    }

    /**
     * Return recent shops (used by dashboard)
     */
    public function shopsRecent(Request $request)
    {
        $limit = (int) $request->query('limit', 6);
        $rows = Shop::with('activeCharge')->orderBy('created_at','desc')->take($limit)->get();
        $items = $rows->map(function($s){
            $isUninstalled = ((int)($s->is_uninstalled ?? 0) === 1) || !empty($s->deleted_at);
            return [
                'id' => $s->id,
                'name' => $s->shop,
                'shop' => $s->shop,
                'domain' => $s->domain,
                'installed_at' => $s->created_at,
                'uninstalled' => $isUninstalled,
                'plan' => $s->activeCharge->name ?? null,
            ];
        });
        return response()->json($items);
    }

    /**
     * Return installs/uninstalls time series for the last N days
     */
    public function shopStats(Request $request)
    {
        $days = (int) $request->query('days', 30);
        $days = max(3, min(365, $days));
        $start = \Carbon\Carbon::now()->subDays($days-1)->startOfDay();

        $labels = [];
        $installs = [];
        $uninstalls = [];
        for ($i = 0; $i < $days; $i++) {
            $date = $start->copy()->addDays($i);
            $labels[] = $date->format('Y-m-d');
            $installs[] = Shop::whereDate('created_at', $date->toDateString())->count();
                // count uninstalls from uninstalled_at or deleted_at
                $fromUninstalledAt = Shop::whereDate('uninstalled_at', $date->toDateString())->count();
                $fromDeletedAt = Shop::onlyTrashed()->whereDate('deleted_at', $date->toDateString())->count();
                $uninstalls[] = $fromUninstalledAt + $fromDeletedAt;
        }

        return response()->json([ 'labels' => $labels, 'installs' => $installs, 'uninstalls' => $uninstalls ]);
    }

    /**
     * Latest installs list
     */
    public function latestInstalls(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $rows = Shop::with('activeCharge')->orderBy('created_at','desc')->take($limit)->get();
        return response()->json($rows->map(function($s){
            return ['id'=>$s->id,'shop'=>$s->shop,'domain'=>$s->domain,'plan'=>$s->activeCharge->name ?? null,'installed_at'=>$s->created_at];
        }));
    }

    /**
     * Latest uninstalls list
     */
    public function latestUninstalls(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $rows = Shop::with('activeCharge')
            ->where(function($q){ $q->where('is_uninstalled', 1)->orWhereNotNull('deleted_at'); })
            ->orderBy('uninstalled_at','desc')
            ->orderBy('deleted_at','desc')
            ->take($limit)
            ->get();

        return response()->json($rows->map(function($s){
            return ['id'=>$s->id,'shop'=>$s->shop,'domain'=>$s->domain,'plan'=>$s->activeCharge->name ?? null,'deleted_at'=>$s->deleted_at,'uninstalled_at'=>$s->uninstalled_at,'is_uninstalled'=> (int)($s->is_uninstalled ?? 0)];
        }));
    }

    /**
     * Update metadata key/value for a shop
     */
    public function updateMetadata(Request $request, $id)
    {
        $shop = Shop::find($id);
        if(!$shop) return response()->json(['error'=>'Shop not found'],404);
        $key = $request->input('key');
        $value = $request->input('value');
        if(!$key) return response()->json(['error'=>'Key required'],400);
        $shop->setMetaData($key, $value);
        return response()->json(['ok'=>true,'key'=>$key,'value'=>$value]);
    }

    /**
     * Delete metadata key for a shop
     */
    public function deleteMetadata(Request $request, $id, $key)
    {
        $shop = Shop::find($id);
        if(!$shop) return response()->json(['error'=>'Shop not found'],404);
        $shop->deleteMeta($key);
        return response()->json(['ok'=>true,'deleted'=>$key]);
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
