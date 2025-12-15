@if (config('msdev2.menu.list'))
    <nav>
        <button class="menu-toggle" aria-expanded="false" aria-controls="main-nav" aria-label="Toggle menu">☰</button>
        @if (config('msdev2.menu.logo'))
            <a class="logo" href="#">
            @if (config('msdev2.menu.logo.type') == "image")
                {!! config('msdev2.menu.logo.value') !!}
            @else
                <img src="{{ config('msdev2.menu.logo.value') }}" class="img">
            @endif
            </a>
        @endif
        <div id="main-nav" class="menu-list">
        @foreach (config('msdev2.menu.list') as $menu)
            @if (!isset($menu['position']) || $menu['position'] == "all" || $menu['position'] == "topbar")
                @php
                    // normalize current request path and menu destination to compare without leading/trailing slashes
                    $currentPath = trim(Request::path(), '/');
                    $menuDestination = '';
                    if (isset($menu['destination'])) {
                        // destination may be a full url or a path like '/gallery'
                        $menuDestination = trim(parse_url($menu['destination'], PHP_URL_PATH) ?: $menu['destination'], '/');
                    }
                    // If menu uses laravel route helper, convert it as well
                    if (isset($menu['type']) && $menu['type'] !== 'vue') {
                        $menuDestination = trim(parse_url(mRoute($menu['destination']), PHP_URL_PATH) ?: mRoute($menu['destination']), '/');
                    }
                    $isActive = ($currentPath === $menuDestination) || ($menuDestination !== '' && \Illuminate\Support\Str::startsWith($currentPath, $menuDestination . '/'));
                @endphp
                @php
                    $href = isset($menu['type']) && $menu['type']=='vue' ? $menu['destination'] : mRoute($menu['destination']);
                    $parsedHost = parse_url($href, PHP_URL_HOST) ?: null;
                    $currentHost = request()->getHost();
                    $isExternal = $parsedHost && $parsedHost !== $currentHost;
                    $extraAttrs = $isExternal ? ' target="_blank" rel="noopener noreferrer" data-external-host="' . e($parsedHost) . '"' : '';
                @endphp
                <a href="{!! $href !!}"{!! $extraAttrs !!} class="{{ $isActive ? 'active' : '' }} {{ preg_replace('/[^a-z0-9]+/i', '-', strtolower($menu['label'])) }} menu {{  isset($menu['type']) && $menu['type']=='vue' ? 'vue_link' : ''}}">
                @if ($menu['icon'])
                    {!! $menu['icon'] !!}
                @endif
                {{$menu['label']}}</a>
            @endif
        @endforeach
    </div>
    @if (config('msdev2.billing'))
        @php
            $planPath = trim(parse_url(mRoute('msdev2.shopify.plan.index'), PHP_URL_PATH) ?: mRoute('msdev2.shopify.plan.index'), '/');
            $currentPath = trim(Request::path(), '/');
            $planActive = ($currentPath === $planPath) || ($planPath !== '' && \Illuminate\Support\Str::startsWith($currentPath, $planPath . '/'));
        @endphp
        <a class="right menu {{ $planActive ? 'active' : '' }}" href="{!!mRoute('msdev2.shopify.plan.index')!!}"><i class="icon-payment"></i> Plan</a>
    @endif
    </nav>
@endif