<?php

namespace Msdev2\Shopify\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Msdev2\Shopify\Services\CreditService;

class CheckCredits
{
    /**
     * Handle an incoming request.
     *
     * Block if no credits remaining.
     */
    public function handle(Request $request, Closure $next)
    {
        $shop = mShop(); // your helper to get current shop

        $remaining = CreditService::totalRemaining($shop);

        if ($remaining <= 0) {

            // If it's an AJAX request, return JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'error'   => 'No credits remaining. Please buy more credits.'
                ], 402);
            }

            // For normal requests, redirect to usage/billing page
            return redirect()
                ->route('msdev2.shopify.usage')
                ->with('error', 'You have no credits remaining. Please buy more credits to continue.');
        }

        return $next($request);
    }
}
