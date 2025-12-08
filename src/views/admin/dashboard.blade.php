@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">

    <!-- GLOBAL SEARCH -->
    <div class="row mb-4">
        <div class="col-md-8 col-lg-6 mx-auto position-relative">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0 ps-3"><i class="fas fa-search text-muted"></i></span>
                <input type="text" id="globalSearch" class="form-control border-start-0 py-2" placeholder="Search Shop, Owner, or Domain..." autocomplete="off">
            </div>
            <div id="searchResults" class="list-group position-absolute w-100 shadow-sm rounded-3 mt-1" style="z-index: 1000; display: none;"></div>
        </div>
    </div>

    <!-- KPI CARDS -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total MRR</small>
                    <h2 class="mb-0 fw-bold text-dark">${{ number_format($totalMrr, 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Active Shops</small>
                    <h2 class="mb-0 fw-bold text-success">{{ $activeShopsCount }}</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Conversion Rate</small>
                    <h2 class="mb-0 fw-bold text-primary">{{ number_format($conversionRate, 1) }}%</h2>
                </div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card h-100">
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

    <!-- PLAN SUMMARY LINE -->
    <div class="card mb-4">
        <div class="card-body py-3 d-flex flex-wrap gap-3 align-items-center">
            <span class="fw-bold text-muted me-2">Plan Summary:</span>
            @foreach($planSummary as $summary)
                <span class="badge bg-info bg-opacity-10 text-info px-3 py-2 rounded-pill border border-info">
                    {{ $summary->name }}: <strong>{{ $summary->count }}</strong>
                </span>
            @endforeach
        </div>
    </div>

    <!-- ============================
       UPDATED: GROUPED SHOP LISTS (RICH DETAIL)
    ============================= -->
    
    <!-- RECENT INSTALLS -->
    <div class="card mb-4">
        <div class="card-header border-bottom-0">
            <i class="fas fa-history text-primary me-2"></i> Recent Installs (Last 10)
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                <thead class="bg-light">
                    <tr>
                        <th class="ps-3">Shop Details</th>
                        <th>Owner / Country</th>
                        <th>Install Date</th>
                        <th>Plan / Renewal</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($recentShops as $shop)
                        @php
                            $details = [];
                            if(!empty($shop->detail)) {
                                $details = is_string($shop->detail) ? json_decode($shop->detail, true) : $shop->detail;
                            }
                            $activeCharge = $shop->activeCharge;
                        @endphp
                        <tr>
                            <td class="ps-3">
                                <div class="fw-bold text-dark text-break" style="max-width: 200px;">
                                    {{ $details['name'] ?? $shop->shop }}
                                </div>
                                <div class="small text-muted">{{ $shop->shop }}</div>
                            </td>
                            <td>
                                <div class="small fw-bold">{{ $details['shop_owner'] ?? 'Unknown' }}</div>
                                @if(isset($details['country_name']))
                                    <span class="badge bg-light text-secondary border mt-1" style="font-size: 0.7rem;">{{ $details['country_name'] }}</span>
                                @endif
                            </td>
                            <td>
                                <div class="small text-dark">{{ $shop->created_at->format('M d, Y') }}</div>
                                <div class="small text-muted" style="font-size: 0.75rem;">{{ $shop->created_at->diffForHumans() }}</div>
                            </td>
                            <td>
                                @if($activeCharge)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success">{{ $activeCharge->name }}</span>
                                    <div class="small text-muted mt-1">
                                        @if($activeCharge->trial_ends_on && \Carbon\Carbon::parse($activeCharge->trial_ends_on)->isFuture())
                                            Trial ends: {{ \Carbon\Carbon::parse($activeCharge->trial_ends_on)->format('M d') }}
                                        @elseif($activeCharge->billing_on)
                                            Renews: {{ \Carbon\Carbon::parse($activeCharge->billing_on)->format('M d') }}
                                        @else
                                            Active
                                        @endif
                                    </div>
                                @else
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary border">No Plan</span>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('admin.shops.login', $shop->id) }}" target="_blank" class="btn btn-outline-primary" title="Login"><i class="fas fa-sign-in-alt"></i></a>
                                    <a href="{{ route('admin.shops.show', $shop->id) }}" class="btn btn-outline-dark" title="Details"><i class="fas fa-eye"></i></a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center text-muted py-3">No recent installs.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="row g-4">
        @foreach($groupedShops as $planName => $shops)
        @php
            $price = 0;
            if($shops->isNotEmpty()) {
                 $firstShop = $shops->first();
                 $charge = $firstShop->charges->where('name', $planName)->where('status', 'active')->first();
                 if($charge) $price = $charge->price;
            }
        @endphp
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center border-bottom-0">
                    <span class="d-flex align-items-center">
                        <i class="fas fa-layer-group text-primary me-2"></i> {{ $planName }} 
                        <span class="text-muted ms-1">(${{ number_format($price, 2) }})</span>
                        <span class="badge bg-light text-dark ms-2 border">{{ count($shops) }}</span>
                    </span>
                    <a href="{{ route('admin.shops', ['plan' => $planName]) }}" class="btn btn-sm btn-link text-decoration-none">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3">Shop Details</th>
                                <th>Install / Renewal</th>
                                <th class="text-end pe-3">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($shops as $shop)
                                @php
                                    $details = [];
                                    if(!empty($shop->detail)) {
                                        $details = is_string($shop->detail) ? json_decode($shop->detail, true) : $shop->detail;
                                    }
                                    $charge = $shop->charges->where('name', $planName)->where('status', 'active')->first();
                                @endphp
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-dark text-break" style="max-width: 150px;">
                                        {{ $details['name'] ?? $shop->shop }}
                                    </div>
                                    <div class="small text-muted">{{ $shop->shop }}</div>
                                </td>
                                <td>
                                    <div class="small text-dark">Installed: {{ $shop->created_at->format('M d') }}</div>
                                    <div class="small text-muted">
                                        @if($charge && $charge->trial_ends_on && \Carbon\Carbon::parse($charge->trial_ends_on)->isFuture())
                                            Trial ends: {{ \Carbon\Carbon::parse($charge->trial_ends_on)->format('M d') }}
                                        @elseif($charge && $charge->billing_on)
                                            Renews: {{ \Carbon\Carbon::parse($charge->billing_on)->format('M d') }}
                                        @endif
                                    </div>
                                </td>
                                <td class="text-end pe-3">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.shops.login', $shop->id) }}" target="_blank" class="btn btn-outline-primary"><i class="fas fa-sign-in-alt"></i></a>
                                        <a href="{{ route('admin.shops.show', $shop->id) }}" class="btn btn-outline-dark"><i class="fas fa-eye"></i></a>
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

    <!-- UNINSTALLED & TICKETS ROW -->
    <div class="row g-4 mt-2">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header text-danger border-bottom-0">
                    <i class="fas fa-trash-alt me-2"></i> Recently Uninstalled (Inactive)
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size: 0.9rem;">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-3 w-50">Shop</th>
                                <th>Last Plan / Date</th>
                                <th class="text-end pe-3">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($uninstalledShops as $shop)
                            <tr>
                                <td class="ps-3">
                                    <div class="fw-bold text-secondary">{{ $shop->shop }}</div>
                                    <div class="small text-muted">Uninstalled: {{ $shop->updated_at->format('M d, Y') }}</div>
                                </td>
                                <td>
                                    @if($shop->lastCharge)
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">{{ $shop->lastCharge->name }}</span>
                                    @else
                                        <span class="small text-muted">No Plan</span>
                                    @endif
                                </td>
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
            <div class="card h-100">
                <div class="card-header text-warning border-bottom-0 d-flex justify-content-between">
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