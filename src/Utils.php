<?php

namespace Msdev2\Shopify;
use Illuminate\Support\Facades\Session;
use Exception;

/**
 * Class to store all util functions
 */
final class Utils
{
    /**
     * Returns a sanitized Shopify shop domain
     *
     * If the provided shop domain or hostname is invalid or could not be sanitized, returns null.
     *
     * @param string      $shop            A Shopify shop domain or hostname
     * @param string|null $myshopifyDomain A custom Shopify domain
     *
     * @return string|null $name a sanitized Shopify shop domain, null if the provided domain is invalid
     */
    public static function sanitizeShopDomain(string $shop, ?string $myshopifyDomain = null): ?string
    {
        $name = trim(strtolower($shop));

        if ($myshopifyDomain) {
            $allowedDomains = [preg_replace("/^\*?\.?(.*)/", "$1", $myshopifyDomain)];
        } else {
            $allowedDomains = ["myshopify.com", "myshopify.io"];
        }

        $allowedDomainsRegexp = "(" . implode("|", $allowedDomains) . ")";

        if (!preg_match($allowedDomainsRegexp, $name) && (strpos($name, ".") === false)) {
            $name .= '.' . ($myshopifyDomain ?? 'myshopify.com');
        }
        $name = preg_replace("/\A(https?\:\/\/)/", '', $name);

        if (preg_match("/\A[a-zA-Z0-9][a-zA-Z0-9\-]*\.{$allowedDomainsRegexp}\z/", $name)) {
            return $name;
        } else {
            return null;
        }
    }

    /**
     * Builds query strings that are compatible with Shopify's format for array handling
     * Example: IDs = [1,2,3]
     * PHP would generate:  ids[]=1&IDs[]=2&IDs[]=3
     * Shopify expects:     ids=["1","2","3"] (URL encoded)
     *
     * @param array $params Array of query parameters
     *
     * @return string The URL encoded query string ("foo=bar&bar=foo")
     */
    public static function buildQueryString(array $params): string
    {
        // Exclude HMAC from query string
        $params = array_filter($params, function ($key) {
            return $key !== 'hmac';
        }, ARRAY_FILTER_USE_KEY);

        // Concatenate arrays to conform with Shopify
        array_walk($params, function (&$value, $key) {
            if (!is_array($value)) {
                return;
            }

            $escapedValues = array_map(function ($value) {
                return sprintf('"%s"', $value);
            }, $value);
            $concatenatedValues = implode(',', $escapedValues);
            $encapsulatedValues = sprintf('[%s]', $concatenatedValues);

            $value = $encapsulatedValues;
        });

        // Building the actual query using PHP's native function
        return http_build_query($params);
    }

    /**
     * Determines if request is valid by processing secret key through an HMAC-SHA256 hash function
     *
     * @param array  $params array of parameters parsed from a URL
     * @param string $secret the secret key associated with the app in the Partners Dashboard
     *
     * @return bool true if the generated hexdigest is equal to the hmac parameter, false otherwise
     */
    public static function validateHmac(array $params, string $secret): bool
    {
        if (empty($params['hmac']) || empty($secret)) {
            return false;
        }

        return hash_equals(
            $params['hmac'],
            hash_hmac('sha256', self::buildQueryString($params), $secret)
        );
    }

    /**
     * Retrieves the query string arguments from a URL, if any
     *
     * @param string $url the url string with query parameters to be extracted
     *
     * @return array $params Array of key/value pairs representing the query parameters or empty array
     */
    public static function getQueryParams(string $url): array
    {
        $queryString = parse_url($url, PHP_URL_QUERY);
        if (empty($queryString)) {
            return [];
        }
        parse_str($queryString, $params);
        return $params;
    }

    /**
     * Returns the appropriate URL for the host that should load the embedded app.
     *
     * @param string $host The host value received from Shopify
     *
     * @return string
     */
    public static function getEmbeddedAppUrl(string $host): string
    {
        if (empty($host)) {
            throw new Exception("Host value cannot be empty");
        }

        $decodedHost = base64_decode($host, true);
        if (!$decodedHost) {
            throw new Exception("Host was not a valid base64 string");
        }

        // $apiKey = Context::$API_KEY;
        $apiKey = '';//Session::get('variableName');
        return "https://$decodedHost/apps/$apiKey";
    }
}