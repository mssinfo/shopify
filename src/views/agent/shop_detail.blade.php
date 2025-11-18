@extends('msdev2::layout.agent')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        @if(empty($shopDetail ?? null))
            <h2 class="mb-0">Shop Detail</h2>
            <div class="text-danger">Shop not found.</div>
        @else
            <h2 class="mb-0">Shop Detail: <a target="_blank" href="https://{{ $shopDetail->shop }}">🔗 {{ $shopDetail->shop }}</a></h2>
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
                    @php
                        $isUn = ((int)($shopDetail->is_uninstalled ?? 0) === 1) || !empty($shopDetail->deleted_at);
                        $unAt = $shopDetail->uninstalled_at ?? $shopDetail->deleted_at ?? null;
                    @endphp
                    <p><strong>Status:</strong> @if($isUn)<span class="badge bg-danger">Uninstalled</span>@else<span class="badge bg-success">Installed</span>@endif</p>
                    @if($unAt)
                        <p><strong>Uninstalled On:</strong> {{ \Carbon\Carbon::parse($unAt)->toDayDateTimeString() }}</p>
                    @endif
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
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>Metadata</div>
                <button class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#addMetaForm">Add / Edit</button>
            </div>
            <div class="card-body">
                <div class="collapse mb-3" id="addMetaForm">
                    <form method="post" action="{{ route('msdev2.agent.shops.metadata.update', ['id' => $shopDetail->id]) }}">
                        @csrf
                        <div class="row g-2">
                            <div class="col-5"><input name="key" class="form-control form-control-sm" placeholder="Key"></div>
                            <div class="col-5"><input name="value" class="form-control form-control-sm" placeholder="Value"></div>
                            <div class="col-2"><button class="btn btn-sm btn-success" type="submit">Save</button></div>
                        </div>
                    </form>
                </div>
                @if($metadata->count())
                    <div class="list-group list-group-flush">
                        @foreach($metadata as $m)
                            <div class="list-group-item d-flex justify-content-between align-items-start">
                                <div>
                                    <div class="fw-bold">{{ $m->key }}</div>
                                    <div class="small text-muted">
                                    {{ nl2br(($m->value)) }}</div>
                                </div>
                                <div class="text-end">
                                    <form method="post" action="{{ route('msdev2.agent.shops.metadata.update', ['id' => $shopDetail->id]) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="key" value="{{ $m->key }}">
                                        <input type="hidden" name="value" value="{{ $m->value }}">
                                        <button class="btn btn-sm btn-outline-secondary" type="submit">Edit</button>
                                    </form>
                                    <form method="post" action="{{ route('msdev2.agent.shops.metadata.delete', ['id' => $shopDetail->id, 'key' => $m->key]) }}" class="d-inline" onsubmit="return confirm('Delete metadata {{ $m->key }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-danger ms-1" type="submit">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-muted">No metadata available.</div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Quick Info</div>
            <div class="card-body small">
                    <p><strong>Session ID:</strong> {{ $shopDetail->session_id ?? 'N/A' }} @if(!empty($shopDetail->session_id)) <button class="btn btn-sm btn-outline-secondary ms-2 copy-field" data-value="{{ e($shopDetail->session_id) }}">Copy</button>@endif</p>
                    <p><strong>Access Token:</strong>
                        @if(isset($shopDetail->access_token) && $shopDetail->access_token)
                            <span>Stored</span>
                            <button class="btn btn-sm btn-outline-secondary ms-2 copy-field" data-value="{{ e($shopDetail->access_token) }}">Copy Token</button>
                        @else
                            <span>Not stored</span>
                        @endif
                    </p>
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