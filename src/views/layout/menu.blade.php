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
                @if(isset($menu['type']) && $menu['type'] == "vue")
                @else
                <a href="{{ isset($menu['type']) && $menu['type']=='laravel' ? Msdev2\Shopify\Utils::getUrl(route($menu['destination'])) : Msdev2\Shopify\Utils::getUrl($menu['destination']) }}" class="@if(Request::path() == $menu['destination']) active @endif menu">
                    @if ($menu['icon'])
                        {!! $menu['icon'] !!}
                    @endif
                    {{$menu['label']}}</a>
                @endif
            @endif
        @endforeach
        @if (config('msdev2.billing'))
            <a class="right" href="{{Msdev2\Shopify\Utils::getUrl(route('msdev2.shopify.plan.index'))}}"><i class="icon-payment"></i> Plan</a>
        @endif
    </nav>

@endif
