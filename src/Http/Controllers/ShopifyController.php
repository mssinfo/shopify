<?php
namespace Msdev2\Shopify\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Msdev2\Shopify\Lib\AuthRedirection;
use Msdev2\Shopify\Lib\DbSessionStorage;
use Msdev2\Shopify\Models\Shop;
use Ramsey\Uuid\Nonstandard\Uuid;
use Shopify\ApiVersion;
use Shopify\Auth\Session;
use Shopify\Clients\HttpHeaders;
use Shopify\Context;
use Shopify\Exception\InvalidWebhookException;
use Shopify\Webhooks\Registry;
use Shopify\Utils;

class ShopifyController extends Controller{

    function fallback(Request $request) {
        return redirect(Utils::getEmbeddedAppUrl($request->query("host", null)) . "/" . $request->path());
    }
    public function install(Request $request)
    {
        return AuthRedirection::redirect($request);
    }
    public function generateToken(Request $request)
    {
        // $cookieCallback = function (OAuthCookie $cookie) use (&$cookiesSet) {
        //     $cookiesSet[$cookie->getName()] = $cookie;
        //     return !empty($cookie->getValue());
        // };
        // $session = OAuth::callback(
        //     $request->cookie(),
        //     $request->query(),
        //     $cookieCallback,
        // );
        // dd($session);
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
        $result = Http::post($url,$query);
        $shop = Shop::updateOrCreate(
            ['shop' => $shopName],
            ['scope' => $result->json("scope"),'is_uninstalled' => 0, 'access_token' => $result->json("access_token")]
        );
        $shop->refresh();
        $redirectUrl = Utils::getEmbeddedAppUrl($host);
        if(config('msdev2.webhooks')){
            $webhooks = explode(",",config('msdev2.webhooks'));
            foreach ($webhooks as $webhook) {
                $response = Registry::register('/shopify/webhooks', $webhook, $shopName, $result->json("access_token"));
                if ($response->isSuccess()) {
                    Log::debug("Registered $webhook webhook for shop ".$shopName);
                } else {
                    Log::error( "Failed to register $webhook  webhook for shop ".$shopName." with response body: " ,[$response->getBody()]);
                }
            }
        }
        Context::initialize(
            config('msdev2.shopify_api_key'),
            config('msdev2.shopify_api_secret'),
            config('msdev2.scopes'),
            $host,
            new DbSessionStorage(),
            ApiVersion::LATEST,
            config('msdev2.is_embedded_app'),
            false,
            null,
            '',
            null,
            [],
        );
        if($shop){
            $offlineSession = new Session(request()->session ?? 'offline_'.$shop->shop, $shop->shop, false, Uuid::uuid4()->toString());
            $offlineSession->setScope(Context::$SCOPES->toString());
            $offlineSession->setAccessToken($shop->access_token);
            $offlineSession->setExpires(strtotime('+1 day'));
            Context::$SESSION_STORAGE->storeSession($offlineSession);
            $result = \Msdev2\Shopify\Utils::rest($shop)->get('shop');
            $shop->detail = $result->getDecodedBody()["shop"];
            $shop->save();
        }
        if (config('msdev2.billing') && !$shop->activeCharge) {
            return redirect($redirectUrl.'/plan');
        }
        return redirect($redirectUrl);
    }
    public function webhooksAction(Request $request,$name = null)
    {
        $rawHeaders = $request->headers->all();
        $headers = new HttpHeaders($rawHeaders);
        Log::info("Request to webhook!",[$request->all(),$rawHeaders,$headers]);
        if($name){
            $shared_secret = config("msdev2.shopify_api_secret");
            $hmac = $request->get('hmac') ?? HttpHeaders::X_SHOPIFY_HMAC;
            $computed_hmac = hash_hmac('sha256', http_build_query($request->all()), $shared_secret);

            // Use hmac data to check that the response is from Shopify or not
            if (!isset($hmac) || !hash_equals($hmac, $computed_hmac)) {
                return mErrorResponse("Invalid HMAC request",[],401);
            }
            return mSuccessResponse("Valid http request");
        }
        $missingHeaders = $headers->diff(
            [HttpHeaders::X_SHOPIFY_HMAC, HttpHeaders::X_SHOPIFY_TOPIC, HttpHeaders::X_SHOPIFY_DOMAIN],
            false,
        );

        if (!empty($missingHeaders)) {
            $missingHeaders = implode(', ', $missingHeaders);
            return mErrorResponse("Missing one or more of the required HTTP headers to process webhooks",[$missingHeaders],401);
        }
        $topic = $headers->get(HttpHeaders::X_SHOPIFY_TOPIC);
        $hookClass = ucwords(str_replace('/',' ',$topic));
        $classWebhook = "\\App\\Webhook\\Handlers\\".str_replace(' ','',$hookClass);
        if (!class_exists($classWebhook)) {
            // Log::info("class hot found for hook");
            return mSuccessResponse("class hot found for hook");
        }
        Registry::addHandler(strtoupper(str_replace(' ','_',$hookClass)), new $classWebhook());
        try {
            $response = Registry::process($rawHeaders, $request->getContent());
            if ($response->isSuccess()) {
                Log::info("Responded to webhook!",[$response]);
                return mSuccessResponse("Responded to webhook!",[$response]);
                // Respond with HTTP 200 OK
            } else {
                // The webhook request was valid, but the handler threw an exception
                Log::error("Webhook handler failed with message: " . $response->getErrorMessage());
                return mErrorResponse("Webhook handler failed with message: " . $response->getErrorMessage());
            }
        } catch (\Exception $error) {
            // The webhook request was not a valid one, likely a code error or it wasn't fired by Shopify
            Log::error('excepion : '.$error->getMessage(),[$error]);
            return mErrorResponse('excepion : '.$error->getMessage(),[$error]);
        }
    }
}
?>
