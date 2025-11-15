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
            <script src="{{ asset('msdev2/shopify.js') }}" ></script>
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
        <link rel="stylesheet" href="{{ asset('msdev2/css/app.css') }}?v=1.5">
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
            @include('msdev2::layout.menu')
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

        <script src="{{ asset('msdev2/app.js') }}"></script>
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
        <script type="text/javascript">
        @php
            // Determine whether to load Tawk
            $tawkEnabled = true;

            if (isset($disableTawk) && $disableTawk) {
                $tawkEnabled = false;
            }

            $disableTawkSection = trim($__env->yieldContent('disable_tawk'));
            if ($disableTawkSection !== '') {
                $tawkEnabled = ! in_array(strtolower($disableTawkSection), ['1','true','yes','on']);
            }

            if (!\Msdev2\Shopify\Utils::shouldRedirectToEmbeddedApp() &&
                request()->header('sec-fetch-dest') !== 'iframe') {
                $tawkEnabled = false;
            }
        @endphp

        @if (config('msdev2.tawk_url') && $tawkEnabled && isset($shop) && $shop)

        window.Tawk_API = window.Tawk_API || {};
        window.Tawk_LoadStart = new Date();

        // ✅ visitor data that MUST be stored inside Tawk
        const __tawkVisitorAttributes = {
            name: "{{ $shop->detail['name'] }} - {{ $shop->detail['shop_owner'] }}",                // ✅ shows as visitor name (notification)
            email: "{{ $shop->detail['email'] }}",
            phone: "{{ $shop->detail['phone'] }}",

            // custom fields
            shop: "https://{{ $shop->detail['myshopify_domain'] }}",
            // plan_name: "{{ $shop->activeCharge->name }}",
            // plan_display_name: "{{ $shop->detail['plan_display_name'] }}",
            app: "{{ config('app.name') }} | {{ $shop->activeCharge->name }}",
            referrer: document.title
        };

        // message for agents
        const userDetailsMessage =
            "🛍️ Shop: {{ $shop->detail['myshopify_domain'] }}\n" +
            "👤 Name: {{ $shop->detail['name'] }}\n" +
            "👤 Owner: {{ $shop->detail['shop_owner'] }}\n" +
            "📧 Email: {{ $shop->detail['email'] }}\n" +
            "📱 Phone: {{ $shop->detail['phone'] ?? 'N/A' }}\n" +
            "🏷️ Charge Name: {{ $shop->activeCharge->name }}\n" +
            "🏷️ Plan: {{ $shop->detail['plan_display_name'] }} ({{ $shop->detail['plan_name'] }})\n" +
            "🏪 Store Name: {{ $shop->detail['name'] }}\n" +
            "📍 Referrer: " + (document.referrer || 'Direct');

        // ✅ Safe loader – retries until Tawk API becomes available
        function __tawkApplyVisitor() {
            if (!window.Tawk_API || !window.Tawk_API.setAttributes) {
                console.warn("Tawk not ready yet… retrying");
                return setTimeout(__tawkApplyVisitor, 500);
            }

            // If the widget isn't online yet, wait and retry. This prevents
            // calls that rely on the Tawk socket server acknowledgement
            // (which can throw "Socket server did not execute the callback").
            try {
                var status = (typeof window.Tawk_API.getStatus === 'function') ? window.Tawk_API.getStatus() : null;
                if (status !== 'online') {
                    console.warn('Tawk not online (status=' + status + ')… retrying');
                    return setTimeout(__tawkApplyVisitor, 1000);
                }
            } catch(e) {
                console.warn('Error checking Tawk status, will retry', e);
                return setTimeout(__tawkApplyVisitor, 1000);
            }

            // ✅ This sets the REAL visitor identity (name, email, phone)
            try {
                window.Tawk_API.setAttributes(__tawkVisitorAttributes, function(err){
                    if (err) console.error("Tawk setAttributes error:", err);
                });
            } catch(e) {
                console.warn('Tawk.setAttributes threw an error, will retry', e);
                return setTimeout(__tawkApplyVisitor, 1000);
            }

            // ✅ Event for agents
            try {
                window.Tawk_API.addEvent('User Visited', {
                    details: userDetailsMessage
                }, function(err){
                    if (!err) {
                        console.log("✅ User details sent to Tawk Agent");
                    }
                });
            } catch(e) {
                console.warn('Tawk.addEvent threw an error, continuing', e);
            }

            // ✅ Start chat (optional)
            try { window.Tawk_API.start(); } catch(e){}
        }

        // ✅ run once Tawk is loaded
        window.Tawk_API.onLoad = function() {
            console.log("✅ Tawk Loaded");
            __tawkApplyVisitor();
        };

        // ✅ also apply when chat starts
        window.Tawk_API.onChatStarted = function() {
            console.log("✅ Chat Started");
            __tawkApplyVisitor();
        };

        // ✅ final fallback
        setTimeout(__tawkApplyVisitor, 3000);

        // ✅ Tawk embed script
        (function(){
            var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
            s1.async=true;
            s1.src='{{ config("msdev2.tawk_url") }}';
            s1.charset='UTF-8';
            s1.setAttribute('crossorigin','*');
            s0.parentNode.insertBefore(s1,s0);
        })();
        @endif
        </script>

        @yield('scripts')
    </body>
</html>
