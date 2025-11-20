@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">

    <!-- GLOBAL SEARCH (Keep as is) -->
    <div class="row mb-4">
        <div class="col-md-6 mx-auto position-relative">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="globalSearch" class="form-control border-start-0" placeholder="Search Shop, Owner, or Domain..." autocomplete="off">
            </div>
            <div id="searchResults" class="list-group position-absolute w-100 shadow" style="z-index: 1000; display: none; top: 45px;"></div>
        </div>
    </div>

    <!-- KPI CARDS (Keep as is) -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total MRR</small>
                    <h2 class="mb-0 fw-bold text-dark">${{ number_format($totalMrr, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Active Shops</small>
                    <h2 class="mb-0 fw-bold text-success">{{ $activeShopsCount }}</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Conversion Rate</small>
                    <h2 class="mb-0 fw-bold text-primary">{{ number_format($conversionRate, 1) }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Tickets</small>
                    <div class="d-flex align-items-end">
                        <h2 class="mb-0 fw-bold text-danger">{{ $ticketsPending }}</h2>
                        <span class="mx-2 text-muted">/</span>
                        <h4 class="mb-0 text-success">{{ $ticketsResolved }}</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- PLAN SUMMARY LINE (Keep as is) -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body py-3 d-flex flex-wrap gap-3 align-items-center">
            <span class="fw-bold text-muted me-2">Plan Summary:</span>
            @foreach($planSummary as $summary)
                <span class="badge bg-info text-dark px-3 py-2 rounded-pill border border-info">
                    {{ $summary->name }}: <strong>{{ $summary->count }}</strong>
                </span>
            @endforeach
            <span class="badge bg-secondary bg-opacity-25 text-dark px-3 py-2 rounded-pill border">
                Free / No Plan: <strong>{{ $freeShopsCount }}</strong>
            </span>
        </div>
    </div>

    <!-- ============================
       UPDATED: GROUPED SHOP LISTS (RICH DETAIL)
    ============================= -->
    <div class="row g-4">
        @foreach($groupedShops as $planName => $shops)
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 d-flex justify-content-between align-items-center border-bottom-0">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-layer-group text-primary me-2"></i> {{ $planName }}
                        <span class="badge bg-light text-dark ms-2 border">{{ count($shops) }} (Recent 10)</span>
                    </span>
                    <a href="{{ route('admin.shops', ['plan' => $planName == 'Free / Trial / No Plan' ? 'freemium' : $planName]) }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 w-50">Shop Details</th>
                                <th>Owner / Country</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shops as $shop)
                                @php
                                    // Safely decode JSON
                                    $details = [];
                                    if(!empty($shop->detail)) {
                                        $details = is_string($shop->detail) ? json_decode($shop->detail, true) : $shop->detail;
                                    }
                                @endphp
                            <tr>
                                <td class="ps-3">
                                    <!-- Store Name -->
                                    <div class="fw-bold text-dark text-break" style="max-width: 200px;">
                                        {{ $details['name'] ?? $shop->shop }}
                                    </div>
                                    <!-- MyShopify Domain -->
                                    <div class="small text-muted">{{ $shop->shop }}</div>
                                    <!-- Custom Domain (if different) -->
                                    @if(isset($details['domain']) && $details['domain'] !== $shop->shop)
                                        <div class="small text-primary"><i class="fas fa-globe me-1"></i> {{ $details['domain'] }}</div>
                                    @endif
                                </td>
                                <td>
                                    <!-- Owner Name -->
                                    <div class="small fw-bold">
                                        <i class="fas fa-user-circle text-secondary me-1"></i> {{ $details['shop_owner'] ?? 'Unknown Owner' }}
                                    </div>
                                    <!-- Email -->
                                    <div class="small text-muted text-truncate" style="max-width: 150px;" title="{{ $details['email'] ?? '' }}">
                                        {{ $details['email'] ?? '' }}
                                    </div>
                                    <!-- Country -->
                                    @if(isset($details['country_name']))
                                        <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.7rem;">
                                            {{ $details['country_name'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.shops.login', $shop->id) }}" target="_blank" class="btn btn-outline-primary" title="Login" data-bs-toggle="tooltip"><i class="fas fa-sign-in-alt"></i></a>
                                        <a href="{{ route('admin.shops.show', $shop->id) }}" class="btn btn-outline-dark" title="Details"><i class="fas fa-eye"></i></a>
                                        <a href="https://{{ $shop->domain ?? $shop->shop }}" target="_blank" class="btn btn-outline-secondary" title="Visit Site"><i class="fas fa-external-link-alt"></i></a>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No shops in this plan recently.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- UNINSTALLED & TICKETS ROW (Keep as is) -->
    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-danger border-bottom-0">
                    <i class="fas fa-trash-alt me-2"></i> Recently Uninstalled (Inactive)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 w-50">Shop</th>
                                <th>Uninstalled Date</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uninstalledShops as $shop)
                            <tr>
                                <td class="ps-3 fw-bold text-secondary">{{ $shop->shop }}</td>
                                <td class="text-muted small">{{ $shop->updated_at->format('M d, Y') }}</td>
                                <td class="text-end pe-3">
                                    <a href="{{ route('admin.shops.show', $shop->id) }}" class="btn btn-sm btn-outline-secondary">View</a>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="3" class="text-center text-muted py-3">No recent uninstalls.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white fw-bold py-3 text-warning border-bottom-0 d-flex justify-content-between">
                    <span><i class="fas fa-envelope-open-text me-2"></i> Recent Open Tickets</span>
                    <a href="{{ route('admin.tickets') }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Subject / Shop</th>
                                <th class="text-end pe-3">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($recentOpenTickets as $ticket)
                            <tr style="cursor: pointer;" onclick="window.location='{{ route('admin.tickets.show', $ticket->id) }}'">
                                <td class="ps-3">
                                    <div class="fw-bold text-dark">{{ Str::limit($ticket->subject, 30) }}</div>
                                    <small class="text-muted">{{ $ticket->shop->shop ?? 'Unknown' }}</small>
                                </td>
                                <td class="text-end pe-3 small text-muted">
                                    {{ $ticket->created_at->diffForHumans(null, true) }}
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="2" class="text-center text-muted py-3">No open tickets.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
    // --- Autocomplete Search Script ---
    const searchInput = document.getElementById('globalSearch');
    const resultsBox = document.getElementById('searchResults');
    let timeout = null;

    searchInput.addEventListener('input', function (e) {
        clearTimeout(timeout);
        const query = e.target.value;
        
        if (query.length < 2) {
            resultsBox.style.display = 'none';
            return;
        }

        timeout = setTimeout(() => {
            fetch("{{ route('admin.api.shops.search') }}?q=" + query)
                .then(response => response.json())
                .then(data => {
                    resultsBox.innerHTML = '';
                    if (data.length > 0) {
                        data.forEach(shop => {
                            const item = document.createElement('a');
                            item.href = "/admin/shops/" + shop.id; 
                            item.className = "list-group-item list-group-item-action d-flex justify-content-between align-items-center";
                            item.innerHTML = `
                                <div>
                                    <div class="fw-bold text-dark">${shop.shop}</div>
                                    <small class="text-muted" style="font-size:0.75rem">${shop.domain ?? ''}</small>
                                </div>
                                <i class="fas fa-chevron-right text-muted"></i>
                            `;
                            resultsBox.appendChild(item);
                        });
                        resultsBox.style.display = 'block';
                    } else {
                        resultsBox.style.display = 'none';
                    }
                });
        }, 300);
    });

    // Close search on outside click
    document.addEventListener('click', function(e) {
        if (!searchInput.contains(e.target) && !resultsBox.contains(e.target)) {
            resultsBox.style.display = 'none';
        }
    });
</script>
@endpush
@endsection