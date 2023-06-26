<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="stylesheet" href="https://www.uptowncss.com/css/uptown.css">
        <style>html, body {height: 100%;} body { display: flex; flex-direction: column;} main { flex: 1 0 auto; } footer { flex-shrink: 0; } nav{display: flex; align-items: center;background-color: #f1f2f4;border-bottom: 0.0625rem solid #dde0e4;}nav a{    border-left: 1px solid #ccc; display: flex;gap: 14px; align-items: center;color: #1f2124;text-align: center;padding: 14px 16px;text-decoration: none;font-size: 17px}nav a.right{margin-left: auto;}nav a.logo{margin: 0;padding: 0;border-left: 0;}nav a.logo img{max-width: 100%;max-height: 100%;width: 35px;margin: 5px 10px;}nav a:hover{background-color: #ebecef;color: black}nav a.active{background-color: #ebecef;color: #007a5c}nav .icon{display: none}@media screen and (max-width: 600px){nav a:not(:first-child){display: none}nav a.icon{float: right;display: block}}@media screen and (max-width: 600px){nav.responsive{position: relative}nav.responsive .icon{position: absolute;right: 0;top: 0}nav.responsive a{float: none;display: block;text-align: left}}</style>
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
        <script src="https://unpkg.com/turbolinks"></script>
        <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
        <script src="https://unpkg.com/@shopify/app-bridge{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-utils{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script>
            window.AppBridge = window['app-bridge'];
            var actions = window.AppBridge.actions;
            var utils = window['app-bridge-utils'];
            var createApp = window.AppBridge.default;
            var getSessionToken = utils.getSessionToken;
            var app = createApp({
                apiKey: "{{ config('msdev2.shopify_api_key') }}",
                host: "{{ \Request::get('host') }}",
                forceRedirect: false,
            });

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

        @yield('scripts')
    </body>
</html>
