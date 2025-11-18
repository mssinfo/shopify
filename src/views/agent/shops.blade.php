@extends('msdev2::layout.agent')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="mb-0">Shops</h3>
        <div class="text-muted small">Search, filter and manage shops.</div>
    </div>
    <div>
        <a href="{{ route('msdev2.agent.dashboard') }}" class="btn btn-outline-secondary">Back to Dashboard</a>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <div class="row g-2 align-items-center">
            <div class="col-md-5">
                <input id="shops-search" type="text" class="form-control" placeholder="Search by name, domain, email, id...">
            </div>
            <div class="col-md-3">
                @if(isset($plans) && is_iterable($plans) && count($plans))
                    <select id="shops-plan" class="form-control">
                        <option value="">All plans</option>
                        @foreach($plans as $p)
                            @php
                                $label = is_object($p) ? ($p->name ?? $p->title ?? $p->id) : (is_array($p)? ($p['name'] ?? $p['title'] ?? $p['id']) : $p);
                            @endphp
                            <option value="{{ is_object($p) ? ($p->id ?? $label) : (is_array($p)? ($p['id'] ?? $label) : $p) }}">{{ $label }}</option>
                        @endforeach
                    </select>
                @else
                    <input id="shops-plan" type="text" class="form-control" placeholder="Filter by plan (name or id)">
                @endif
            </div>
            <div class="col-md-2">
                <select id="shops-status" class="form-control">
                    <option value="">All status</option>
                    <option value="installed">Installed</option>
                    <option value="uninstalled">Uninstalled</option>
                </select>
            </div>
            <div class="col-md-2 d-flex justify-content-end">
                <button id="shops-search-btn" class="btn btn-primary me-2">Search</button>
                <button id="shops-clear-btn" class="btn btn-outline-secondary">Clear</button>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover" id="shops-table">
                <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th class="d-none d-md-table-cell">Domain</th>
                                <th class="d-none d-lg-table-cell">Plan</th>
                                <th>Status</th>
                                <th class="d-none d-lg-table-cell">Uninstalled At</th>
                                <th class="text-end">Actions</th>
                            </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7" class="text-muted">Use the search box above to find shops.</td></tr>
                </tbody>
            </table>
        </div>
        <nav class="mt-3" aria-label="shops pagination">
            <ul class="pagination" id="shops-pagination"></ul>
        </nav>
    </div>
</div>
@endsection

@section('scripts')
<!-- shops JS migrated to public/msdev2/js/agent.js -->
@endsection
