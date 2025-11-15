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

<script>
    (function(){
        // Normalize path: trim trailing slash, ensure leading slash for comparison
        function normalizePath(p){
            if(!p) return '/';
            try{
                // If a full URL passed, extract pathname
                var u = new URL(p, window.location.origin);
                p = u.pathname;
            }catch(e){ /* ignore */ }
            // remove trailing slash unless root
            if(p.length > 1 && p.endsWith('/')) p = p.slice(0, -1);
            return p;
        }

        function updateVueMenuActive(){
            // Select all navigational anchors inside nav (includes menu items and the Plan 'right' link)
            var links = document.querySelectorAll('nav a');
            var current = normalizePath(window.location.pathname || '/');
            links.forEach(function(a){
                // skip logo link or placeholders
                if (a.classList.contains('logo')) return;
                var href = a.getAttribute('href') || a.getAttribute('data-href') || '';
                // skip empty or javascript links
                if(!href || href.trim() === '#' || href.trim().toLowerCase().startsWith('javascript:')) return;
                // skip absolute external links (different host)
                try{
                    var testUrl = new URL(href, window.location.origin);
                    if(testUrl.origin !== window.location.origin) return;
                }catch(e){ /* ignore, href might be relative */ }

                var target = normalizePath(href);
                var isActive = (current === target) || (target !== '/' && current.indexOf(target + '/') === 0);
                if(isActive){
                    a.classList.add('active');
                } else {
                    a.classList.remove('active');
                }
            });
        }

        // dispatch event when history methods are called
        function hookHistory(){
            var push = history.pushState;
            history.pushState = function(){
                var ret = push.apply(this, arguments);
                window.dispatchEvent(new Event('locationchange'));
                return ret;
            };
            var replace = history.replaceState;
            history.replaceState = function(){
                var ret = replace.apply(this, arguments);
                window.dispatchEvent(new Event('locationchange'));
                return ret;
            };
            window.addEventListener('popstate', function(){
                window.dispatchEvent(new Event('locationchange'));
            });
        }

        if(document.readyState === 'loading'){
            document.addEventListener('DOMContentLoaded', function(){
                hookHistory();
                updateVueMenuActive();
            });
        } else {
            hookHistory();
            updateVueMenuActive();
        }

        // Also update when custom locationchange occurs
        window.addEventListener('locationchange', function(){
            // small timeout to allow router to update url
            setTimeout(updateVueMenuActive, 10);
        });

        // ensure clicks on vue links update immediately
        document.addEventListener('click', function(e){
            var a = e.target.closest && e.target.closest('a.vue_link');
            if(a){
                setTimeout(updateVueMenuActive, 10);
            }
        });

        // Ping external hosts for menu links and disable the link if unreachable
        function pingExternalLinks(){
            var links = document.querySelectorAll('nav a[data-external-host]');
            links.forEach(function(a){
                var host = a.getAttribute('data-external-host');
                if(!host) return;
                var testUrl = (location.protocol === 'https:' ? 'https://' : 'http://') + host + '/favicon.ico?ping=' + Date.now();
                var img = new Image();
                var handled = false;
                var t = setTimeout(function(){ if(handled) return; handled = true; a.setAttribute('title','External site appears to be unreachable'); a.style.opacity='0.6'; a.style.pointerEvents='none'; }, 2500);
                img.onload = function(){ if(handled) return; handled = true; clearTimeout(t); };
                img.onerror = function(){ if(handled) return; handled = true; clearTimeout(t); a.setAttribute('title','External site appears to be unreachable'); a.style.opacity='0.6'; a.style.pointerEvents='none'; };
                try{ img.src = testUrl; }catch(e){ handled = true; clearTimeout(t); a.setAttribute('title','External site appears to be unreachable'); a.style.opacity='0.6'; a.style.pointerEvents='none'; }
            });
        }
        // run once on load
        setTimeout(pingExternalLinks, 100);
    })();
</script>
