<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>{{ config('app.name') }}</title>
        <link rel="stylesheet" href="https://www.uptowncss.com/css/uptown.css">
        <style>
        html, body {height: 100%;} body { display: flex; flex-direction: column;} main { flex: 1 0 auto; } footer { flex-shrink: 0; } nav{display: flex; align-items: center;background-color: #f1f2f4;border-bottom: 0.0625rem solid #dde0e4;}nav a{    border-left: 1px solid #ccc; display: flex;gap: 14px; align-items: center;color: #1f2124;text-align: center;padding: 14px 16px;text-decoration: none;font-size: 17px}nav a.right{margin-left: auto;}nav a.logo{margin: 0;padding: 0;border-left: 0;}nav a.logo img{max-width: 100%;max-height: 100%;width: 35px;margin: 5px 10px;}nav a:hover{background-color: #ebecef;color: black}nav a.active{background-color: #ebecef;color: #007a5c}nav .icon{display: none}@media screen and (max-width: 600px){nav a:not(:first-child){display: none}nav a.icon{float: right;display: block}}@media screen and (max-width: 600px){nav.responsive{position: relative}nav.responsive .icon{position: absolute;right: 0;top: 0}nav.responsive a{float: none;display: block;text-align: left}}.mt-10{margin-top:10px;}.mt-20{margin-top:20px;}
        [readonly]::after { content: ''; position: absolute; width: 100%; height: 100%; top: 0; left: 0; z-index: 99; background-color: #cccccc7a;}.card{position: relative;}
        </style>
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
            window.$GLOBALS = {
                "shop": "{{ \Request::get('host') }}",
                "csrf-token": "{{ csrf_token() }}",
                "session": {
                    "content": "{{ session()->getId() }}",
                    "cookie": "{{ config('session.cookie') }}"
                },
                push : (path, param) => {
                    if(typeof param == 'object'){
                        param = {...param,...window.router.currentRoute.value.query}
                    }else{
                        param = window.router.currentRoute.value.query
                    }
                    if(window.router.getRoutes().find((route) => route.name === path))
                        return window.router.push({name: path, params: param})
                    return window.router.push({path: path, query: param})
                }
            }
        </script>
        @if (config('msdev2.appbridge_enabled'))
            <script src="https://unpkg.com/@shopify/app-bridge{{ '@'.config('msdev2.appbridge_version') }}"></script>
            <script src="https://unpkg.com/@shopify/app-bridge-utils{{ '@'.config('msdev2.appbridge_version') }}"></script>
            <script data-turbolinks-eval="false">
                window.AppBridge = window['app-bridge'];
                var app = window.AppBridge.createApp({
                    apiKey: "{{ config('msdev2.shopify_api_key') }}",
                    host: "{{ \Request::get('host') }}",
                    forceRedirect: {{ config('msdev2.is_embedded_app') == true ? 'true' : 'false' }},
                });
                var actions = window.AppBridge.actions;
                {{--
                // var createApp = window.AppBridge.default;
                // var utils = window['app-bridge-utils'];
                // var getSessionToken = utils.getSessionToken;
                // console.log(getSessionToken,'getSessionToken');

                // app.dispatch(
                //     actions.Redirect.toRemote({
                //         url: '{{route("msdev2.shopify.plan.index")}}'
                //     })
                // );

                // const modalOptions = {
                //     title: 'My Modal',
                //     message: 'Hello world!',
                //     size: actions.Modal.Size.small,
                // };
                // const myModal = actions.Modal.create(app, modalOptions);
                // myModal.dispatch(actions.Modal.Action.OPEN);
                --}}
                function showToast(msg,isError,subscribeFun,clearFun){
                    isError = typeof isError == 'undefined' ? false : true
                    let toastOptions = {
                        message: msg,
                        duration: 5000,
                        isError: isError,
                    };
                    const toastNotice = actions.Toast.create(app, toastOptions);
                    toastNotice.subscribe(actions.Toast.Action.SHOW, data => {
                        if(typeof subscribeFun != 'undefined'){
                            subscribeFun()
                        }
                    });
                    toastNotice.subscribe(actions.Toast.Action.CLEAR, data => {
                        if(typeof clearFun != 'undefined'){
                            clearFun()
                        }
                    });
                    toastNotice.dispatch(actions.Toast.Action.SHOW);
                }
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
        @else
          <script>
              function showToast(msg,isError,subscribeFun,clearFun){
                alert(msg);
                if(typeof subscribeFun != 'undefined'){
                    subscribeFun()
                }
                if(typeof clearFun != 'undefined'){
                    clearFun()
                }
              }
          </script>
        @endif
        @yield('scripts')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                // Get all elements with the class "clickable"
                const clickableElements = document.querySelectorAll('.vue_link');
                // Add a click event listener to each clickable element
                clickableElements.forEach(function (element) {
                    element.addEventListener('click', function (event) {
                        // Handle the click event here
                        // console.log(window.apps,window.router,element.getAttribute('href'));
                        event.preventDefault();
                        return window.$GLOBALS.push(element.getAttribute('href'));
                        // You can perform any action or call a function here when the element is clicked
                    });
                });
            });
        </script>
    </body>
</html>
