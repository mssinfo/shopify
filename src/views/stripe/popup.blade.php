<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Secure Payment</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        :root{--primary:#556ee6;--muted:#6b7280}
        html,body{height:100%;margin:0}
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; background:#f6f7fb; display:flex; align-items:center; justify-content:center; padding:24px; }
        .card { width:100%; max-width:540px; background:#fff; border-radius:12px; box-shadow:0 6px 24px rgba(16,24,40,.08); padding:20px; }
        .row { margin-bottom:14px; }
        label{display:block;color:var(--muted);font-size:13px;margin-bottom:6px}
        input[type=text], input[type=email]{width:95%; padding:10px 12px; border:1px solid #e6e9ef; border-radius:8px; font-size:15px}
        #card-element { padding:12px; border:1px solid #e6e9ef; border-radius:8px; background:#fbfdff }
        .btn { background:var(--primary); color:#fff; padding:10px 14px; border-radius:8px; border:none; cursor:pointer; font-weight:600 }
        .btn[disabled]{ opacity:0.6; cursor:not-allowed }
        #errors { color:#b00020; margin-top:8px; min-height:20px }
        .meta { color:var(--muted); font-size:14px; margin-bottom:10px }
        .actions { display:flex; align-items:center }
        .cancel { background:#e6e6e9; color:#222; margin-left:10px }

        /* success overlay */
        .success-wrap{ position:fixed; inset:0; display:flex; align-items:center; justify-content:center; background:rgba(8,10,15,.5); visibility:hidden; opacity:0; transition:opacity .18s ease, visibility .18s; }
        .success-card{ background:#fff; padding:20px 26px; border-radius:12px; text-align:center; box-shadow:0 8px 30px rgba(2,6,23,.2) }
        .success-card h2{ margin:0 0 6px; font-size:18px }
        .success-card p{ margin:0; color:var(--muted) }
        .success-wrap.show{ visibility:visible; opacity:1 }
    </style>
</head>
<body>
<div class="card">
    <h3>Complete Payment</h3>
    <p>Credits: <strong id="qtyDisplay">{{ $qty }}</strong> — Amount: <strong id="amountDisplay">{{ ($displayCurrency ?? 'USD') === 'INR' ? '₹' : '$' }}{{ number_format($displayCost ?? $cost,2) }}</strong></p>

    <div class="row">
        <label>Full name</label>
        <input type="text" id="buyerName" value="{{ $name ?? ($shop->detail['name'] ?? '') }}" />
    </div>
    <div class="row">
        <label>Email</label>
        <input type="email" id="buyerEmail" value="{{ $email ?? ($shop->detail['email'] ?? '') }}" />
    </div>

    <div class="row">
        <label>Card</label>
        <div id="card-element"></div>
        <div id="errors"></div>
    </div>

    <div class="row actions">
        <button id="payBtn" class="btn">Pay {{ ($displayCurrency ?? 'USD') === 'INR' ? '₹' : '$' }}<span id="amountSpan">{{ number_format($displayCost ?? $cost,2) }}</span></button>
        <button id="cancelBtn" class="btn cancel">Cancel</button>
    </div>
</div>

<div id="successWrap" class="success-wrap" aria-hidden="true">
    <div class="success-card">
        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" style="margin-bottom:12px"><path d="M12 22C17.5228 22 22 17.5228 22 12C22 6.47715 17.5228 2 12 2C6.47715 2 2 6.47715 2 12C2 17.5228 6.47715 22 12 22Z" fill="#e6faf1"/><path d="M16.2 9.2L10.8 14.6L7.8 11.6" stroke="#12b76a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>
        <h2>Payment successful</h2>
        <p>Thank you — your credits will be added shortly.</p>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
    const publishableKey = "{{ config('msdev2.stripe.publishable') ?: env('STRIPE_KEY') }}";
    const payBtn = document.getElementById('payBtn');
    const cancelBtn = document.getElementById('cancelBtn');
    const errors = document.getElementById('errors');
    const successWrap = document.getElementById('successWrap');
    const qty = {{ (int)$qty }};
    const cost = {{ number_format($displayCost ?? $cost,2,'.','') }};
    const displayCurrency = {!! json_encode($displayCurrency ?? 'USD') !!};

    let stripe = Stripe(publishableKey);
    let elements = stripe.elements();
    let card = elements.create('card');
    card.mount('#card-element');
    card.on('change', function(event){ errors.textContent = event.error ? event.error.message : ''; });

    let clientSecret = null;
    const signedQuery = {!! json_encode($signed_query ?? '') !!};
    const createIntentRoute = signedQuery ? "{{ route('msdev2.stripe.create_intent_public') }}" : "{{ route('msdev2.stripe.create_intent') }}";

    async function createIntent() {
        const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        const body = { qty: qty, cost: cost, name: document.getElementById('buyerName').value, email: document.getElementById('buyerEmail').value };
        // If this popup was served via a signed URL, include the signed query so the public endpoint can validate it
        if (signedQuery) body.signed_query = signedQuery;
        const resp = await fetch(createIntentRoute, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
            body: JSON.stringify(body)
        });
        let j = {};
        try {
            j = await resp.json();
            console.log('create_intent response (json)', j);
        } catch (e) {
            // read text body for debugging
            let bodyText = '';
            try { bodyText = await resp.text(); } catch (t) { bodyText = '<unreadable response>'; }
            console.error('Invalid JSON from create_intent', e, 'status=', resp.status, 'body=', bodyText);
            errors.textContent = 'Failed to initialize payment: invalid server response (status ' + resp.status + ')';
            // show a snippet of the response to help debugging (not full dump)
            try { errors.textContent += ': ' + bodyText.slice(0, 300); } catch (e) {}
            payBtn.disabled = true;
            return;
        }
        if (!j.success) {
            // show error and any debug info the server returned
            let msg = j.error || 'unknown';
            if (j.debug) {
                try { msg += ' — ' + JSON.stringify(j.debug); } catch (e) { /* ignore */ }
            }
            errors.textContent = 'Failed to initialize payment: ' + msg;
            payBtn.disabled = true;
            return;
        }
        clientSecret = j.client_secret;
    }

    payBtn.addEventListener('click', async function(){
        payBtn.disabled = true;
        errors.textContent = '';
        // ensure we have a clientSecret; if not, try to initialize now
        if (!clientSecret) {
            errors.textContent = 'Initializing payment...';
            const ok = await (async function(){
                try {
                    await createIntent();
                    return !!clientSecret;
                } catch(e){
                    return false;
                }
            })();
            if (!ok) {
                errors.textContent = 'Payment not initialized. Please check your inputs and try again.';
                payBtn.disabled = false;
                return;
            }
        }

        const name = document.getElementById('buyerName').value || '';
        const email = document.getElementById('buyerEmail').value || '';

        const res = await stripe.confirmCardPayment(clientSecret, {
            payment_method: {
                card: card,
                billing_details: { name: name, email: email }
            }
        }).catch(function(e){
            return { error: { message: e && e.message ? e.message : 'Stripe confirm error' } };
        });

        if (res.error) {
            // show detailed server/client error when available
            errors.textContent = res.error.message || 'Payment failed.';
            payBtn.disabled = false;
            return;
        }

        const pi = res.paymentIntent;
        if (pi && (pi.status === 'succeeded' || pi.status === 'requires_capture')) {
            // Notify server to finalize
            try {
                const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                await fetch("{{ route('msdev2.credits.stripe_confirm') }}", {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token },
                    body: JSON.stringify({ payment_intent_id: pi.id })
                });
            } catch (e) {
                // ignore
            }

            // show success overlay briefly, then notify opener and close
            try {
                successWrap.classList.add('show');
                // small delay so user sees success
                setTimeout(function(){
                    try { window.opener.postMessage({ payu: 'success' }, '*'); } catch (e) {}
                    window.close();
                }, 1400);
            } catch (e) {
                try { window.opener.postMessage({ payu: 'success' }, '*'); } catch (e) {}
                window.close();
            }
            return;
        }

        errors.textContent = 'Payment not completed (status: ' + (pi ? pi.status : 'unknown') + ').';
        payBtn.disabled = false;
    });

    cancelBtn.addEventListener('click', function(){ try { window.opener.postMessage({ payu: 'failed' }, '*'); } catch(e){} window.close(); });

    // initialize when page loads
    (async function(){
        if (!publishableKey) {
            errors.textContent = 'Stripe publishable key not configured.';
            payBtn.disabled = true;
            return;
        }
        await createIntent();
    })();
</script>
</body>
</html>
