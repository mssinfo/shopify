@extends('msdev2::layout.admin')

@section('content')
<div class="container-fluid px-0">
    
    <!-- Toolbar -->
    <div class="card border-0 shadow-sm mb-3">
        <div class="card-body p-3 d-flex flex-wrap gap-2 justify-content-between align-items-center">
            <div class="d-flex gap-2 align-items-center">
                <h5 class="mb-0 me-2">System Logs</h5>
                
                <!-- Auto Refresh -->
                <button class="btn btn-sm btn-outline-success" id="btnAutoRefresh" onclick="toggleAutoRefresh()">
                    <i class="fas fa-sync"></i> Auto: <span id="autoStatus">OFF</span>
                </button>
            </div>

            <form method="GET" action="{{ route('admin.logs') }}" id="filterForm" class="d-flex gap-2">
                <!-- Level Filter -->
                <select name="level" class="form-select form-select-sm" onchange="this.form.submit()" style="width: 120px;">
                    <option value="all">All Levels</option>
                    <option value="error" {{ request('level') == 'error' ? 'selected' : '' }}>Error</option>
                    <option value="alert" {{ request('level') == 'alert' ? 'selected' : '' }}>Alert</option>
                    <option value="info" {{ request('level') == 'info' ? 'selected' : '' }}>Info</option>
                    <option value="debug" {{ request('level') == 'debug' ? 'selected' : '' }}>Debug</option>
                </select>

                <!-- Search -->
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="fas fa-search"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="Search error..." value="{{ request('search') }}">
                    <button class="btn btn-secondary" type="submit">Go</button>
                </div>

                <!-- Actions -->
                <div class="btn-group btn-group-sm ms-2">
                    <a href="{{ route('admin.logs.download') }}" class="btn btn-outline-secondary"><i class="fas fa-download"></i></a>
                    <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteLogsModal">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Table -->
    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="table-layout: fixed;">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 100px;">Level</th>
                        <th style="width: 160px;">Time</th>
                        <th>Message (Click for details)</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    @include('msdev2::admin.logs.table_rows', ['logs' => $logs])
                </tbody>
            </table>
        </div>
        <div class="card-footer bg-white">
            {{ $logs->links('pagination::bootstrap-5') }}
        </div>
    </div>

</div>

<!-- Log Detail Modal -->
<div class="modal fade" id="logDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Log Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body bg-dark text-white p-3">
                <pre id="modalLogContent" style="white-space: pre-wrap; font-family: monospace; font-size: 0.85rem; color: #00ff9d;"></pre>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Logs Confirmation Modal -->
<div class="modal fade" id="deleteLogsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-trash me-2"></i> Clear All Logs</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p><strong>Are you sure you want to clear all system logs?</strong></p>
                <p class="text-muted small mb-0">This action will permanently delete all log entries. This cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="window.location.href='{{ route('admin.logs.delete') }}'">
                    <i class="fas fa-trash me-2"></i> Delete All Logs
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // 1. Modal Logic
    const logModal = new bootstrap.Modal(document.getElementById('logDetailModal'));
    const modalContent = document.getElementById('modalLogContent');

    function showLogDetail(encodedContent) {
        // Decode URL encoded string passed from PHP
        modalContent.innerText = decodeURIComponent(encodedContent);
        logModal.show();
    }

    // 2. Auto Refresh Logic
    let refreshInterval = null;
    const tableBody = document.getElementById('logTableBody');

    function toggleAutoRefresh() {
        const btn = document.getElementById('btnAutoRefresh');
        const status = document.getElementById('autoStatus');

        if (refreshInterval) {
            clearInterval(refreshInterval);
            refreshInterval = null;
            btn.classList.remove('btn-success'); btn.classList.add('btn-outline-success');
            status.innerText = 'OFF';
        } else {
            // Refresh every 5 seconds
            refreshInterval = setInterval(() => {
                // Keep current filters
                const url = new URL(window.location.href);
                fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(res => res.text())
                    .then(html => {
                        tableBody.innerHTML = html;
                    });
            }, 5000);
            btn.classList.remove('btn-outline-success'); btn.classList.add('btn-success');
            status.innerText = 'ON';
        }
    }
</script>
@endpush
@endsection