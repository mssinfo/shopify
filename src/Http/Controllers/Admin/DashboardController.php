<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Charge;
use Msdev2\Shopify\Models\Ticket;
use Illuminate\Support\Facades\DB;
use Msdev2\Shopify\Http\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        // --- 1. KPI STATISTICS ---
        
        $totalMrr = Charge::where('status', 'active')->sum('price');
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

        // C. Get "Free / Trial" shops (No Active Charge)
        $groupedShops['Free / Trial / No Plan'] = Shop::where('is_uninstalled', 0)
            ->doesntHave('activeCharge') // Assuming relation defined in model
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // --- 4. UNINSTALLED SHOPS (Recent 10) ---
        $uninstalledShops = Shop::where('is_uninstalled', 1)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get();

        // --- 5. OPEN TICKETS (Recent 10) ---
        $recentOpenTickets = Ticket::where('status', 0)
            ->with('shop')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('msdev2::admin.dashboard', compact(
            'totalMrr', 
            'activeShopsCount', 
            'conversionRate', 
            'ticketsPending', 
            'ticketsResolved',
            'planSummary',
            'freeShopsCount',
            'groupedShops',
            'uninstalledShops',
            'recentOpenTickets'
        ));
    }
}