<?php

namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Msdev2\Shopify\Lib\AuthRedirection;
use Msdev2\Shopify\Lib\DbSessionStorage;
use Msdev2\Shopify\Models\Session as ModelsSession;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Utils as ShopifyUtils;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shopify\Auth\FileSessionStorage;
use Shopify\Auth\Session;
use Shopify\Clients\HttpHeaders;
use Shopify\Context;
use Shopify\Utils;
use Shopify\Webhooks\Registry;

class ShopifyController extends BaseController
{

    function fallback(Request $request)
    {
        $this->clearCache();
        Log::error("install fallback app on ", [$request->all(), $_SERVER]);
        $shopName = $request->shop ?? null;
        if (strpos($request->getRequestUri(), config("msdev2.proxy_path")) !== false && !isset($request->shop)) {
            $shopName = $_SERVER['HTTP_HOST'];
        }
        if ($shopName) {
            $shop = Shop::where('shop', $shopName)->orWhere('domain', $shopName)->first();
            if (!$shop) {
                return redirect(config("app.url") . '/authenticate?shop=' . $shopName);
            }
            if (ShopifyUtils::shouldRedirectToEmbeddedApp($request))
                return redirect(Utils::getEmbeddedAppUrl($request->query("host", null)) . "/" . $request->path());
            return false;
        }
        return ["status" => "fallback success"];
    }

    public function install(Request $request)
    {
        return AuthRedirection::redirect($request);
    }

    public function help(Request $request)
    {
        return view("msdev2::help");
    }

    public function generateToken(Request $request)
    {
        $this->clearCache();
        $shared_secret = config("msdev2.shopify_api_secret");
        $api_key = config('msdev2.shopify_api_key');
        $params = $request->all(); // Retrieve all request parameters
        $hmac = $request->get('hmac'); // Retrieve HMAC request parameter
        $params = array_diff_key($params, array('hmac' => '')); // Remove hmac from params
        ksort($params); // Sort params lexographically

        // Compute SHA256 digest
        $computed_hmac = hash_hmac('sha256', http_build_query($params), $shared_secret);

        // Use hmac data to check that the response is from Shopify or not
        if (!hash_equals($hmac, $computed_hmac)) {
            return "NOT VALIDATED – Someone is trying to be shady!";
        }
        $host = $request->query('host');
        $shopName = Utils::sanitizeShopDomain($request->query('shop'));

        $query = array(
            "client_id" => $api_key, // Your API key
            "client_secret" => $shared_secret, // Your app credentials (secret key)
            "code" => $params['code'] // Grab the access key from the URL
        );
        // Generate access token URL
        $url = "https://" . $shopName . "/admin/oauth/access_token";
        $result = Http::withOptions(['verify' => false])->post($url, $query);
        $shop = Shop::updateOrCreate(
            ['shop' => $shopName],
            ['scope' => $result->json("scope"), 'is_uninstalled' => 0, 'access_token' => $result->json("access_token")]
        );
        $shop->refresh();
        $redirectUrl = Utils::getEmbeddedAppUrl($host);
        if (config('msdev2.webhooks')) {
            try {
                $webhooks = explode(",", config('msdev2.webhooks'));
                foreach ($webhooks as $webhook) {
                    Registry::register('/shopify/webhooks', $webhook, $shopName, $result->json("access_token"));
                }
            } catch (\Throwable $th) {
                Log::error("Webhook registration failed", ['error' => $th->getMessage()]);
            }
        }
        Context::initialize(
            apiKey: config('msdev2.shopify_api_key'),
            apiSecretKey: config('msdev2.shopify_api_secret'),
            scopes: config('msdev2.scopes'),
            hostName: $host,
            sessionStorage: new DbSessionStorage(),
            apiVersion: config('msdev2.api_version'),
            isEmbeddedApp: config('msdev2.is_embedded_app'),
            isPrivateApp: config('msdev2.is_private_app', false),
        );
        $session = ShopifyUtils::getSession($shop->shop);
        if ($session) {
            $sessionStore = new Session($session, $shop->shop, true, Uuid::uuid4()->toString());
            $sessionStore->setScope(Context::$SCOPES->toString());
            $sessionStore->setAccessToken($shop->access_token);
            $sessionStore->setExpires(strtotime('+1 day'));
            Context::$SESSION_STORAGE->storeSession($sessionStore);
        }

        // --- Start of REST implementation ---
        $response = ShopifyUtils::rest()->get('shop');
        $shop->detail = $response->getDecodedBody()['shop'];
        $shop->save();

        $classWebhook = "\\App\\Webhook\\Handlers\\AppInstalled";
        if (class_exists($classWebhook)) {
            $classWebhook::dispatch($shop);
        }
        if (config('msdev2.billing') && !$shop->activeCharge) {
            return redirect($redirectUrl . '/plan');
        }

        return redirect($redirectUrl);
    }

