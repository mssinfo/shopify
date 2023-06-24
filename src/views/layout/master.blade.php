<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name') }}</title>
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

        <script src="https://unpkg.com/@shopify/app-bridge{{ config('msdev2.appbridge_version') ? '@'.config('appbridge_version.appbridge_version') : '' }}"></script>
        <script>
            var AppBridge = window['app-bridge'];
            var createApp = AppBridge.default;
            var app = createApp({
                apiKey: '{{ config('msdev2.shopify_api_key') }}',
                shopOrigin: '{{ request()->get('shop') }}',
                forceRedirect: false,
            });
        </script>

        @yield('scripts')


    </body>
</html>