<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Charge;
use Msdev2\Shopify\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Msdev2\Shopify\Http\Controllers\BaseController;
use Illuminate\Http\Request;

class DashboardController extends BaseController
{
    public function index()
    {
        // --- 1. KPI STATISTICS ---
        
        $totalMrr = Charge::where('status', 'active')
            ->where(function($query) {
                $query->whereNull('trial_ends_on')
                      ->orWhere('trial_ends_on', '<=', now());
            })
            ->sum('price');
        $activeShopsCount = Shop::where('is_uninstalled', 0)->count();
        
        // Calculate Conversion (Paying / Active)
        $payingShopsCount = Charge::where('status', 'active')->where('price', '>', 0)->distinct('shop_id')->count('shop_id');
        $conversionRate = $activeShopsCount > 0 ? ($payingShopsCount / $activeShopsCount) * 100 : 0;
        
        // Ticket Counts
        $ticketsPending = Ticket::where('status', 0)->count();
        $ticketsResolved = Ticket::where('status', 2)->count();

        // --- 2. PLAN SUMMARY (COUNTS) ---
        // Used for the "Pro Plan - 1, Free Plan - 5" summary line
        $planSummary = Charge::where('status', 'active')
            ->select('name', DB::raw('count(*) as count'))
            ->groupBy('name')
            ->get();
        
        // Calculate Free/No Plan count manually
        $shopsWithPlan = Charge::where('status', 'active')->distinct('shop_id')->count('shop_id');
        $freeShopsCount = $activeShopsCount - $shopsWithPlan;

        // --- 3. GROUPED LISTS (Recent 10 per Plan) ---
        
        $groupedShops = [];

        // A. Get all active plan names
        $planNames = Charge::where('status', 'active')->distinct()->pluck('name');

        // B. Loop through each plan and get recent 10 shops
        foreach ($planNames as $planName) {
            $groupedShops[$planName] = Shop::where('is_uninstalled', 0)
                ->whereHas('charges', function ($q) use ($planName) {
                    $q->where('status', 'active')->where('name', $planName);
                })
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->with('charges') // Eager load for price display
                ->get();
        }

        // --- NEW: RECENT INSTALLS (Recent 10) ---
        $recentShops = Shop::where('is_uninstalled', 0)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->with('activeCharge')
            ->get();

        // --- 4. UNINSTALLED SHOPS (Recent 10) ---
        $uninstalledShops = Shop::where('is_uninstalled', 1)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->with('lastCharge')
            ->get();

        // --- 5. OPEN TICKETS (Recent 10) ---
        $recentOpenTickets = Ticket::where('status', 0)
            ->with('shop')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // --- 6. UPCOMING PAYMENTS (Next 10) ---
        // Prefer authoritative billing_on in charges; exclude Free plans
        $upcomingPayments = Charge::with('shop')
            ->where('status', 'active')
            ->where('type', 'recurring')
            ->whereNotIn('name', ['Free'])
            ->whereNotNull('billing_on')
            ->where('billing_on', '>', Carbon::now())
            ->orderBy('billing_on', 'asc')
            ->limit(10)
            ->get();

        // Normalize Charge models to expected view shape: object with shop, charge, amount, date, plan
        if ($upcomingPayments->isNotEmpty()) {
            $upcomingPayments = $upcomingPayments->map(function($c) {
                $date = null;
                try {
                    $date = $c->billing_on ? (string) Carbon::parse($c->billing_on)->toDateTimeString() : null;
                } catch (\Throwable $e) {}
                return (object)[
                    'shop' => $c->shop,
                    'charge' => $c,
                    'amount' => $c->price ?? ($c->amount ?? 0),
                    'date' => $date,
                    'plan' => $c->name ?? null,
                ];
            })->values();
        }

        // Fallback: if no charges returned (maybe billing_on not set), build from groupedShops
        if ($upcomingPayments->isEmpty()) {
            $all = collect();
            if (!empty($groupedShops) && is_iterable($groupedShops)) {
                foreach ($groupedShops as $pName => $shops) {
                    foreach ($shops as $s) {
                        $charges = $s->charges ?? [];
                        foreach ($charges as $c) {
                            $chargeName = strtolower(trim($c->name ?? ''));
                            $type = strtolower($c->type ?? 'recurring');
                            $billing = $c->billing_on ?? $c->date ?? null;
                            if ($billing) {
                                try {
                                    $billingDt = Carbon::parse($billing);
                                } catch (\Throwable $e) {
                                    continue;
                                }
                                if ($billingDt->isFuture() && $type === 'recurring' && $chargeName !== 'free') {
                                    $all->push((object)[
                                        'shop' => $s,
                                        'charge' => $c,
                                        'amount' => $c->price ?? $c->amount ?? 0,
                                        'date' => $billingDt->toDateTimeString(),
                                        'plan' => $c->name ?? $pName,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
            $upcomingPayments = $all->sortBy(function ($it) {
                return Carbon::parse($it->date)->timestamp;
            })->values()->take(10);
        }

        return view('msdev2::admin.dashboard', compact(
            'totalMrr', 
            'activeShopsCount', 
            'conversionRate', 
            'ticketsPending', 
            'ticketsResolved',
            'planSummary',
            'freeShopsCount',
            'groupedShops',
            'recentShops',
            'uninstalledShops',
            'recentOpenTickets',
            'upcomingPayments'
        ));
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