<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }} |  @if (\Cache::get('shop')) {{\Cache::get('shop')->shop}}  @endif</title>
        <link rel="stylesheet" href="{{ asset('msdev2/app.css') }}">
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
            window.URL_ROOT = '{{ config("app.url") }}';
            var appBridgeEnabled = '{{ (config("msdev2.appbridge_enabled")) == true ? "1" : "0" }}';
            var isEmbeddedApp = '{{ config("msdev2.is_embedded_app") == true ? "1" : "0" }}';
            var apiKey = '{{ config("msdev2.shopify_api_key") }}';
        </script>
        @if (config('msdev2.appbridge_enabled'))
        <script src="https://unpkg.com/@shopify/app-bridge{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-utils{{ '@'.config('msdev2.appbridge_version') }}"></script>
        @endif
        <script src="{{ asset('msdev2/app.js') }}"></script>
        @if (config('msdev2.appbridge_enabled'))
            <script src="https://unpkg.com/@shopify/app-bridge{{ '@'.config('msdev2.appbridge_version') }}"></script>
            <script src="https://unpkg.com/@shopify/app-bridge-utils{{ '@'.config('msdev2.appbridge_version') }}"></script>
            <script data-turbolinks-eval="false">
            @if (config('msdev2.menu.list'))
                var NavigationMenu = actions.NavigationMenu;
                var AppLink = actions.AppLink;
                NavigationMenu.create(app, {
                    items: [
                    @foreach (config('msdev2.menu.list') as $menu)
                        @if (isset($menu["position"]) && ($menu["position"] == "sidebar" || $menu["position"] == "all"))
                        AppLink.create(app, {
                            label: '{{$menu["label"]}}',
                            destination: '{{$menu["destination"]}}',
                        }),
                        @endif
                    @endforeach
                    ]
                });
            @endif
            </script>
        @endif
        @yield('scripts')
        @if (config('msdev2.tawk_url') != '')
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
            @if (\Cache::get('shop'))
                Tawk_API.visitor = {
                    name : "{{\Cache::get('shop')->detail['shop_owner']}}",
                    email : "{{\Cache::get('shop')->detail['customer_email']}}",
                    phone : "{{\Cache::get('shop')->detail['phone']}}",
                    shop : "{{\Cache::get('shop')->detail['myshopify_domain']}}",
                    plan_name : "{{\Cache::get('shop')->detail['plan_name']}}",
                    plan_display_name : "{{\Cache::get('shop')->detail['plan_display_name']}}",
                    referrer : document.referrer,
                };    
            @endif
            (function(){
            var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
            s1.async=true;
            s1.src='{{config("msdev2.tawk_url")}}';
            s1.charset='UTF-8';
            s1.setAttribute('crossorigin','*');
            s0.parentNode.insertBefore(s1,s0);
            })();
        </script>
        <!--End of Tawk.to Script-->
        @endif
    </body>
</html>