    public function webhooksAction(Request $request, $name = null)
    {
        $rawHeaders = $request->headers->all();
        $headers = new HttpHeaders($rawHeaders);
        if ($name) {
            $shared_secret = config("msdev2.shopify_api_secret");
            $hmac = $request->get('hmac') ?? HttpHeaders::X_SHOPIFY_HMAC;
            $computed_hmac = hash_hmac('sha256', http_build_query($request->all()), $shared_secret);

            // Use hmac data to check that the response is from Shopify or not
            if (!isset($hmac) || !hash_equals($hmac, $computed_hmac)) {
                return mErrorResponse("Invalid HMAC request", [], 401);
            }
            return mSuccessResponse("Valid http request");
        }
        $missingHeaders = $headers->diff(
            [HttpHeaders::X_SHOPIFY_HMAC, HttpHeaders::X_SHOPIFY_TOPIC, HttpHeaders::X_SHOPIFY_DOMAIN],
            false,
        );

        if (!empty($missingHeaders)) {
            $missingHeaders = implode(', ', $missingHeaders);
            return mErrorResponse("Missing one or more of the required HTTP headers to process webhooks", [$missingHeaders], 401);
        }
        $topic = $headers->get(HttpHeaders::X_SHOPIFY_TOPIC);
        $hookClass = ucwords(str_replace('/', ' ', $topic));
        $classWebhook = "\\App\\Webhook\\Handlers\\" . str_replace(' ', '', $hookClass);
        $shopName = $headers->get(HttpHeaders::X_SHOPIFY_DOMAIN);
        // Deduplicate identical webhook payloads for the same shop/topic to avoid repeated DB work
        $payload = $request->getContent() ?? '';
        $payloadHash = sha1($payload);
        $dedupeKey = 'webhook_dedupe:' . ($shopName ?: 'unknown') . ':' . ($topic ?: 'unknown') . ':' . $payloadHash;
        if (Cache::has($dedupeKey)) {
            // Already seen this exact payload recently — respond 200 to Shopify and skip processing
            return mSuccessResponse('Duplicate webhook ignored');
        }
        // Mark this payload as seen briefly (60s)
        Cache::put($dedupeKey, true, 60);

        // Acquire a short lock per-shop to prevent concurrent processing storms
        $lockKey = 'webhook_lock:' . ($shopName ?: 'global');
        $lock = Cache::lock($lockKey, 10);
        if (!$lock->get()) {
            // Another process is handling webhooks for this shop — skip to avoid duplicate DB ops
            return mSuccessResponse('Webhook processing deferred due to active lock');
        }
        if ($hookClass == "AppUninstalled") {
            ModelsSession::where('shop', $shopName)->delete();
            $this->clearCache(true);
        }
        if (!class_exists($classWebhook)) {
            return mSuccessResponse("class hot found for hook");
        }
        Registry::addHandler(strtoupper(str_replace(' ', '_', $hookClass)), new $classWebhook());
        try {
            $response = Registry::process($rawHeaders, $request->getContent());
            if ($response->isSuccess()) {
                return mSuccessResponse("Responded to webhook!", [$response]);
            } else {
                mLog("Webhook handler failed with message: " . $response->getErrorMessage(), [], 'error');
                return mErrorResponse("Webhook handler failed with message: " . $response->getErrorMessage());
            }
        } catch (\Exception $error) {
            try {
                $cls = new $classWebhook();
                mLog('exception while processing webhook: ' . $error->getMessage(), [$error], 'error');
                // attempt a safe fallback call to handler's handle method, if present
                if (method_exists($cls, 'handle')) {
                    $cls->handle($topic, $shopName, $request->all());
                }
            } catch (\Exception $inner) {
                mLog('exception in webhook fallback handler: ' . $inner->getMessage(), [$inner], 'error');
            }
            return mErrorResponse('exception : ' . $error->getMessage(), [$error]);
        } finally {
            // release lock
            try { $lock->release(); } catch (\Throwable $e) { /* ignore */ }
        }
    }

    private function clearCache($all = false): void
    {
        if ($all) Artisan::call('cache:forget shop');
        Artisan::call('cache:forget shopname');
    }
}