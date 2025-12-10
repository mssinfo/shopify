@php
// Reuse the PayU popup-close template so all payment providers show the same UX
@endphp
@include('msdev2::payu.popup-close', ['status' => $status ?? null])
