<?php
namespace Msdev2\Shopify\Lib;

use Illuminate\Support\Facades\Log;

class LoggingHttpClientProxy
{
    protected $client;
    protected $type;

    public function __construct($client, $type = 'rest')
    {
        $this->client = $client;
        $this->type = $type;
    }

    public function __call($method, $args)
    {
        $start = microtime(true);
        try {
            $result = call_user_func_array([$this->client, $method], $args);
        } catch (\Throwable $e) {
            $this->logEntry($method, $args, null, $e);
            throw $e;
        }

        $duration = round((microtime(true) - $start) * 1000, 2);

        $responseSummary = $this->extractResponseSummary($result);

        $this->logEntry($method, $args, $responseSummary, null, $duration);

        // push a small console log entry into session so the admin-area component can print it
        try {
            if (function_exists('session')) {
                $entry = [
                    'time' => date('H:i:s'),
                    'type' => $this->type,
                    'method' => $method,
                    'args' => $this->shortArgs($args),
                    'response' => $responseSummary,
                    'duration_ms' => $duration,
                ];
                $arr = session('msdev2_debug_console', []);
                $arr[] = $entry;
                session(['msdev2_debug_console' => array_slice($arr, -200)]);
            }
        } catch (\Throwable $e) {
            // ignore session write failures
        }

        return $result;
    }

    protected function shortArgs($args)
    {
        try {
            $a = array_map(function ($v) {
                if (is_scalar($v)) return $v;
                return is_array($v) ? array_slice($v, 0, 6) : gettype($v);
            }, $args);
            return $a;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function extractResponseSummary($result)
    {
        try {
            if (is_null($result)) return null;
            if (is_array($result)) return array_slice($result, 0, 20);
            if (is_object($result)) {
                if (method_exists($result, 'getDecodedBody')) {
                    return $this->truncate(json_encode($result->getDecodedBody()));
                }
                if (method_exists($result, 'getBody')) {
                    $body = $result->getBody();
                    if (is_string($body)) return $this->truncate($body);
                    if (is_object($body) && method_exists($body, '__toString')) return $this->truncate((string) $body);
                }
                if (method_exists($result, 'json')) {
                    return $this->truncate(json_encode($result->json()));
                }
                return get_class($result);
            }
            return $this->truncate((string) $result);
        } catch (\Throwable $e) {
            return 'unserializable_response';
        }
    }

    protected function truncate($s, $len = 2000)
    {
        if (!is_string($s)) $s = json_encode($s);
        if (strlen($s) > $len) return substr($s, 0, $len) . '...';
        return $s;
    }

    protected function logEntry($method, $args, $responseSummary = null, $error = null, $duration = null)
    {
        $msg = strtoupper($this->type) . " CALL: {$method} ";
        try {
            $msg .= json_encode($this->shortArgs($args));
        } catch (\Throwable $e) {
            $msg .= 'args_unserializable';
        }
        $context = ['duration_ms' => $duration];
        if ($responseSummary) $context['response'] = is_string($responseSummary) ? $responseSummary : $responseSummary;
        if ($error) {
            $context['error'] = $error->getMessage();
            if (function_exists('mLog')) {
                mLog($msg, $context, 'error');
            } else {
                Log::error($msg, $context);
            }
            return;
        }
        if (function_exists('mLog')) {
            mLog($msg, $context, 'info');
        } else {
            Log::info($msg, $context);
        }
    }
}
