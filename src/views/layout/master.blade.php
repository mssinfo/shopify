<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <meta name="shopify-api-key" content="{{config('msdev2.shopify_api_key')}}" />
        <meta name="shopify-shop" content="{{ $shop?->shop }}" />
        <title>{{ config('app.name') }} |  {{ $shop?->detail['name'] ?? '' }} | {{ str_replace(config('app.url'), '', url()->current()) }}</title>
        <script>window.URL_ROOT = '{{ config("app.url") }}';</script>
        @if (!\Msdev2\Shopify\Utils::shouldRedirectToEmbeddedApp() && request()->header('sec-fetch-dest') !== 'iframe')
            <script>
                window.shopify = window.shopify || {};
                window.shopify.config = {
                    appUrl: '{{ config("app.url") }}',
                    shopUrl: '{{ $shop?->shop }}',
                    apiKey: '{{ config("msdev2.shopify_api_key") }}',
                    accessToken: '{{ $shop?->access_token }}',
                    version: '{{ config("msdev2.api_version") }}'
                };
            </script>   
            <script src="{{ asset('msdev2/js/shopify.js') }}" ></script>
            <style>
                ui-nav-menu {
                    display: none;
                } 
            </style>
        @elseif (config('msdev2.appbridge_enabled'))
            <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js" ></script>
        @endif
        @if (config('msdev2.enable_polaris')) 
        <script src="https://cdn.shopify.com/shopifycloud/polaris.js"></script>
        @endif
        <link rel="stylesheet" href="{{ asset('msdev2/css/app.css') }}">
        @php
        $cssFile = null;
        // Prefer a Blade section 'css' so child views can set it using @section('css','name')
        $cssName = trim($__env->yieldContent('css')) ?: ($css ?? null);
        if (!empty($cssName)) {
            $cssPath = "msdev2/css/pages/{$cssName}.css";
            $cssFile = public_path($cssPath);
        }
        @endphp
        @if (!empty($cssFile) && file_exists($cssFile))
           <link rel="stylesheet" href="{{ asset($cssPath) }}?v={{ filemtime($cssFile) }}">
        @endif
        @yield('styles')
        
    </head>
    <body>
        <main role="main">
            @include('msdev2::layout.includes.menu')
            @yield('content')
        </main>
        @if (config("msdev2.footer"))
            <footer>
                <article class="help">
                    <span></span>
                    {!! config("msdev2.footer") !!}
                </article>
            </footer>
        @endif
        @if (config('msdev2.enable_turbolinks'))
            <script src="https://unpkg.com/turbolinks"></script>
        @endif
        @if (config('msdev2.enable_alpinejs'))
            <script src="https://unpkg.com/alpinejs" defer></script>
        @endif
        <script>
            var appBridgeEnabled = '{{ (config("msdev2.appbridge_enabled")) == true ? "1" : "0" }}';
            var isEmbeddedApp = '{{ config("msdev2.is_embedded_app") == true ? "1" : "0" }}';
            var apiKey = '{{ config("msdev2.shopify_api_key") }}';
        </script>

        <script src="{{ asset('msdev2/js/app.js') }}"></script>
        @if (config('msdev2.appbridge_enabled'))
            @if (config('msdev2.menu.list'))
            <ui-nav-menu>
                <a href="/" rel="home">Home</a>
                @foreach (config('msdev2.menu.list') as $menu)
                @if (isset($menu["position"]) && ($menu["position"] == "sidebar" || $menu["position"] == "all"))
                <a href="{{$menu["destination"]}}">{!!$menu["label"]!!}</a>
                @endif
                @endforeach
            </ui-nav-menu>
            @endif
        @endif
        

        @php
            // determine git branch for display (fallback to env GIT_BRANCH)
            $gitBranch = null;
            try {
                $gitHead = base_path('.git/HEAD');
                if (file_exists($gitHead)) {
                    $head = trim(@file_get_contents($gitHead));
                    if (str_starts_with($head, 'ref: ')) {
                        $gitBranch = basename($head);
                    } else {
                        $gitBranch = substr($head, 0, 7);
                    }
                }
            } catch (\Throwable $e) {
                $gitBranch = null;
            }
            if (!$gitBranch) {
                $gitBranch = env('GIT_BRANCH') ?: null;
            }
        @endphp
        @include('msdev2::layout.includes.chat')
        <x-admin-area>
            @if($gitBranch)
                <div style="position:fixed;left:12px;bottom:12px;z-index:999999;padding:6px 10px;background:rgba(17,24,39,0.95);color:#aee6ff;border-radius:8px;font-size:12px;font-family:Menlo,monospace;box-shadow:0 6px 20px rgba(2,6,23,0.6);">
                    git: {{ $gitBranch }}
                </div>
            @endif
        </x-admin-area>
        @yield('scripts')
    </body>
</html>
