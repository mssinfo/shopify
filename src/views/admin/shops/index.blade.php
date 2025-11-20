@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    
    <!-- HEADER & FILTERS -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="mb-0 fw-bold">All Shops <span class="badge bg-light text-secondary rounded-pill border ms-2">{{ $shops->total() }}</span></h5>
            </div>

            <form method="GET" action="{{ route('admin.shops') }}" class="row g-2 align-items-center">
                <!-- Search Input -->
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search Domain, Owner, or Email..." value="{{ request('search') }}">
                    </div>
                </div>

                <!-- Plan Filter -->
                <div class="col-md-3">
                    <select name="plan" class="form-select">
                        <option value="all">All Plans</option>
                        <option value="freemium" {{ request('plan') == 'freemium' ? 'selected' : '' }}>Freemium / No Plan</option>
                        @foreach($allPlans as $planName)
                            <option value="{{ $planName }}" {{ request('plan') == $planName ? 'selected' : '' }}>{{ $planName }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Submit & Reset -->
                <div class="col-md-4 text-end">
                    <button type="submit" class="btn btn-primary px-4">Filter</button>
                    <a href="{{ route('admin.shops') }}" class="btn btn-outline-secondary ms-1">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-uppercase small text-muted">
                        <tr>
                            <th class="ps-4 py-3">Shop Details</th>
                            <th class="py-3">Owner / Location</th>
                            <th class="py-3">Plan</th>
                            <th class="py-3">Status</th>
                            <th class="py-3">Installed</th>
                            <th class="text-end pe-4 py-3">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($shops as $shop)
                            @php
                                // Safely decode JSON details
                                $details = [];
                                if(!empty($shop->detail)) {
                                    $details = is_string($shop->detail) ? json_decode($shop->detail, true) : $shop->detail;
                                }
                            @endphp
                        <tr>
                            <!-- Column 1: Shop Name & Domains -->
                            <td class="ps-4">
                                <div class="fw-bold text-dark" style="font-size: 0.95rem;">
                                    {{ $details['name'] ?? $shop->shop }}
                                </div>
                                <div class="small text-muted">{{ $shop->shop }}</div>
                                @if(isset($details['domain']) && $details['domain'] !== $shop->shop)
                                    <div class="small text-primary mt-1">
                                        <i class="fas fa-globe me-1" style="font-size: 0.7rem;"></i> {{ $details['domain'] }}
                                    </div>
                                @endif
                            </td>

                            <!-- Column 2: Owner, Email, Country -->
                            <td>
                                <div class="d-flex align-items-center mb-1">
                                    <i class="fas fa-user-circle text-secondary me-2"></i>
                                    <span class="fw-bold text-dark small">{{ $details['shop_owner'] ?? 'Unknown' }}</span>
                                </div>
                                <div class="small text-muted mb-1">
                                    <a href="mailto:{{ $details['email'] ?? '' }}" class="text-decoration-none text-muted">
                                        <i class="far fa-envelope me-1"></i> {{ $details['email'] ?? '-' }}
                                    </a>
                                </div>
                                @if(isset($details['country_name']))
                                    <span class="badge bg-light text-secondary border rounded-1" style="font-size: 0.7rem;">
                                        {{ $details['country_name'] }}
                                    </span>
                                @endif
                            </td>

                            <!-- Column 3: Plan -->
                            <td>
                                @if($shop->activeCharge)
                                    <span class="badge bg-info text-dark border border-info">
                                        {{ $shop->activeCharge->name }}
                                    </span>
                                    <div class="small text-muted mt-1">${{ $shop->activeCharge->price }}/mo</div>
                                @else
                                    <span class="badge bg-light text-secondary border">Freemium</span>
                                @endif
                            </td>

                            <!-- Column 4: Status -->
                            <td>
                                @if($shop->is_uninstalled)
                                    <span class="badge bg-danger bg-opacity-10 text-danger px-2 py-1">Uninstalled</span>
                                    <div class="small text-danger mt-1" style="font-size: 0.7rem;">
                                        {{ $shop->updated_at->format('M d, Y') }}
                                    </div>
                                @else
                                    <span class="badge bg-success bg-opacity-10 text-success px-2 py-1">Active</span>
                                    @if($shop->is_online)
                                        <div class="small text-success mt-1" style="font-size: 0.7rem;">
                                            <i class="fas fa-circle" style="font-size: 0.5rem;"></i> Online
                                        </div>
                                    @endif
                                @endif
                            </td>

                            <!-- Column 5: Installed Date -->
                            <td class="text-muted small">
                                <div class="fw-bold text-dark">{{ $shop->created_at->format('M d, Y') }}</div>
                                <div>{{ $shop->created_at->diffForHumans() }}</div>
                            </td>

                            <!-- Column 6: Actions -->
                            <td class="text-end pe-4">
                                <div class="btn-group btn-group-sm shadow-sm">
                                    <a href="{{ route('admin.shops.login', $shop->id) }}" class="btn btn-outline-primary px-2" title="Direct Login" data-bs-toggle="tooltip">
                                        <i class="fas fa-sign-in-alt"></i>
                                    </a>
                                    <a href="{{ route('admin.shops.show', $shop->id) }}" class="btn btn-outline-dark px-2" title="View Details" data-bs-toggle="tooltip">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="https://{{ $details['domain'] ?? $shop->shop }}" target="_blank" class="btn btn-outline-secondary px-2" title="Visit Storefront" data-bs-toggle="tooltip">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <div class="mb-2"><i class="fas fa-search fa-2x text-light"></i></div>
                                No shops found matching your filters.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3">
            {{ $shops->links('pagination::bootstrap-5') }}
        </div>
    </div>
</div>

@push('scripts')
<script>
    // Initialize Bootstrap tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl)
    })
</script>
@endpush
@endsection