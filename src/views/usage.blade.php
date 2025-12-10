@extends('msdev2::layout.master')
@section('css', 'usage')
@section('content')


 @include('msdev2::components.usage-bar', [
        'stats' => $stats
    ])

<!-- Buy Credits Modal -->
<div id="buyModal" class="modal-bg">
    <div class="modal-box">
        <span class="close-btn" onclick="closeBuyModal()">&times;</span>
        <h3 class="modal-title">Buy Credits</h3>

        <div class="modal-content">
            <div class="modal-row">
                <label class="label">Enter Credits</label>
                <input type="number" id="creditQty" class="input" placeholder="e.g., 50" value="50" min="1">
            </div>

            <div class="modal-row">
                <label class="label">Price per credit (USD)</label>
                <div class="price-display">$<span id="pricePerCreditDisplay">{{$plan["feature"]["perUnitPrice"]}}</span></div>
            </div>

            <div class="modal-row">
                <label class="label">Cost (USD)</label>
                <input type="text" id="creditCost" class="input" readonly>
            </div>

            <div class="calc-box">
                <div id="calcExpression">50 × {{$plan["feature"]["perUnitPrice"]}} = $15.00</div>
                <div id="calcNote" class="calc-note">This is the live calculation for your selected credits.</div>
            </div>

            <div class="modal-actions">
                <button id="buyNowBtn" class="buy-btn" onclick="startCreditPurchase()">Buy Now</button>
            </div>
        </div>
    </div>
</div>


<!-- Hidden PayU form -->
<form id="payuForm" target="payuPopup" method="POST"></form>
<div class="credits-box">
<div style="margin-top:20px; display:flex; justify-content:space-between; align-items:center;">
    <h3>Usage History</h3>
    <button class="buy-btn" onclick="openBuyModal()">Buy More Credits</button>
</div>
@if($history->isEmpty())
    <div class="empty-state">
        <div class="empty-state-icon">
            <svg viewBox="0 0 60 60" class="empty-state-svg">
                <path fill="#DFE3E8" fill-rule="evenodd" d="M30 58C45.464 58 58 45.464 58 30S45.464 2 30 2 2 14.536 2 30s12.536 28 28 28zm0-2c14.36 0 26-11.64 26-26S44.36 4 30 4 4 15.64 4 30s11.64 26 26 26z"></path>
                <path fill="#DFE3E8" d="M20 30a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm20 0a4 4 0 1 1 0-8 4 4 0 0 1 0 8z"></path>
                <path stroke="#DFE3E8" stroke-width="2" stroke-linecap="round" d="M20 42c3.333-2.667 6.667-4 10-4s6.667 1.333 10 4"></path>
            </svg>
        </div>
        <div class="empty-state-title">No usage history yet</div>
        <div class="empty-state-description">Your credit usage history will appear here once you start using credits.</div>
    </div>
