<?php
namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;

class LogsController extends BaseController{

    private function getLogFileDates()
    {
        $dates = [];
        $files = glob(storage_path('logs/'.mShopName().'/*.log'));
        $files = array_reverse($files);
        foreach ($files as $path) {
            $fileName = basename($path);
            array_push($dates, $fileName);
        }
        return $dates;
    }


    public function index(Request $request)
    {
        $availableDates = $this->getLogFileDates();

        // Accept query params for date, search query and level
        $selectedDate = $request->query('date', $availableDates[0] ?? null);
        $q = $request->query('q', null);
        $level = $request->query('level', null);

        $logs = $this->getLogs($availableDates, $selectedDate);

        // If search or level provided, filter logs server-side for performance on large files
        if ($q) {
            $qLower = mb_strtolower($q);
            $logs = array_filter($logs, function($row) use ($qLower) {
                return mb_strpos(mb_strtolower($row['message']), $qLower) !== false
                    || mb_strpos(mb_strtolower($row['timestamp']), $qLower) !== false
                    || mb_strpos(mb_strtolower($row['env']), $qLower) !== false;
            });
        }
        if ($level) {
            $lvl = strtoupper($level);
            $logs = array_filter($logs, function($row) use ($lvl) {
                return strtoupper($row['type']) === $lvl;
            });
        }

        $data = [
            'label'=>['INFO', 'EMERGENCY', 'CRITICAL', 'ALERT', 'ERROR', 'WARNING', 'NOTICE', 'DEBUG'],
            'available_log_dates' => $availableDates,
            'selected_date' => $selectedDate,
            'logs' => array_values($logs),
            'query' => $q,
            'level' => $level,
        ];
        return view('msdev2::agent.logs',compact('data'));
    }
    /**
     * Download the full raw log file for a given date
     */
    public function download(Request $request)
    {
        $availableDates = $this->getLogFileDates();
        $date = $request->query('date', $availableDates[0] ?? null);
        if (!$date) {
            return redirect()->route('msdev2.agent.logs')->withErrors(['log' => 'No log file available']);
        }
        $path = storage_path('logs/' . mShopName() . '/' . $date);
        if (!file_exists($path)) {
            return redirect()->route('msdev2.agent.logs')->withErrors(['log' => 'Log file not found']);
        }
        return response()->download($path, $date, ['Content-Type' => 'text/plain']);
    }

    /**
     * Clear (truncate) a log file for a given date
     */
    public function clear(Request $request)
    {
        $availableDates = $this->getLogFileDates();
        $date = $request->input('date', $availableDates[0] ?? null);
        if (!$date || !in_array($date, $availableDates)) {
            return redirect()->route('msdev2.agent.logs')->withErrors(['log' => 'Invalid log file selected']);
        }
        $path = storage_path('logs/' . mShopName() . '/' . $date);
        if (!file_exists($path)) {
            return redirect()->route('msdev2.agent.logs')->withErrors(['log' => 'Log file not found']);
        }
        // Truncate file
        file_put_contents($path, '');
        return redirect()->route('msdev2.agent.logs', ['date' => $date])->with('success', 'Log file cleared');
    }

    /**
     * Delete a log file for a given date
     */
    public function delete(Request $request)
    {
        $availableDates = $this->getLogFileDates();
        $date = $request->input('date', $availableDates[0] ?? null);
        if (!$date || !in_array($date, $availableDates)) {
            return redirect()->route('msdev2.agent.logs')->withErrors(['log' => 'Invalid log file selected']);
        }
        $path = storage_path('logs/' . mShopName() . '/' . $date);
        if (!file_exists($path)) {
            return redirect()->route('msdev2.agent.logs')->withErrors(['log' => 'Log file not found']);
        }
        unlink($path);
        return redirect()->route('msdev2.agent.logs')->with('success', 'Log file deleted');
    }
    private function getLogs($availableDates, $configDate = null)
    {
        if (count($availableDates) == 0) {
            return [];
        }
        if ($configDate == null) {
            $configDate = $availableDates[0];
        }

        if (!in_array($configDate, $availableDates)) {
            return [];
        }


        $pattern = "/^\[(?<date>.*)\]\s(?<env>\w+)\.(?<type>\w+):(?<message>.*)/m";

        $fileName =  $configDate;
        $content = file_get_contents(storage_path('logs/' . mShopName() .'/'. $fileName));
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER, 0);

        $logs = [];
        foreach ($matches as $match) {
            $logs[] = [
                'timestamp' => $match['date'],
                'env' => $match['env'],
                'type' => $match['type'],
                'message' => trim($match['message'])
            ];
        }
        return $logs;
    }

}

?>
