<?php
namespace Msdev2\Shopify\Http\Middleware;
use Closure;
use Illuminate\Auth\AuthManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class VerifyShopify
{
    public function __construct( AuthManager $auth) {
        $this->auth = $auth;
    }
    public function handle(Request $request, Closure $next)
    {
        if (Str::contains($request->getRequestUri(), ['/auth/callback', '/install', '/billing']) || isset($request->shop)) {
            return $next($request);
        }
        return ['invalid url shop'];
    }
}