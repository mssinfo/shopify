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
        @php
            // Allow pages to disable Tawk initialization either by:
            // 1) Passing $disableTawk = true from controller, OR
            // 2) Setting @section('disable_tawk', 'true') in the Blade view
            $tawkEnabled = true;
            if (isset($disableTawk) && $disableTawk) {
                $tawkEnabled = false;
            }
            $disableTawkSection = trim($__env->yieldContent('disable_tawk'));
            if ($disableTawkSection !== '') {
                $tawkEnabled = ! in_array(strtolower($disableTawkSection), ['1','true','yes','on']);
            }
            if (!\Msdev2\Shopify\Utils::shouldRedirectToEmbeddedApp() && request()->header('sec-fetch-dest') !== 'iframe'){
                $tawkEnabled = false;
            }
        @endphp
        @if (config('msdev2.tawk_url') != '' && $tawkEnabled && isset($shop) && $shop)
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            // Initialize Tawk API early and register onLoad so attributes are reliably saved and visible to agents
            window.Tawk_API = window.Tawk_API || {};
            window.Tawk_LoadStart = new Date();
            // Persist visitor identity + custom attributes so agents can see/update (created if new, updated if exist)
            const __tawkVisitorAttributes = {
                app: "{{ config('app.name') }}",
                shop: "{{$shop->detail['myshopify_domain']}}",
                plan_name: "{{$shop->detail['plan_name']}}",
                plan_display_name: "{{$shop->detail['plan_display_name']}}",
                referrer: document.referrer,
                name: "{{$shop->detail['name']}}",
                email: "{{$shop->detail['email']}}",
            };
            // Prepare user details message
            const userDetailsMessage = 
                "🛍️ Shop: {{$shop->detail['myshopify_domain']}}\n" +
                "👤 Owner: {{$shop->detail['shop_owner']}}\n" +
                "📧 Email: {{$shop->detail['email']}}\n" +
                "📱 Phone: {{$shop->detail['phone'] ?? 'N/A'}}\n" +
                "🏷️ Plan: {{$shop->detail['plan_display_name']}} ({{$shop->detail['plan_name']}})\n" +
                "🏪 Store Name: {{$shop->detail['name']}}\n" +
                "📍 Referrer: " + (document.referrer || 'Direct');

            function __tawkApplyVisitor() {
                window.Tawk_API.setAttributes(__tawkVisitorAttributes, function(error){});
                window.Tawk_API.addEvent('User Visited', {
                    'details': userDetailsMessage
                }, function(error){
                    if (!error) {
                        console.log('User details sent to agent:', userDetailsMessage);
                    }
                });
                // Prefill chat form (UI) — does not persist by itself, but improves UX
                window.Tawk_API.visitor = {
                    name: "{{$shop->detail['shop_owner']}}",
                    email: "{{$shop->detail['email']}}",
                    phone: "{{$shop->detail['phone']}}",
                };
                window.Tawk_API.start();
            }
            // Send message to agent as soon as user visits
            window.Tawk_API.onLoad = function() { 
                // __tawkApplyVisitor();
            };
            
            // Always set handler; Tawk will call it when chat starts
            window.Tawk_API.onChatStarted = function(){ 
                // __tawkApplyVisitor(); 
            };
            // Fallback: re-apply after a short delay in case onLoad fires before our handler
            setTimeout(__tawkApplyVisitor, 1000);

            (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true;
                s1.src='{{ config("msdev2.tawk_url") }}';
                s1.charset='UTF-8';
                s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
        @endif
        @yield('scripts')
    </body>
</html>
