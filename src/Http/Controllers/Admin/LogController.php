<?php
namespace Msdev2\Shopify\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Msdev2\Shopify\Http\Controllers\BaseController;

class LogController extends BaseController
{
    public function index(Request $request)
    {
        // 1. Get Logs
        $logPath = storage_path('logs/laravel.log');
        $parsedLogs = [];
        
        if (File::exists($logPath)) {
            // Read last 2000 lines to avoid memory limits
            $fileContent = implode("", array_slice(file($logPath), -2000));
            
            // Regex to match default Laravel log format:
            // [2025-11-20 10:30:00] local.ERROR: Message {"context":...}
            $pattern = '/\[(\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\] \w+\.([A-Z]+): (.*?)(?=\[\d{4}-\d{2}-\d{2}|$)/s';
            
            preg_match_all($pattern, $fileContent, $matches, PREG_SET_ORDER);
            
            // Process matches into array (Reverse to show newest first)
            foreach (array_reverse($matches) as $match) {
                $parsedLogs[] = [
                    'date'    => $match[1],
                    'level'   => strtolower($match[2]), // error, info, debug, alert
                    'message' => trim($match[3]),
                    'full'    => $match[0]
                ];
            }
        }

        // 2. Filter by Level (Alert, Info, Error)
        if ($request->filled('level') && $request->level !== 'all') {
            $parsedLogs = array_filter($parsedLogs, function($log) use ($request) {
                return $log['level'] === strtolower($request->level);
            });
        }

        // 3. Filter by Search Term
        if ($request->filled('search')) {
            $term = strtolower($request->search);
            $parsedLogs = array_filter($parsedLogs, function($log) use ($term) {
                return str_contains(strtolower($log['message']), $term) || 
                       str_contains(strtolower($log['level']), $term);
            });
        }

        // 4. Simple Array Pagination
        $perPage = 40;
        $currentPage = Request()->get('page', 1);
        $offset = ($currentPage - 1) * $perPage;
        $total = count($parsedLogs);
        $logsToDisplay = array_slice($parsedLogs, $offset, $perPage);

        // Manual Paginator
        $paginatedLogs = new \Illuminate\Pagination\LengthAwarePaginator(
            $logsToDisplay, $total, $perPage, $currentPage, 
            ['path' => Request()->url(), 'query' => Request()->query()]
        );

        if($request->ajax()) {
            return view('msdev2::admin.logs.table_rows', ['logs' => $paginatedLogs])->render();
        }

        return view('msdev2::admin.logs.index', ['logs' => $paginatedLogs]);
    }

    public function download()
    {
        $logPath = storage_path('logs/laravel.log');
        return File::exists($logPath) ? response()->download($logPath) : back()->with('error', 'Log file not found.');
    }

    public function delete()
    {
        $logPath = storage_path('logs/laravel.log');
        if (File::exists($logPath)) {
            File::put($logPath, ''); // Clear content instead of deleting file to keep permissions
            return redirect()->route('admin.logs')->with('success', 'Log file cleared.');
        }
        return back()->with('error', 'Log file not found.');
    }
}