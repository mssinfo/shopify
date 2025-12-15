
@php
// Determine whether to load Tawk
$chatEnabled = true;

if (isset($disableChat) && $disableChat) {
    $chatEnabled = false;
}

$disableChatSection = trim($__env->yieldContent('disable_chat'));
if ($disableChatSection !== '') {
    $chatEnabled = ! in_array(strtolower($disableChatSection), ['1','true','yes','on']);
}

// Disable Tawk when the app is embedded inside Shopify iframe
// or when embedded-app redirect is explicitly enabled.
if (\Msdev2\Shopify\Utils::shouldRedirectToEmbeddedApp() || request()->header('sec-fetch-dest') === 'iframe') {
    $chatEnabled = false;
}
@endphp
@if (config('msdev2.tawk_url') )
<script type="text/javascript">
    window.Tawk_API = window.Tawk_API || {};
    window.Tawk_LoadStart = new Date();

    // ✅ visitor data that MUST be stored inside Tawk
    const __tawkVisitorAttributes = {
        name: "{{ data_get($shop, 'detail.name', '') }} - {{ data_get($shop, 'detail.shop_owner', '') }}",                // ✅ shows as visitor name (notification)
        email: "{{ data_get($shop, 'detail.email', '') }}",
        // custom fields
        shop: "https://{{ data_get($shop, 'detail.myshopify_domain', '') }}",
        // plan_name: "{{ data_get($shop, 'activeCharge.name', 'FREE') }}",
        // plan_display_name: "{{ data_get($shop, 'detail.plan_display_name', '') }}",
        app: "({{ data_get($shop, 'activeCharge.name', 'FREE') }}) {{ config('app.name') }}",
        referrer: "Shopify Plan: {{ data_get($shop, 'detail.plan_display_name', '') }}",
    };
    @if (!empty(data_get($shop, 'detail.phone')))
        __tawkVisitorAttributes.phone = "{{ data_get($shop, 'detail.phone', '') }}";
    @endif
        

    // ✅ Safe loader – retries until Tawk API becomes available
    function __tawkApplyVisitor() {
        if (!window.Tawk_API || !window.Tawk_API.setAttributes) {
            console.warn("Tawk not ready yet… retrying");
            return setTimeout(__tawkApplyVisitor, 3000);
        }
        window.Tawk_API.setAttributes(__tawkVisitorAttributes, function(err){
            console.log("Tawk setAttributes error", err);
        });
        @if ($chatEnabled && isset($shop) && $shop)
        window.Tawk_API.showWidget();
        @endif
        // ✅ Event for admins
        try {
                if(!localStorage.getItem('is_visited{{ $shop->id ?? '' }}') === '1'){
                localStorage.setItem('is_visited{{ $shop->id ?? '' }}', '1');
                window.Tawk_API.addEvent('ShopVisit', {
                    details: `🛍️ Shop: {{ data_get($shop, 'detail.myshopify_domain', '') }}
                        👤 Name: {{ data_get($shop, 'detail.name', '') }}
                        👤 Owner: {{ data_get($shop, 'detail.shop_owner', '') }}
                        📧 Email: {{ data_get($shop, 'detail.email', '') }}
                        📱 Phone: {{ data_get($shop, 'detail.phone', 'N/A') }}
                        🏷️ App Charge Name: {{ data_get($shop, 'activeCharge.name', 'FREE') }}
                        🏷️ Shopify Plan: {{ data_get($shop, 'detail.plan_display_name', '') }} ({{ data_get($shop, 'detail.plan_name', '') }})
                        🏪 Store Name: {{ data_get($shop, 'detail.name', '') }}
                        📍 Referrer: ${document.title || 'Direct'}`
                }, function(err){
                    console.log("✅ User details sent to Tawk Admin",err);
                });
            }
        } catch(e) {
            console.warn('Tawk.addEvent threw an error, continuing', e);
        }

        // ✅ Start chat (optional)
        try { window.Tawk_API.start(); } catch(e){}
    }

    // ✅ run once Tawk is loaded
    window.Tawk_API.onLoad = function() {
        console.log("✅ Tawk Loaded");
        __tawkApplyVisitor();
        
    };

    // ✅ Tawk embed script
    (function(){
        var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
        s1.async=true;
        s1.src='{{ config("msdev2.tawk_url") }}';
        s1.charset='UTF-8';
        s1.setAttribute('crossorigin','*');
        s0.parentNode.insertBefore(s1,s0);
    })();
    </script>
@elseif (config('msdev2.tidio_url'))
<script src="{{ config('msdev2.tidio_url') }}" async></script>   
<script>
    document.addEventListener("tidioChat-ready", function(){
    @if (isset($shop) && $shop)
        tidioChatApi.setVisitorData({
            distinct_id: "{{ $shop->id }}",
            name: "{{ data_get($shop, 'detail.name', '') }} - {{ data_get($shop, 'detail.shop_owner', '') }}",
            email: "{{ data_get($shop, 'detail.email', '') }}",
            phone: "{{ data_get($shop, 'detail.phone', '') }}",
            city: "{{ data_get($shop, 'detail.city', '') }}",
            country: "{{ data_get($shop, 'detail.country_code', '') }}",
            tags: ["shopify", "plan-{{ data_get($shop, 'activeCharge.name', 'FREE') }}", "shop-{{ data_get($shop, 'detail.plan_display_name', '') }}","https://{{ data_get($shop, 'detail.myshopify_domain', '') }}"],
        });
        
    @endif
    @if (!$chatEnabled)
        tidioChatApi.on("ready", function(){
            tidioChatApi.hide();
        });
    @endif    
     console.log("✅ Tidio Chat Loaded");
    });
 </script>   
@endif