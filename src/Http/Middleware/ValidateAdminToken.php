<?php
namespace Msdev2\Shopify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class ValidateAdminToken
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // If the current user is an authenticated admin, allow the request
        if (Auth::check()) {
            return $next($request);
        }

        $token = $request->query('token');
        $shop = $request->query('shop');

        // Only validate when a token is present. If no token, do not interfere.
        if ($token) {
            $key = 'admin_direct_' . $token;
            // Use get() to avoid consuming the token on read. Consumption
            // should happen at the point the app establishes the intended session.
            $data = Cache::get($key);

            if (!$data || (!empty($shop) && ($data['shop'] ?? null) !== $shop)) {
                // invalid token - redirect to base URL (strip query)
                return redirect(config('app.url'));
            }
            // token valid - allow request to proceed (we did not consume token here)
        }

        return $next($request);
    }
}
