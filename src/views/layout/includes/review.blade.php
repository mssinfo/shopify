@if (!Auth::check())
@php
	$showReview = false;
	try { $shop = mShop(); } catch (\Throwable $e) { $shop = null; }
	if ($shop) {
		try {
			$nextMeta = null;
			try { $nextMeta = $shop->meta('request-review'); } catch (\Throwable $_) { $nextMeta = null; }
			if (!$nextMeta) {
				$usedDays = 0;
				try { $usedDays = (int) $shop->appUsedDay(); } catch (\Throwable $_) { $usedDays = 0; }
				if ($usedDays >= 6) {
					$showReview = true;
				}
			}
		} catch (\Throwable $e) { /* ignore */ }

		$next = null;
		try { $next = $shop->meta('request-review'); } catch (\Throwable $e) { $next = null; }
		if (!$showReview && $next) {
			try { $showReview = (\Carbon\Carbon::parse($next)->lte(\Carbon\Carbon::now())); } catch (\Throwable $e) { $showReview = false; }
		}
        if ($showReview) {
            $shop->meta('request-review', \Carbon\Carbon::now()->addDays(30)->toDateTimeString());
        }
	}
@endphp
<script>
(async ()=>{
    if (window.shopify && shopify.scopes && typeof shopify.scopes.query === 'function') {
        const required = {!! json_encode(array_values(array_filter(array_map('trim', explode(',', config('msdev2.scopes')))))) !!};
        const res = await shopify.scopes.query();
        const granted = res?.granted ?? res ?? [];
        const missing = required.filter(s => !granted.includes(s));
        if (missing.length) {
            await shopify.scopes.request(missing);
        }
    }
@if($showReview)
    const result = await shopify.reviews.request();
    const payload = { success: !!result.success, shop: '{{ $shop->shop }}' };
    await fetch("{{ route('msdev2.shopify.review.mark') }}", {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify(payload),
        keepalive: true
    }).catch(()=>{
        const blob = new Blob([JSON.stringify(payload)], { type: 'application/json' });
        navigator.sendBeacon("{{ route('msdev2.shopify.review.mark') }}", blob);
    });
@endif
})();
</script>
@endif