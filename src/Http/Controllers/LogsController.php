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


        $logs = $this->getLogs($availableDates);

        $data = [
            'label'=>['INFO', 'EMERGENCY', 'CRITICAL', 'ALERT', 'ERROR', 'WARNING', 'NOTICE', 'DEBUG'],
            'available_log_dates' => $availableDates,
            'logs' => $logs
        ];
        return view('msdev2::logs',compact('data'));
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
