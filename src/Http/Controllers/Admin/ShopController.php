<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Msdev2\Shopify\Http\Controllers\BaseController;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Models\Charge;
use Msdev2\Shopify\Models\Metadata;
use Msdev2\Shopify\Models\Ticket;

class ShopController extends BaseController
{
    /**
     * JSON API for Dashboard Autocomplete
     */
    public function autocomplete(Request $request)
    {
        $search = $request->get('q');
        
        if(strlen($search) < 2) return response()->json([]);

        $shops = Shop::select('id', 'shop', 'domain')
            ->where('shop', 'LIKE', "%{$search}%")
            ->orWhere('domain', 'LIKE', "%{$search}%")
            ->limit(10)
            ->get();

        return response()->json($shops);
    }

    /**
     * Direct Login Action
     */
    public function login($id)
    {
        $shopInfo = Shop::findOrFail($id);
        return redirect($shopInfo->generateLoginUrl());
    }
    /**
     * Display a listing of the shops.
     */
    /**
     * Advanced Shop List with Sort & Filters
     */
    public function index(Request $request)
    {
        $query = Shop::query();

        // 1. Text Search (Shop or Domain)
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s) {
                $q->where('shop', 'LIKE', "%$s%")
                  ->orWhere('domain', 'LIKE', "%$s%");
            });
        }

        // 2. Filter by Plan Name
        if ($request->filled('plan') && $request->plan !== 'all') {
            if ($request->plan === 'freemium') {
                $query->doesntHave('activeCharge');
            } else {
                $query->whereHas('charges', function($q) use ($request) {
                    $q->where('status', 'active')->where('name', $request->plan);
                });
            }
        }

        // 3. Sorting Logic
        $sortColumn = $request->get('sort', 'created_at'); // Default sort
        $sortDir = $request->get('dir', 'desc');

        if ($sortColumn === 'plan') {
            // Complex sort: Join charges table to sort by plan name
            $query->leftJoin('charges', function($join) {
                $join->on('shops.id', '=', 'charges.shop_id')
                     ->where('charges.status', 'active');
            })
            ->select('shops.*', 'charges.name as plan_name')
            ->orderBy('plan_name', $sortDir);
        } else {
            // Standard column sort
            $query->orderBy($sortColumn, $sortDir);
        }

        // Execute Query
        $shops = $query->with('activeCharge')->paginate(20)->withQueryString();

        // Get all unique plan names for the filter dropdown
        $allPlans = Charge::where('status', 'active')->distinct()->pluck('name');

        return view('msdev2::admin.shops.index', compact('shops', 'allPlans'));
    }

    public function show(Request $request, $id)
    {
        // 1. Fetch Shop Data (Variable: $shopInfo)
        $shopInfo = Shop::with(['metadata', 'charges', 'tickets'])->findOrFail($id);

        // 2. Plan History (Latest First)
        $charges = $shopInfo->charges()->orderBy('created_at', 'desc')->get();
        $activePlan = $charges->where('status', 'active')->first();

        // 3. JSON Detail
        $shopJsonDetail = !empty($shopInfo->detail) 
            ? (is_string($shopInfo->detail) ? json_decode($shopInfo->detail, true) : $shopInfo->detail) 
            : [];

        // 4. Log Logic: storage/logs/{shop_domain}/
        $logPath = storage_path("logs/{$shopInfo->shop}");
        $logFiles = [];
        $selectedLogFile = $request->get('log_file');

        if (File::exists($logPath) && File::isDirectory($logPath)) {
            $files = File::files($logPath);
            foreach ($files as $file) {
                if ($file->getExtension() === 'log') {
                    $logFiles[] = $file->getFilename();
                }
            }
            rsort($logFiles); // Latest dates first
        }
        
        // Default to latest log if none selected
        if (!$selectedLogFile && count($logFiles) > 0) {
            $selectedLogFile = $logFiles[0];
        }

        // 5. Dynamic Tables (from config)
        $dynamicTables = [];
        if (!empty(config('msdev2.tables'))) {
            $tablesList = explode(',', config('msdev2.tables'));
            foreach ($tablesList as $tableName) {
                $tableName = trim($tableName);
                try {
                    $rows = DB::table($tableName)->where('shop_id', $id)->latest()->limit(50)->get();
                    if ($rows->isNotEmpty()) $dynamicTables[$tableName] = $rows;
                } catch (\Exception $e) { continue; }
            }
        }

        // 6. GraphQL Metafields
        $shopifyMetafields = $this->getShopifyMetafields($shopInfo);

        return view('msdev2::admin.shops.show', compact(
            'shopInfo', 
            'charges',
            'activePlan',
            'shopJsonDetail', 
            'logFiles', 
            'selectedLogFile', 
            'dynamicTables',
            'shopifyMetafields'
        ));
    }

    // --- Helper: Get Log Content (AJAX) ---
    public function getLogContent(Request $request, $id)
    {
        $shop = Shop::findOrFail($id);
        $filename = $request->get('file');
        $path = storage_path("logs/{$shop->shop}/" . basename($filename));

        if (File::exists($path)) {
            return response()->json([
                'content' => File::get($path),
                'modified' => date('H:i:s', File::lastModified($path))
            ]);
        }
        return response()->json(['content' => 'File not found.', 'modified' => now()], 404);
    }

    // --- Helper: GraphQL Metafields ---
    private function getShopifyMetafields($shop)
    {
        $appId = config('msdev2.app_id');
        $query = <<<GQL
        query getMetafields(\$namespace: String!) {
            currentAppInstallation {
                metafields(first: 50, namespace: \$namespace) {
                    edges { node { id namespace key value type } }
                }
            }
            shop {
                metafields(first: 50, namespace: \$namespace) {
                    edges { node { id namespace key value type } }
                }
            }
        }
        GQL;

        try {
            $response = mGraph($shop)->query([
                'query' => $query, 'variables' => ['namespace' => $appId]
            ])->getDecodedBody();

            return [
                'private' => array_map(fn($e) => $e['node'], $response['data']['currentAppInstallation']['metafields']['edges'] ?? []),
                'public'  => array_map(fn($e) => $e['node'], $response['data']['shop']['metafields']['edges'] ?? []),
            ];
        } catch (\Exception $e) {
            return ['private' => [], 'public' => [], 'error' => $e->getMessage()];
        }
    }

    // --- Actions ---
    public function downloadLog(Request $request, $id) {
        $shop = Shop::findOrFail($id);
        $path = storage_path("logs/{$shop->shop}/" . basename($request->get('file')));
        return File::exists($path) ? response()->download($path) : back()->with('error', 'File not found');
    }

    public function deleteLog(Request $request, $id) {
        $shop = Shop::findOrFail($id);
        $path = storage_path("logs/{$shop->shop}/" . basename($request->get('file')));
        if(File::exists($path)) { File::delete($path); return redirect()->route('admin.shops.show', $id)->with('success', 'Log deleted'); }
        return back()->with('error', 'File not found');
    }

    public function storeMetadata(Request $request, $id) {
        $shop = Shop::findOrFail($id);
        $shop->metadata()->create($request->only('key', 'value'));
        return back()->with('success', 'Saved');
    }

    public function deleteMetadata($id) {
        Metadata::destroy($id);
        return back()->with('success', 'Deleted');
    }
    
}