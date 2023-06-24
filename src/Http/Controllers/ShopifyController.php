<?php
namespace Msdev2\Shopify\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Msdev2\Shopify\Models\Shop;
use Msdev2\Shopify\Lib\EnsureBilling;
use Shopify\Utils;
use Shopify\Webhooks\Registry;
use Shopify\Auth\OAuth;
use Illuminate\Support\Facades\Log;

class ShopifyController extends Controller{

    public function install(Request $request)
    {
        $shop = $request->shop;
        $api_key = config('msdev2.shopify_api_key');
        $scopes = config('msdev2.scopes');
        $redirect_uri = route("msdev2.callback");
        $shop = Utils::sanitizeShopDomain($shop);
        if(!$shop){
            return redirect()->back()->withErrors(['msg'=>'invalid domain']);
        }
        $install_url = "https://" . $shop . "/admin/oauth/authorize?client_id=" . $api_key . "&scope=" . $scopes . "&redirect_uri=" . urlencode($redirect_uri);
        return redirect($install_url);
    }
    public function generateToken(Request $request)
    {
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
        $query = array(
            "client_id" => $api_key, // Your API key
            "client_secret" => $shared_secret, // Your app credentials (secret key)
            "code" => $params['code'] // Grab the access key from the URL
        );
        $host = $request->query('host');
        $shop = Utils::sanitizeShopDomain($request->query('shop'));
        $session = OAuth::callback(
            $request->cookie(),
            $request->query(),
            ['App\Lib\CookieHandler', 'saveShopifyCookie'],
        );
        // Generate access token URL
        $url = "https://" . $shop . "/admin/oauth/access_token";
        $result = Http::post($url,$query);
        Shop::updateOrInsert(
            ['scope' => $result->json("scope"), 'access_token' => $result->json("access_token")],
            ['shop' => $shop]
        );
        $redirectUrl = Utils::getEmbeddedAppUrl($host);
        if(config('msdev2.webhooks')){
            $webhooks = explode(",",config('msdev2.webhooks'));
            foreach ($webhooks as $webhook) {
                $response = Registry::register(config('app.url').'/api/webhooks?type=', $webhook, $shop, $result->json("access_token"));
                if ($response->isSuccess()) {
                    Log::debug("Registered $webhook webhook for shop ".$shop);
                } else {
                    Log::error( "Failed to register $webhook  webhook for shop ".$shop." with response body: " ,[$response->getBody()]);
                }
            }
        }
        if (config('msdev2.billing.required')) {
            list($hasPayment, $confirmationUrl) = EnsureBilling::check($session, config('msdev2.billing'));
    
            if (!$hasPayment) {
                $redirectUrl = $confirmationUrl;
            }
        }
        return redirect($redirectUrl);
    }
}
?>