@else
<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Type</th>
            <th>Qty</th>
            <th>Cost</th>
            <th>Reference ID</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($history as $row)
        <tr>
            <td>{{ $row->created_at }}</td>
            <td>{{ $row->type }}</td>
            <td>{{ $row->quantity }}</td>
            <td>${{ $row->cost }}</td>
            <td>{{ $row->reference_id ?? (is_array($row->meta) ? json_encode($row->meta) : $row->meta) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

<div class="pagination-wrapper">{!! $history->links('pagination::bootstrap-4') !!}</div>
</div>

<script>
    const pricePerCredit = {{$plan["feature"]["perUnitPrice"]}}; // CHANGE PRICING HERE

    function openBuyModal() {
        document.getElementById("buyModal").style.display = "flex";
        calculateCost();
        updateCalcDisplay();
    }

    function closeBuyModal() {
        document.getElementById("buyModal").style.display = "none";
    }

    function calculateCost() {
        const qtyInput = document.getElementById("creditQty");
        let qty = parseInt(qtyInput.value) || 0;
        if (qty < 1) qty = 0;
        let cost = (qty * pricePerCredit).toFixed(2);
        document.getElementById("creditCost").value = cost;
        // toggle buy button
        const buyBtn = document.getElementById('buyNowBtn');
        if (qty < 1) {
            buyBtn.disabled = true;
            buyBtn.style.opacity = 0.6;
            buyBtn.style.cursor = 'not-allowed';
        } else {
            buyBtn.disabled = false;
            buyBtn.style.opacity = 1;
            buyBtn.style.cursor = 'pointer';
        }
        updateCalcDisplay();
    }

    function updateCalcDisplay(){
        const qty = parseInt(document.getElementById("creditQty").value) || 0;
        const per = pricePerCredit.toFixed(2);
        const subtotal = (qty * pricePerCredit).toFixed(2);
        document.getElementById('pricePerCreditDisplay').innerText = per;
        document.getElementById('calcExpression').innerText = `${qty} × $${per} = $${subtotal}`;
    }

    document.getElementById("creditQty").addEventListener("input", calculateCost);


    async function startCreditPurchase() {
        let qty = parseInt(document.getElementById("creditQty").value) || 0;
        if (qty < 1) {
            $GLOBALS.showToast('Please enter a valid credits quantity', true);
            return;
        }
        let cost = document.getElementById("creditCost").value;
        // show loading state on button
        setBuyLoading(true);

        const response = await fetch("{!! mRoute('msdev2.credits.buy') !!}", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-CSRF-TOKEN": "{{ csrf_token() }}"
            },
            body: JSON.stringify({ qty, cost })
        });

        const data = await response.json();

        if (data.status === "success") {
            $GLOBALS.showToast("Credits added via Shopify Billing!", true);
            setBuyLoading(false);
            location.reload();
            return;
        }

        if (data.status === "fallback_payu") {
            if (data.stripe_error) {
                $GLOBALS.showToast('Stripe error: ' + data.stripe_error + '\nFalling back to PayU.', true);
            }
            // open PayU popup and keep loading until popup closes or posts a message
            const popup = openPayUPaymentPopup(data.form);
            monitorPopupClose(popup);
            return;
        }

        if (data.status === "fallback_stripe") {
            if (!data.popup_url) {
                $GLOBALS.showToast('Stripe checkout failed to initialize. Falling back to paypal.', true);
                setBuyLoading(false);
                return;
            }
            // Open Stripe Checkout in a popup and monitor close
            const popup = window.open(data.popup_url, 'stripePopup', 'width=900,height=700');
            monitorPopupClose(popup);
            return;
        }

        // New PaymentIntent flow: return client_secret for Stripe.js confirmation
        if (data.status === 'stripe_intent') {
            // You should integrate Stripe.js to confirm the payment using data.client_secret.
            // For now, open a small helper window that can handle client-side Stripe flows (or notify user).
            // We'll just notify and stop loading so developer can add Stripe.js integration.
            $GLOBALS.showToast('Stripe payment initialized. Integrate Stripe.js to confirm the payment using the returned client_secret.', true);
            console.info('stripe_intent', data);
            setBuyLoading(false);
            return;
        }
        if (data.status === "fallback_paypal") {
            if (data.paypal_error) {
                $GLOBALS.showToast('PayPal error: ' + data.paypal_error + '\nFalling back to PayU.', true);
            }
            if (!data.approve_url) {
                $GLOBALS.showToast('PayPal checkout failed to initialize. Falling back to PayU.', true);
                setBuyLoading(false);
                return;
            }
            const popup = window.open(data.approve_url, 'paypalPopup', 'width=900,height=700');
            monitorPopupClose(popup);
            return;
        }
    }

    // PayU Popup
    function openPayUPaymentPopup(formData) {
        let popup = window.open("", "payuPopup", "width=600,height=700");

        let form = document.getElementById("payuForm");
        form.action = formData.action;
        form.innerHTML = "";

        Object.keys(formData).forEach(key => {
            if (key === "action") return;
            form.innerHTML += `<input type="hidden" name="${key}" value="${formData[key]}">`;
        });

        form.submit();
        return popup;
    }

    // Monitor popup and remove loading when it closes
    function monitorPopupClose(popup) {
        if (!popup) {
            setBuyLoading(false);
            return;
        }
        const poll = setInterval(() => {
            try {
                if (popup.closed) {
                    clearInterval(poll);
                    setBuyLoading(false);
                }
            } catch (e) {
                // ignore cross-origin access until closed
            }
        }, 500);
    }

    function setBuyLoading(on) {
        const buyBtn = document.getElementById('buyNowBtn');
        if (on) {
            buyBtn.disabled = true;
            buyBtn.dataset.origText = buyBtn.innerHTML;
            buyBtn.innerHTML = 'Loading...';
            buyBtn.style.opacity = 0.6;
            buyBtn.style.cursor = 'not-allowed';
        } else {
            buyBtn.disabled = false;
            if (buyBtn.dataset.origText) buyBtn.innerHTML = buyBtn.dataset.origText;
            buyBtn.style.opacity = 1;
            buyBtn.style.cursor = 'pointer';
        }
    }
    window.addEventListener("message", function(event) {
        if (event.data && event.data.payu === "success") {
            location.reload();
        }
    });
</script>

@endsection
