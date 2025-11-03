<?php

namespace Msdev2\Shopify\Http\Middleware;

use Closure;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Http\RedirectResponse;
use Msdev2\Shopify\Lib\AuthRedirection;
use Msdev2\Shopify\Lib\EnsureBilling;
use Msdev2\Shopify\Lib\TopLevelRedirection;
use Shopify\Auth\Session;
use Shopify\Clients\Graphql;
use Shopify\Context;
use Shopify\Utils;

class EnsureShopifySession
{
    public const ACCESS_MODE_ONLINE = 'online';
    public const ACCESS_MODE_OFFLINE = 'offline';

    public const TEST_GRAPHQL_QUERY = <<<QUERY
    {
        shop {
            name
        }
    }
    QUERY;

    /**
     * Checks if there is currently an active Shopify session.
     *
     * @param Request $request
     * @param Closure $next
     * @param string $accessMode
     * @return RedirectResponse|mixed
     * @throws Exception
     */
    public function handle(Request $request, Closure $next, string $accessMode = self::ACCESS_MODE_OFFLINE)
    {
        $isOnline = $this->isOnlineMode($accessMode);
        $session = Utils::loadCurrentSession($request->header(), $request->cookie(), $isOnline);

        $shop = Utils::sanitizeShopDomain($request->query('shop', ''));
        if ($session && $shop && $session->getShop() !== $shop) {
            return AuthRedirection::redirect($request);
        }

        if ($session && $this->validateActiveSession($session, $request)) {
            $request->attributes->set('shopifySession', $session);
            return $next($request);
        }

        $shop = $this->determineShopDomain($request, $session);

        return TopLevelRedirection::redirect($request, "/api/auth?shop=$shop");
    }

    /**
     * Validates an active session by either checking billing or running a test GraphQL query.
     *
* @param Session $session The session to be validated.
     * @param Request $request The incoming request.
     * @return bool True if the session is valid, false otherwise.
     */
    private function validateActiveSession(Session $session, Request $request): bool
    {
        if (!$session->isValid()) {
            return false;
        }

        if (Config::get('msdev2.billing.required', false)) {
            try {
                // The EnsureBilling::check method expects our app's Shop model, not the Shopify Session object.
                // We must load our Shop model from the database using the shop domain from the session.
                $shopModel = \Msdev2\Shopify\Models\Shop::where('shop', $session->getShop())->first();

                // If the shop doesn't exist in our database for any reason, we cannot proceed.
                if (!$shopModel) {
                    return false;
                }

                // Now, call the check method with the correct Shop model instance.
                list($hasPayment, $confirmationUrl) = EnsureBilling::check($shopModel, Config::get('msdev2.billing'));

                if (!$hasPayment) {
                    TopLevelRedirection::redirect($request, $confirmationUrl);
                    return false;
                }
                return true;
            } catch (Exception $e) {
                // An exception during billing check means the session is likely invalid.
                return false;
            }
        }

        // If billing is not required, run a simple GraphQL query to ensure the access token is still valid.
        try {
            $client = new Graphql($session->getShop(), $session->getAccessToken());
            $response = $client->query(self::TEST_GRAPHQL_QUERY);
            return $response->getStatusCode() === 200;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Determines the shop domain from the request or an existing (but potentially invalid) session.
     *
     * @param Request $request
     * @param Session|null $session
     * @return string|null
     */
    private function determineShopDomain(Request $request, ?Session $session): ?string
    {
        $shop = Utils::sanitizeShopDomain($request->query('shop', ''));
        if ($shop) {
            return $shop;
        }

        if ($session) {
            return $session->getShop();
        }

        if (Context::$IS_EMBEDDED_APP) {
            $bearerPresent = preg_match("/Bearer (.*)/", $request->header('Authorization', ''), $bearerMatches);
            if ($bearerPresent) {
                $payload = Utils::decodeSessionToken($bearerMatches[1]);
                return parse_url($payload['dest'], PHP_URL_HOST);
            }
        }

        return null;
    }

    /**
     * Determines if the required access mode is 'online'.
     *
     * @param string $accessMode
     * @return bool
     * @throws Exception
     */
    private function isOnlineMode(string $accessMode): bool
    {
        if ($accessMode === self::ACCESS_MODE_ONLINE) {
            return true;
        }
        if ($accessMode === self::ACCESS_MODE_OFFLINE) {
            return false;
        }

        throw new Exception(
            "Unrecognized access mode '$accessMode', accepted values are 'online' and 'offline'"
        );
    }
}