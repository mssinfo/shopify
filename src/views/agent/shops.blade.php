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
                <input id="shops-plan" type="text" class="form-control" placeholder="Filter by plan (name or id)">
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
                    <tr><td colspan="6" class="text-muted">Use the search box above to find shops.</td></tr>
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
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('shops-search');
    const planInput = document.getElementById('shops-plan');
    const searchBtn = document.getElementById('shops-search-btn');
    const clearBtn = document.getElementById('shops-clear-btn');
    const tableBody = document.querySelector('#shops-table tbody');
    const pagination = document.getElementById('shops-pagination');

    let currentPage = 1;

    function fetchShops(q='', plan='', page=1, status=''){
        const url = new URL(`{{ url('/agent/shops/search') }}`);
        if(q) url.searchParams.set('q', q);
        if(plan) url.searchParams.set('plan', plan);
        if(status) url.searchParams.set('status', status);
        url.searchParams.set('page', page);
        url.searchParams.set('limit', 15);

        tableBody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
        fetch(url.toString())
            .then(r=>r.json())
            .then(data=>{
                renderTable(data.items || data || []);
                renderPagination(data);
            }).catch(err=>{
                tableBody.innerHTML = '<tr><td colspan="6" class="text-danger">Error loading results</td></tr>';
                pagination.innerHTML = '';
            });
    }

    function renderTable(items){
        tableBody.innerHTML = '';
        if(!Array.isArray(items) || !items.length){
            tableBody.innerHTML = '<tr><td colspan="7" class="text-muted">No shops found</td></tr>';
            return;
        }
        items.forEach(it => {
            const tr = document.createElement('tr');
            const name = it.name || it.shop || '—';
            const domain = it.domain || '';
            const plan = (it.plan && it.plan.name) || it.plan || '—';
            const status = it.uninstalled ? '<span class="badge bg-danger">Uninstalled</span>' : '<span class="badge bg-success">Installed</span>';
            const uninstalledAt = it.uninstalled_at || it.deleted_at || null;
            const uninstalledDisplay = uninstalledAt ? new Date(uninstalledAt).toLocaleString() : '—';
            const actions = `<a class="btn btn-sm btn-outline-secondary" href="{{ url('/agent/shops') }}/${it.id}/view">View</a>`;
            tr.innerHTML = `<td>${it.id}</td><td>${name}</td><td class="d-none d-md-table-cell">${domain}</td><td class="d-none d-lg-table-cell">${plan}</td><td>${status}</td><td class="d-none d-lg-table-cell">${uninstalledDisplay}</td><td class="text-end">${actions}</td>`;
            tableBody.appendChild(tr);
        });
    }

    function renderPagination(data){
        // expects { total, per_page, current_page, last_page } or fallback
        pagination.innerHTML = '';
        const total = data.total || (data.meta && data.meta.total) || 0;
        const per = data.per_page || (data.meta && data.meta.per_page) || 15;
        const current = data.current_page || (data.meta && data.meta.current_page) || currentPage;
        const last = data.last_page || (data.meta && data.meta.last_page) || Math.max(1, Math.ceil(total / per));

        // simple prev/next
        const prevLi = document.createElement('li'); prevLi.className = 'page-item ' + (current <= 1 ? 'disabled' : '');
        prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current-1}">Previous</a>`;
        pagination.appendChild(prevLi);

        for(let p = 1; p <= Math.min(last, 7); p++){
            const li = document.createElement('li'); li.className = 'page-item ' + (p===current? 'active':'');
            li.innerHTML = `<a class="page-link" href="#" data-page="${p}">${p}</a>`;
            pagination.appendChild(li);
        }

        const nextLi = document.createElement('li'); nextLi.className = 'page-item ' + (current >= last ? 'disabled' : '');
        nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current+1}">Next</a>`;
        pagination.appendChild(nextLi);

        // handlers
        pagination.querySelectorAll('a.page-link').forEach(a=>{
            a.addEventListener('click', function(e){
                e.preventDefault();
                const p = Number(this.getAttribute('data-page')) || 1;
                if(p < 1) return;
                currentPage = p;
                fetchShops(searchInput.value.trim(), planInput.value.trim(), currentPage);
            });
        });
    }

    searchBtn.addEventListener('click', ()=>{ currentPage = 1; fetchShops(searchInput.value.trim(), planInput.value.trim(), 1, document.getElementById('shops-status').value); });
    clearBtn.addEventListener('click', ()=>{ searchInput.value=''; planInput.value=''; document.getElementById('shops-status').value=''; currentPage=1; fetchShops('', '', 1); });

    // quick search on enter
    searchInput.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); searchBtn.click(); } });
    planInput.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ e.preventDefault(); searchBtn.click(); } });
    document.getElementById('shops-status').addEventListener('change', function(){ currentPage=1; fetchShops(searchInput.value.trim(), planInput.value.trim(), 1, this.value); });

    // initial load
    fetchShops();
});
</script>
@endsection
