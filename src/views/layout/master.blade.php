<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
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
        @if (config('msdev2.SHOPIFY_ENABLE_TURBOLINKS'))
            <script src="https://unpkg.com/turbolinks"></script>
        @endif
        @if (config('msdev2.SHOPIFY_ENABLE_ALPINEJS'))
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
    </body>
</html>
