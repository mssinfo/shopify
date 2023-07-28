@if (config('msdev2.menu.list'))
    <nav>
        @if (config('msdev2.menu.logo'))
            <a class="logo" href="#">
            @if (config('msdev2.menu.logo.type') == "image")
                {!! config('msdev2.menu.logo.value') !!}
            @else
                <img src="{{ config('msdev2.menu.logo.value') }}" class="img">
            @endif
            </a>
        @endif
        @foreach (config('msdev2.menu.list') as $menu)
            @if (!isset($menu['position']) || $menu['position'] == "all" || $menu['position'] == "topbar")
                <a href="{{ isset($menu['type']) && $menu['type']=='vue' ? $menu['destination'] : mRoute($menu['destination']) }}" class="@if(Request::path() == $menu['destination']) active @endif menu {{  isset($menu['type']) && $menu['type']=='vue' ? 'vue_link' : ''}}">
                    @if ($menu['icon'])
                        {!! $menu['icon'] !!}
                    @endif
                    {{$menu['label']}}</a>
            @endif
        @endforeach
        @if (config('msdev2.billing'))
            <a class="right" href="{{mRoute('msdev2.shopify.plan.index')}}"><i class="icon-payment"></i> Plan</a>
        @endif
    </nav>

@endif
