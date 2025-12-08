@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    
    <!-- BACK LINK -->
    <div class="mb-3">
        <a href="{{ route('admin.shops') }}" class="text-decoration-none text-muted small">
            <i class="fas fa-arrow-left me-1"></i> Back to Shops List
        </a>
    </div>

    <!-- ==========================================
         TOP PROFILE HEADER (Basic Details)
    ========================================== -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-body p-4">
            <div class="row">
                <!-- COL 1: Store Identity & Status -->
                <div class="col-lg-5 mb-3 mb-lg-0 border-end-lg">
                    <div class="d-flex align-items-start">
                        <div class="bg-light rounded p-3 me-3 text-center" style="min-width: 80px;">
                            <i class="fas fa-store fa-2x text-secondary"></i>
                        </div>
                        <div>
                            <h3 class="fw-bold text-dark mb-1">
                                {{ $shopJsonDetail['name'] ?? $shopInfo->shop }}
                            </h3>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <a href="https://{{ $shopJsonDetail['domain'] ?? $shopInfo->shop }}" target="_blank" class="text-primary text-decoration-none">
                                    {{ $shopJsonDetail['domain'] ?? $shopInfo->shop }} <i class="fas fa-external-link-alt small ms-1"></i>
                                </a>
                                @if($shopInfo->is_online)
                                    <span class="badge bg-success bg-opacity-10 text-success border border-success px-2">Online</span>
                                @endif
                            </div>
                            <div>
                                @if($shopInfo->is_uninstalled)
                                    <span class="badge bg-danger">Uninstalled {{ $shopInfo->updated_at->format('M d, Y') }}</span>
                                @else
                                    <span class="badge bg-success">Active Install</span>
                                @endif
                                <span class="text-muted small ms-2">ID: {{ $shopInfo->id }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- COL 2: Owner & Contact Info -->
                <div class="col-lg-4 mb-3 mb-lg-0 border-end-lg ps-lg-4">
                    <h6 class="text-uppercase text-muted small fw-bold mb-3">Contact Information</h6>
                    
                    <!-- Owner -->
                    <div class="d-flex align-items-center mb-2">
                        <div class="text-secondary me-3" style="width: 20px;"><i class="fas fa-user-circle"></i></div>
                        <div class="fw-bold text-dark">{{ $shopJsonDetail['shop_owner'] ?? 'N/A' }}</div>
                    </div>
                    
                    <!-- Email -->
                    <div class="d-flex align-items-center mb-2">
                        <div class="text-secondary me-3" style="width: 20px;"><i class="far fa-envelope"></i></div>
                        <div>
                            <a href="mailto:{{ $shopJsonDetail['email'] ?? '' }}" class="text-decoration-none text-dark">
                                {{ $shopJsonDetail['email'] ?? 'N/A' }}
                            </a>
                            @if(isset($shopJsonDetail['customer_email']) && $shopJsonDetail['customer_email'] !== $shopJsonDetail['email'])
                                <br><small class="text-muted">Cust: {{ $shopJsonDetail['customer_email'] }}</small>
                            @endif
                        </div>
                    </div>

                    <!-- Phone -->
                    <div class="d-flex align-items-center">
                        <div class="text-secondary me-3" style="width: 20px;"><i class="fas fa-phone"></i></div>
                        <div class="text-muted">{{ $shopJsonDetail['phone'] ?? 'N/A' }}</div>
                    </div>
                </div>

                <!-- COL 3: Location & Actions -->
                <div class="col-lg-3 ps-lg-4 d-flex flex-column justify-content-between">
                    <div>
                        <h6 class="text-uppercase text-muted small fw-bold mb-3">Location</h6>
                        <div class="d-flex align-items-start mb-2">
                            <div class="text-secondary me-3" style="width: 20px;"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <div class="fw-bold">{{ $shopJsonDetail['city'] ?? '' }}</div>
                                <div class="text-muted small">{{ $shopJsonDetail['province'] ?? '' }} {{ $shopJsonDetail['zip'] ?? '' }}</div>
                                <div class="badge bg-light text-dark border mt-1">{{ $shopJsonDetail['country_name'] ?? 'Unknown' }}</div>
                            </div>
                        </div>
                        @if(isset($shopJsonDetail['iana_timezone']))
                        <div class="d-flex align-items-center mt-2 small text-muted">
                            <i class="far fa-clock me-3" style="width: 20px;"></i> {{ $shopJsonDetail['iana_timezone'] }}
                        </div>
                        @endif
                    </div>

                    <div class="mt-3 text-end">
                        <a href="{{ route('admin.shops.login', $shopInfo->id) }}" target="_blank" class="btn btn-primary w-100 mb-2">
                            <i class="fas fa-key me-2"></i> Direct Login
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ==========================================
         MAIN CONTENT (Stats, Logs, Tabs)
    ========================================== -->
    <div class="row g-4">
        
        <!-- LEFT COLUMN: App Stats & Metadata -->
        <div class="col-lg-4">
            
            <!-- App Performance Card -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold">App Performance</div>
                <table class="table table-borderless mb-0">
                    <tr>
                        <td class="text-muted ps-3">Current Plan</td>
                        <td class="text-end pe-3 fw-bold">
                            @if($activePlan)
                                <span class="text-primary">{{ $activePlan->name }}</span> <small class="text-muted">(${{ $activePlan->price }})</small>
                            @else
                                <span class="text-secondary">Freemium / Trial</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-3">Installed</td>
                        <td class="text-end pe-3" title="{{ $shopInfo->created_at }}">{{ $shopInfo->created_at->diffForHumans() }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-3">Lifetime Revenue</td>
                        <td class="text-end pe-3 text-success fw-bold">${{ number_format($shopInfo->total_earnings, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted ps-3">App Install ID</td>
                        <td class="text-end pe-3 small text-truncate" style="max-width: 120px;">
                            {{ $shopInfo->metadata->where('key', '_current_app_installation_id')->first()->value ?? 'N/A' }}
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Local Metadata -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white fw-bold d-flex justify-content-between align-items-center">
                    <span>Local Metadata (DB)</span>
                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#addMetaModal"><i class="fas fa-plus"></i></button>
                </div>
                <div class="list-group list-group-flush">
                    @forelse($shopInfo->metadata as $meta)
                        <div class="list-group-item d-flex justify-content-between align-items-center p-2">
                            <div class="small text-break" style="line-height:1.2;">
                                <strong>{{ $meta->key }}</strong><br>
                                <span class="text-muted">{{ Str::limit($meta->value, 50) }}</span>
                            </div>
                            <form action="{{ route('admin.shops.metadata.delete', $meta->id) }}" method="POST" onsubmit="return confirm('Delete this key?');">
                                @csrf @method('DELETE')
                                <button class="btn btn-link text-danger btn-sm"><i class="fas fa-times"></i></button>
                            </form>
                        </div>
                    @empty
                        <div class="list-group-item text-muted small text-center">No local metadata found.</div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Detailed Tabs -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white p-0">
                    <ul class="nav nav-tabs card-header-tabs ms-0 me-0" id="shopTabs" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#logs">Logs</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#metafields">Shopify Metafields</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#history">Billing History</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#json">Raw JSON</button></li>
                        @if(!empty($dynamicTables))
                            <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#dynamic">Tables</button></li>
                        @endif
                    </ul>
                </div>

                <div class="card-body p-3">
                    <div class="tab-content">

                        <!-- LOGS TAB (Inlined Log Viewer) -->
                        <div class="tab-pane fade show active" id="logs">
                            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                                <!-- File Selector -->
                                <form id="logFileForm" method="GET" action="{{ route('admin.shops.show', $shopInfo->id) }}" class="d-flex align-items-center">
                                    <select name="log_file" class="form-select form-select-sm me-2" onchange="this.form.submit()" style="width: 160px;">
                                        @forelse($logFiles as $file)
                                            <option value="{{ $file }}" {{ $selectedLogFile == $file ? 'selected' : '' }}>{{ $file }}</option>
                                        @empty
                                            <option value="">No logs available</option>
                                        @endforelse
                                    </select>
                                </form>

                                <!-- Tools -->
                                <div class="btn-group btn-group-sm">
                                    <div class="input-group input-group-sm me-2" style="width: 150px;">
                                        <span class="input-group-text bg-light border-end-0"><i class="fas fa-search"></i></span>
                                        <input type="text" id="logSearch" class="form-control border-start-0" placeholder="Search logs...">
                                    </div>
                                    
                                    <button type="button" class="btn btn-outline-success" id="btnAutoRefresh" onclick="toggleAutoRefresh()">
                                        <i class="fas fa-sync"></i> <span id="autoStatus">OFF</span>
                                    </button>
                                    
                                    @if($selectedLogFile)
                                        <a href="{{ route('admin.shops.logs.download', ['id' => $shopInfo->id, 'file' => $selectedLogFile]) }}" class="btn btn-outline-secondary" title="Download">
                                            <i class="fas fa-download"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" title="Delete" data-bs-toggle="modal" data-bs-target="#deleteShopLogModal">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Content Area -->
                            <div class="bg-dark text-light p-3 rounded font-monospace position-relative" style="height: 500px; overflow-y: auto; font-size: 0.8rem;">
                                <div id="logContent" style="white-space: pre-wrap;">Loading log file...</div>
                            </div>
                            <div class="text-end mt-1">
                                <small class="text-muted">Last Updated: <span id="lastUpdated">--:--:--</span></small>
                            </div>
                        </div>

                        <!-- METAFIELDS TAB -->
                        <div class="tab-pane fade" id="metafields">
                            @if(isset($shopifyMetafields['error']))
                                <div class="alert alert-danger p-2 small mb-2">GraphQL Error: {{ $shopifyMetafields['error'] }}</div>
                            @endif

                            <h6 class="fw-bold text-primary mt-2"><i class="fas fa-lock me-2"></i>Private (App Installation)</h6>
                            <div class="table-responsive border rounded mb-4">
                                <table class="table table-sm table-striped mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light"><tr><th>Key</th><th>Type</th><th>Value</th></tr></thead>
                                    <tbody>
                                        @forelse($shopifyMetafields['private'] as $m)
                                        <tr>
                                            <td class="fw-bold">{{ $m['key'] }}</td>
                                            <td><span class="badge bg-secondary" style="font-size:0.7em">{{ $m['type'] }}</span></td>
                                            <td>
                                                @if($m['type'] === 'json')
                                                    <pre class="m-0 bg-light p-1 rounded" style="max-height:380px;overflow:auto">{{ json_encode(json_decode($m['value']), JSON_PRETTY_PRINT) }}</pre>
                                                @else
                                                    <div class="text-break">{{ Str::limit($m['value'], 150) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="3" class="text-center text-muted">No private metafields found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            <h6 class="fw-bold text-success"><i class="fas fa-globe me-2"></i>Public (Shop)</h6>
                            <div class="table-responsive border rounded">
                                <table class="table table-sm table-striped mb-0" style="font-size: 0.85rem;">
                                    <thead class="table-light"><tr><th>Key</th><th>Type</th><th>Value</th></tr></thead>
                                    <tbody>
                                        @forelse($shopifyMetafields['public'] as $m)
                                        <tr>
                                            <td class="fw-bold">{{ $m['key'] }}</td>
                                            <td><span class="badge bg-secondary" style="font-size:0.7em">{{ $m['type'] }}</span></td>
                                            <td>
                                                @if($m['type'] === 'json')
                                                    <pre class="m-0 bg-light p-1 rounded" style="max-height:380px;overflow:auto">{{ json_encode(json_decode($m['value']), JSON_PRETTY_PRINT) }}</pre>
                                                @else
                                                    <div class="text-break">{{ Str::limit($m['value'], 150) }}</div>
                                                @endif
                                            </td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="3" class="text-center text-muted">No public metafields found.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- BILLING HISTORY TAB -->
                        <div class="tab-pane fade" id="history">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead class="table-light"><tr><th>Plan</th><th>Price</th><th>Status</th><th>Date</th></tr></thead>
                                    <tbody>
                                        @forelse($charges as $charge)
                                        <tr>
                                            <td>
                                                <div class="fw-bold">{{ $charge->name }}</div>
                                                @if($charge->trial_days) <small class="text-info">Trial: {{ $charge->trial_days }}d</small> @endif
                                            </td>
                                            <td>${{ $charge->price }}</td>
                                            <td><span class="badge bg-{{ $charge->status=='active'?'success':($charge->status=='cancelled'?'danger':'secondary') }}">{{ $charge->status }}</span></td>
                                            <td>{{ $charge->created_at->format('M d, Y') }} <small class="text-muted">({{ $charge->created_at->diffForHumans() }})</small></td>
                                        </tr>
                                        @empty
                                        <tr><td colspan="4" class="text-center text-muted">No billing history.</td></tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- RAW JSON TAB -->
                        <div class="tab-pane fade" id="json">
                            <pre class="bg-light p-3 border rounded" style="max-height: 500px; overflow: auto;">{{ json_encode($shopJsonDetail, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                        </div>

                        <!-- DYNAMIC TABLES TAB -->
                        @if(!empty($dynamicTables))
                        <div class="tab-pane fade" id="dynamic">
                            <ul class="nav nav-pills mb-3" role="tablist">
                                @foreach($dynamicTables as $tbl => $rows)
                                    <li class="nav-item"><button class="nav-link {{ $loop->first?'active':'' }} btn-sm" data-bs-toggle="pill" data-bs-target="#t-{{ $tbl }}">{{ ucfirst($tbl) }}</button></li>
                                @endforeach
                            </ul>
                            <div class="tab-content">
                                @foreach($dynamicTables as $tbl => $rows)
                                    <div class="tab-pane fade {{ $loop->first?'show active':'' }}" id="t-{{ $tbl }}">
                                        <div class="table-responsive border rounded" style="max-height:500px">
                                            <table class="table table-sm table-striped mb-0" style="font-size:0.75rem">
                                                <thead class="table-dark sticky-top">
                                                    <tr>
                                                        @foreach((array)$rows[0] as $k=>$v) 
                                                            <th style="white-space: nowrap;">{{$k}}</th> 
                                                        @endforeach
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($rows as $row)
                                                    <tr>
                                                        @foreach((array)$row as $v) 
                                                            <td class="text-truncate" style="max-width:200px" title="{{ is_scalar($v) ? $v : json_encode($v) }}">
                                                                {{ is_scalar($v) ? Str::limit($v, 50) : json_encode($v) }}
                                                            </td> 
                                                        @endforeach
                                                    </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: Add Metadata -->
<div class="modal fade" id="addMetaModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form action="{{ route('admin.shops.metadata.store', $shopInfo->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h6 class="modal-title">Add Metadata</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-2"><input type="text" name="key" class="form-control form-control-sm" placeholder="Key" required></div>
                <div class="mb-0"><textarea name="value" class="form-control form-control-sm" placeholder="Value" required></textarea></div>
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-sm btn-primary w-100">Save</button></div>
        </form>
    </div>
</div>

<!-- MODAL: Delete Shop Log Confirmation -->
@if($selectedLogFile)
<div class="modal fade" id="deleteShopLogModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i> Delete Log File</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to delete this log file?</strong></p>
                <p class="mb-2">File: <code>{{ $selectedLogFile }}</code></p>
                <p class="text-muted small mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form action="{{ route('admin.shops.logs.delete', ['id' => $shopInfo->id, 'file' => $selectedLogFile]) }}" method="POST" class="d-inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i> Delete Log
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script>
    // --- LOG VIEWER LOGIC ---
    const currentFile = "{{ $selectedLogFile }}";
    const logContentDiv = document.getElementById('logContent');
    const searchInput = document.getElementById('logSearch');
    const lastUpdatedSpan = document.getElementById('lastUpdated');
    let autoRefreshInterval = null;
    let fullLogText = "";

    function fetchLogContent() {
        if(!currentFile) return;
        fetch(`{{ route('admin.shops.logs.content', $shopInfo->id) }}?file=${currentFile}`)
            .then(res => res.json())
            .then(data => {
                fullLogText = data.content;
                applyLogFilter();
                lastUpdatedSpan.innerText = data.modified;
            })
            .catch(err => { logContentDiv.innerText = "Error loading logs."; });
    }

    function toggleAutoRefresh() {
        const btn = document.getElementById('btnAutoRefresh');
        const status = document.getElementById('autoStatus');
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            autoRefreshInterval = null;
            btn.classList.remove('btn-success'); btn.classList.add('btn-outline-success');
            status.innerText = "OFF";
        } else {
            fetchLogContent();
            autoRefreshInterval = setInterval(fetchLogContent, 5000);
            btn.classList.remove('btn-outline-success'); btn.classList.add('btn-success');
            status.innerText = "ON";
        }
    }

    function applyLogFilter() {
        const term = searchInput.value.toLowerCase();
        if (!term) { logContentDiv.innerText = fullLogText; return; }
        const lines = fullLogText.split('\n');
        logContentDiv.innerText = lines.filter(line => line.toLowerCase().includes(term)).join('\n');
    }

    searchInput.addEventListener('input', applyLogFilter);

    // Initial Load
    if(currentFile) fetchLogContent();
</script>
@endpush
@endsection