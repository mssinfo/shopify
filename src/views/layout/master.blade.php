<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }} |  @if (\Cache::get('shopName')) {{\Cache::get('shopName')}}  @endif</title>
        <link rel="stylesheet" href="{{ asset('msdev2/app.css') }}?v=1.2">
        <meta name="shopify-api-key" content="{{config('msdev2.shopify_api_key')}}" />
        <script>window.URL_ROOT = '{{ config("app.url") }}';</script>
        @if (config('msdev2.appbridge_enabled'))
        <script src="https://cdn.shopify.com/shopifycloud/app-bridge.js" ></script>
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
        @if (config('msdev2.tawk_url') != '')
        <!--Start of Tawk.to Script-->
        <script type="text/javascript">
            window.Tawk_API = window.Tawk_API ||{}, Tawk_LoadStart=new Date();
            (function(){
                var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
                s1.async=true;
                s1.src='{{config("msdev2.tawk_url")}}';
                s1.charset='UTF-8';
                s1.setAttribute('crossorigin','*');
                s0.parentNode.insertBefore(s1,s0);
            })();
            setTimeout(() => {
                @if ($shop)
                window.Tawk_API.visitor = {
                    name : "{{$shop->detail['shop_owner']}}",
                    email : "{{$shop->detail['email']}}",
                    phone : "{{$shop->detail['phone']}}",
                };
                window.Tawk_API.onLoad = function(){
                    window.Tawk_API.setAttributes({
                        shop : "{{$shop->detail['myshopify_domain']}}",
                        plan_name : "{{$shop->detail['plan_name']}}",
                        plan_display_name : "{{$shop->detail['plan_display_name']}}",
                        referrer : document.referrer,
                        name : "{{$shop->detail['name']}}",
                        email : "{{$shop->detail['email']}}",
                        phone : "{{$shop->detail['phone']}}",
                    }, function(error){});
                }
                @endif
            }, 1000);
        </script>
        <!--End of Tawk.to Script-->
        @endif
        @yield('scripts')
    </body>
</html>
