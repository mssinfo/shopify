<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="stylesheet" href="https://www.uptowncss.com/css/uptown.css">
        @yield('styles')
    </head>
    <body>
        <main role="main">
            @include('msdev2::layout.menu')
            @yield('content')
            @if (config("msdev2.footer")) 
                <footer>
                    <article class="help">
                      <span></span>
                      {!! config("msdev2.footer") !!}
                    </article>
                </footer>
            @endif
        </main>
        <script src="https://unpkg.com/turbolinks"></script>
        <script src="https://cdn.jsdelivr.net/gh/alpinejs/alpine@v2.x.x/dist/alpine.min.js" defer></script>
        <script src="https://unpkg.com/@shopify/app-bridge{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script src="https://unpkg.com/@shopify/app-bridge-utils{{ '@'.config('msdev2.appbridge_version') }}"></script>
        <script>
            window.AppBridge = window['app-bridge'];
            var actions = window.AppBridge.actions;
            var utils = window['app-bridge-utils'];
            var createApp = window.AppBridge.default;
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