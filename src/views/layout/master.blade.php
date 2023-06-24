<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
        
        <link rel="stylesheet" href="https://www.uptowncss.com/css/uptown.css">
        <script src="https://unpkg.com/turbolinks"></script>
        <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>

        @yield('styles')
    </head>

    <body>
        <div class="app-wrapper">
            <div class="app-content">
                <main role="main">
                    @yield('content')
                </main>
            </div>
        </div>

        <script src="https://unpkg.com@shopify/app-bridge{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script src="https://unpkg.com@shopify/app-bridge-utils{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script></script>
            window.AppBridge = window['app-bridge'];
            var actions = window.AppBridge.actions;
            var utils = window['app-bridge-utils'];
            var createApp = window.AppBridge.default;
            var app = createApp({
                apiKey: "{{ \Osiset\ShopifyApp\Util::getShopifyConfig('api_key', $shopDomain ?? Auth::user()->name ) }}",
                host: "{{ \Request::get('host') }}",
                forceRedirect: false,
            });
            {{-- var NavigationMenu = actions.NavigationMenu;
            var AppLink = actions.AppLink;
            NavigationMenu.create(app, {
                items: [
                @foreach (config('shopify-helper.menu') as $menu)
                @if (isset($menu["position"]) && ($menu["position"] == "sidebar" || $menu["position"] == "all"))
                AppLink.create(app, {
                    label: '{{$menu["label"]}}',
                    destination: '{{$menu["destination"]}}',
                }),
                @endif
                @endforeach
                ]
            }); --}}
        </script>

        @yield('scripts')


    </body>
</html>