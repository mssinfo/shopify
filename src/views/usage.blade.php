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
                <div class="price-display">$<span id="pricePerCreditDisplay">0.30</span></div>
            </div>

            <div class="modal-row">
                <label class="label">Cost (USD)</label>
                <input type="text" id="creditCost" class="input" readonly>
            </div>

            <div class="calc-box">
                <div id="calcExpression">50 × $0.30 = $15.00</div>
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
</div>
{{ $history->links() }}

<script>
    const pricePerCredit = 0.30; // CHANGE PRICING HERE

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
            alert('Please enter a valid credits quantity');
            return;
        }
        let cost = document.getElementById("creditCost").value;

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
            alert("Credits added via Shopify Billing!");
            location.reload();
            return;
        }

        if (data.status === "fallback_payu") {
            openPayUPaymentPopup(data.form);
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
    }
    window.addEventListener("message", function(event) {
        if (event.data && event.data.payu === "success") {
            location.reload();
        }
    });
</script>

@endsection
