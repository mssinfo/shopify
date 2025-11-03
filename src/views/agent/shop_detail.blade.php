@extends('msdev2::layout.agent')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        @if(empty($shopDetail ?? null))
            <h2 class="mb-0">Shop Detail</h2>
            <div class="text-danger">Shop not found.</div>
        @else
            <h2 class="mb-0">Shop Detail: {{ $shopDetail->shop }}</h2>
            <div class="text-muted">Domain: {{ $shopDetail->domain ?? 'N/A' }} &middot; ID: {{ $shopDetail->id }}</div>
        @endif
    </div>
    <div>
            @php
                $d = $shopDetail->domain ?? $shopDetail->shop ?? null;
            if (!$d) {
                $adminUrl = '#';
            } else {
                if (preg_match('#^https?://#i', $d)) {
                        $adminUrl = rtrim($d, '/') . '/admin';
                } else {
                    $adminUrl = 'https://' . trim($d, '/') . '/admin';
                }
            }
        @endphp
         <a target="_blank" class="btn btn-outline-primary" href="{{ route('msdev2.agent.shops.direct', ['id' => $shopDetail->id]) }}">Direct login in shop</a>
        <a class="btn btn-info" href="#" data-bs-toggle="modal" data-bs-target="#shopDetailModal">Details</a>
        <a class="btn btn-secondary" href="{{ route('msdev2.agent.dashboard') }}">Back</a>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        @if(empty($shopDetail ?? null))
            <div class="alert alert-warning">No shop details to show.</div>
        @else
            <div class="card mb-3">
                <div class="card-header">Overview</div>
                <div class="card-body">
                    <p><strong>Shop:</strong> {{ $shopDetail->shop }}</p>
                    <p><strong>Domain:</strong> {{ $shopDetail->domain ?? 'N/A' }}</p>
                    <p><strong>Is Online:</strong> {{ $shopDetail->is_online ? 'Yes' : 'No' }}</p>
                    <p><strong>Installed On:</strong> {{ $shopDetail->created_at?->toDayDateTimeString() ?? 'N/A' }}</p>
                    <p><strong>Active Plan:</strong> {{ $shopDetail->activeCharge?->name ?? 'N/A' }}</p>
                </div>
            </div>

                <div class="card mb-3">
                <div class="card-header">Plan / Purchase History</div>
                <div class="card-body">
                    @if(isset($charges) && $charges->count())
                        <div class="list-group">
                            @foreach($charges as $c)
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between">
                                        <div>
                                            <strong>{{ $c->name }}</strong>
                                            <div class="text-muted small">Type: {{ $c->type }} &middot; Status: {{ $c->status }}</div>
                                        </div>
                                        <div class="text-end small">
                                            <div>Price: {{ $c->price }}</div>
                                            <div>Activated: {{ $c->activated_on ? (\Carbon\Carbon::parse($c->activated_on)->toDayDateTimeString()) : 'N/A' }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-muted">No charge history found.</div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">Metadata</div>
            <div class="card-body">
                @if($metadata->count())
                    <table class="table table-sm">
                        <thead><tr><th>Key</th><th>Value</th></tr></thead>
                        <tbody>
                            @foreach($metadata as $m)
                                <tr><td>{{ $m->key }}</td><td><pre style="margin:0">{{ $m->value }}</pre></td></tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="text-muted">No metadata available.</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Quick Info</div>
            <div class="card-body small">
                <p><strong>Session ID:</strong> {{ $shopDetail->session_id ?? 'N/A' }}</p>
                <p><strong>Access Token:</strong> {{ isset($shopDetail->access_token) ? 'Stored' : 'Not stored' }}</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal to show raw shop detail -->
<div class="modal fade" id="shopDetailModal" tabindex="-1" aria-labelledby="shopDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="shopDetailModalLabel">Shop Raw Detail</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <pre id="shopDetailJson" style="white-space:pre-wrap;word-break:break-word;background:#f8f9fa;padding:12px;border-radius:6px;">{{ json_encode($shopDetail->detail ?? [], JSON_PRETTY_PRINT) }}</pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection
