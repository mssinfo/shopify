@extends('msdev2::layout.agent')
@section('content')
<div class="mb-4 d-flex justify-content-between align-items-center">
    <div>
        <h3 class="mb-0">Welcome, {{ Auth::user()->name ?? 'Agent' }}</h3>
        <div class="text-muted small">Manage stores, tickets and plans from here.</div>
    </div>
    <div>
        <a href="{{ route('msdev2.agent.tickets') }}" class="btn btn-outline-secondary">View Tickets</a>
    </div>
</div>

<div class="row">
    <div class="col-12">
        <div class="input-group mb-3 agent-search-wrapper">
            <input id="agent-shop-search" type="text" class="form-control" placeholder="Search shop by name or domain..." autocomplete="off">
            <button id="agent-shop-clear" class="btn btn-outline-secondary" type="button">Clear</button>
            <div id="agent-shop-suggestions" class="list-group mt-1" style="position:relative; z-index:1000;"></div>
        </div>
        <div id="agent-shop-detail" class="card my-3 d-none">
            <div class="card-body" id="agent-shop-detail-body"></div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">Total Store</div>
                        <div class="text-lg fw-bold">{{$total["shop"]}}</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-calendar feather-xl text-white-50"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">Tickets (Resoved)</div>
                        <div class="text-lg fw-bold">{{$total["ticket"]["resolve"]}}</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-dollar-sign feather-xl text-white-50"><line x1="12" y1="1" x2="12" y2="23"></line><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"></path></svg>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 col-xl-4 mb-4">
        <div class="card bg-danger text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">Ticket Pending</div>
                        <div class="text-lg fw-bold">{{$total["ticket"]["pending"]}}</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-square feather-xl text-white-50"><polyline points="9 11 12 14 22 4"></polyline><path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11"></path></svg>
                </div>
            </div>
        </div>
    </div>
    @foreach ($changes as $change)
    <div class="col-lg-6 col-xl-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div class="me-3">
                        <div class="text-white-75 small">{{$change->name}} Plan</div>
                        <div class="text-lg fw-bold">{{$change->count}} Shop</div>
                    </div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-message-circle feather-xl text-white-50"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8v.5z"></path></svg>
                </div>
            </div>
        </div>
    </div>
    @endforeach
    <!-- Full width chart -->
    <div class="col-12 mt-3">
        <div class="card mb-3">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <div>
                        <div class="text-muted small">Install / Uninstall (last 30 days)</div>
                        <div class="fw-bold">Shop activity</div>
                    </div>
                    <div class="text-muted small">Auto-updating chart</div>
                </div>
                <div class="chart-fixed" style="height:360px;">
                    <canvas id="shop-activity-chart" style="width:100%; height:100%;"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest installs / uninstalls side-by-side -->
    <div class="col-12">
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Latest</div>
                            <div class="fw-bold">Installs</div>
                        </div>
                        <a href="{{ route('msdev2.agent.shops') }}" class="btn btn-sm btn-outline-primary">View all</a>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="latest-installs-list">
                            <li class="list-group-item text-muted">Loading installs...</li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small text-muted">Latest</div>
                            <div class="fw-bold">Uninstalls</div>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <ul class="list-group list-group-flush" id="latest-uninstalls-list">
                            <li class="list-group-item text-muted">Loading uninstalls...</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Chart.js for dashboard charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" crossorigin="anonymous"></script>
<script>
document.addEventListener('DOMContentLoaded', function(){
    const input = document.getElementById('agent-shop-search');
    const suggestions = document.getElementById('agent-shop-suggestions');
    const detailCard = document.getElementById('agent-shop-detail');
    const detailBody = document.getElementById('agent-shop-detail-body');
    const clearBtn = document.getElementById('agent-shop-clear');
    const recentTableBody = document.querySelector('#recent-shops-table tbody');
    const chartCanvas = document.getElementById('shop-activity-chart');

    let timeout = null;
    let activityChart = null;

    // Suggestion search
    input.addEventListener('input', function(e){
        const q = e.target.value.trim();
        clearTimeout(timeout);
        if(q.length < 2){
            suggestions.innerHTML = '';
            return;
        }
        timeout = setTimeout(()=>{
            fetch(`{{ url('/agent/shops/search') }}?q=${encodeURIComponent(q)}&limit=8`)
                .then(r=>r.json()).then(data=>{
                    suggestions.innerHTML = '';
                    if(Array.isArray(data) && data.length){
                        data.forEach(item=>{
                            const el = document.createElement('button');
                            el.type = 'button';
                            el.className = 'list-group-item list-group-item-action';
                            el.innerHTML = `<div class="d-flex justify-content-between"><div>${(item.name||item.shop)} <a href="https://${item.shop}" target="_blank">🔗</a> <div class="small text-muted">${item.domain || ''}</div></div><div class="small text-muted">ID ${item.id}</div></div>`;
                            el.addEventListener('click', ()=>{
                                // Fetch inline shop detail and show
                                fetch(`{{ url('/agent/shops') }}/${item.id}`)
                                    .then(r=>r.json()).then(sd=>{
                                        suggestions.innerHTML = '';
                                        input.value = '';
                                        renderInlineDetail(sd);
                                    }).catch(()=>{ window.location.href = `{{ url('/agent/shops') }}/${item.id}/view`; });
                            });
                            suggestions.appendChild(el);
                        });
                    }
                }).catch(()=>{ suggestions.innerHTML = ''; });
        }, 250);
    });

    // hide suggestions on outside click
    document.addEventListener('click', function(e){
        if(!document.querySelector('.agent-search-wrapper')?.contains(e.target)){
            suggestions.innerHTML = '';
        }
    });

    // Enter key should go to shops listing with query
    input.addEventListener('keydown', function(e){
        if(e.key === 'Enter'){
            const q = input.value.trim();
            window.location.href = `{{ url('/agent/shops') }}?q=${encodeURIComponent(q)}`;
        }
    });

    clearBtn.addEventListener('click', function(){
        input.value = '';
        suggestions.innerHTML = '';
        detailCard.classList.add('d-none');
        detailBody.innerHTML = '';
    });

    // Render inline shop detail in dashboard
    function renderInlineDetail(sd){
        if(!sd) return;
        detailCard.classList.remove('d-none');
        let html = '';
        html += `<h5>${sd.shop} <small class="text-muted">ID ${sd.id}</small></h5>`;
        html += `<p class="mb-1"><strong>Domain:</strong> ${sd.domain || 'N/A'}</p>`;
        html += `<p class="mb-1"><strong>Active Plan:</strong> ${sd.activeCharge?.name || 'N/A'}</p>`;
        // status + uninstalled date
        const isUn = sd.uninstalled || sd.is_uninstalled || false;
        const unAt = sd.uninstalled_at || sd.deleted_at || null;
        if(isUn){ html += `<p class="mb-1"><strong>Status:</strong> <span class="badge bg-danger">Uninstalled</span> ${unAt?'<div class="small text-muted">On '+new Date(unAt).toLocaleString()+'</div>':''}</p>`; }
        else { html += `<p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Active</span></p>`; }
        // access token clickable if url-like or show copy
        const token = sd.access_token || sd.token || null;
        if(token){
            const isUrl = /^https?:\/\//i.test(token);
            if(isUrl){ html += `<p class="mb-1"><strong>Access Token:</strong> <a href="${token}" target="_blank">Open token URL</a></p>`; }
            else { html += `<p class="mb-1"><strong>Access Token:</strong> <code id="inline-token">${token}</code> <button class="btn btn-sm btn-outline-secondary ms-2" id="copy-inline-token">Copy</button></p>`; }
        }
        html += `<div class="mt-3"><a class="btn btn-sm btn-primary me-2" href="{{ url('/agent/shops') }}/${sd.id}/view">View details</a><a class="btn btn-sm btn-outline-secondary" href="{{ url('/agent/shops') }}/${sd.id}/direct" target="_blank">Direct login</a></div>`;
        // metadata simple list
        if(sd.metadata && sd.metadata.length){
            html += `<hr><h6>Metadata</h6><div class="small">`;
            sd.metadata.forEach(m => {
                let v = m.value;
                try{ v = JSON.parse(m.value); v = JSON.stringify(v); }catch(e){}
                html += `<div class="mb-1"><strong>${m.key}:</strong> <code style="white-space:pre-wrap">${v}</code></div>`;
            });
            html += `</div>`;
        }

        detailBody.innerHTML = html;
        const copyBtn = document.getElementById('copy-inline-token');
        if(copyBtn){ copyBtn.addEventListener('click', ()=>{ navigator.clipboard.writeText(document.getElementById('inline-token').textContent); copyBtn.innerText='Copied'; setTimeout(()=>copyBtn.innerText='Copy',1500); }); }
    }

    // Load activity chart data
    function loadActivityChart(){
        if(!chartCanvas) return;
        fetch(`{{ url('/agent/shops/stats') }}`)
            .then(r=>r.json())
            .then(json=>{
                // Expecting { labels: [...], installs: [...], uninstalls: [...] }
                const labels = json.labels || (json.map? json.map(x=>x.label) : []);
                const installs = json.installs || (json.map? json.map(x=>x.installs||0) : []);
                const uninstalls = json.uninstalls || (json.map? json.map(x=>x.uninstalls||0) : []);

                // fallbacks
                if(!labels.length && Array.isArray(json) && json.length){
                    // maybe array of {date, installs, uninstalls}
                    json.forEach(row => {
                        labels.push(row.date || row.label || '');
                        installs.push(row.installs || 0);
                        uninstalls.push(row.uninstalls || 0);
                    });
                }

                const data = {
                    labels: labels,
                    datasets: [
                        { label: 'Installs', data: installs, borderColor: '#198754', backgroundColor: 'rgba(25,135,84,0.08)', tension: .2 },
                        { label: 'Uninstalls', data: uninstalls, borderColor: '#dc3545', backgroundColor: 'rgba(220,53,69,0.06)', tension: .2 }
                    ]
                };

                const cfg = {
                    type: 'line', data: data, options: {
                        responsive: true, maintainAspectRatio: false,
                        plugins: { legend: { position: 'top' } },
                        scales: { x: { ticks: { maxRotation: 0 } }, y: { beginAtZero: true } }
                    }
                };

                if(activityChart){ try{ activityChart.destroy(); }catch(e){} }
                activityChart = new Chart(chartCanvas, cfg);
            }).catch(err=>{
                // If endpoint missing or error, show placeholder message inside canvas parent
                const parent = chartCanvas.closest('.card-body');
                if(parent){ parent.querySelector('canvas').style.display = 'none';
                    let el = parent.querySelector('.text-muted.placeholder');
                    if(!el){ el = document.createElement('div'); el.className = 'text-muted placeholder'; el.innerText = 'No activity data available.'; parent.appendChild(el); }
                }
            });
    }

    // Load recent shops
    function loadRecentShops(){
        // try /agent/shops/recent then fallback to search
        fetch(`{{ url('/agent/shops/recent') }}`)
            .then(r=>r.json())
            .then(data=> renderRecent(data))
            .catch(()=>{
                fetch(`{{ url('/agent/shops/search') }}?limit=6`) 
                  .then(r=>r.json()).then(data=> renderRecent(data))
                  .catch(()=>{/* ignore */});
            });
    }

    function renderRecent(items){
        if(!recentTableBody) return;
        recentTableBody.innerHTML = '';
        if(!Array.isArray(items) || !items.length){
            const tr = document.createElement('tr');
            tr.innerHTML = '<td colspan="4" class="text-muted small">No recent shops</td>';
            recentTableBody.appendChild(tr);
            return;
        }
        items.slice(0,6).forEach(it=>{
            const tr = document.createElement('tr');
            const name = it.name || it.shop || '—';
            const domain = it.domain || '';
            const plan = (it.plan && it.plan.name) || it.plan || '—';
            const isUn = it.uninstalled || it.is_uninstalled || false;
            const unAt = it.uninstalled_at || it.deleted_at || null;
            const status = isUn ? '<span class="badge bg-danger">Uninstalled</span>' : '<span class="badge bg-success">Active</span>';
            const detailDate = unAt ? '<div class="small text-muted">' + new Date(unAt).toLocaleString() + '</div>' : '';
            tr.innerHTML = `<td><a href="{{ url('/agent/shops') }}/${it.id}/view">${name}</a></td><td class="d-none d-md-table-cell">${domain}</td><td class="d-none d-lg-table-cell">${plan}</td><td class="text-end">${status}${detailDate}</td>`;
            recentTableBody.appendChild(tr);
        });
    }

    // Initial loads
    loadActivityChart();
    loadRecentShops();
    // load latest installs / uninstalls lists
    function loadLatestLists(){
        fetch(`{{ url('/agent/shops/latest/installs') }}`)
            .then(r=>r.json()).then(items=>{
                const el = document.getElementById('latest-installs-list'); el.innerHTML='';
                if(!items.length) el.innerHTML='<li class="list-group-item text-muted">No recent installs</li>';
                items.forEach(it=>{
                    const li = document.createElement('li'); li.className='list-group-item';
                    li.innerHTML = `<div class="d-flex justify-content-between"><div><a href="{{ url('/agent/shops') }}/${it.id}/view">${it.shop}</a><div class="small text-muted">${it.domain || ''}</div></div><div class="small text-muted">${new Date(it.installed_at).toLocaleString()}</div></div>`;
                    el.appendChild(li);
                });
            }).catch(()=>{ document.getElementById('latest-installs-list').innerHTML='<li class="list-group-item text-muted">Error loading</li>'; });

        fetch(`{{ url('/agent/shops/latest/uninstalls') }}`)
            .then(r=>r.json()).then(items=>{
                const el = document.getElementById('latest-uninstalls-list'); el.innerHTML='';
                if(!items.length) el.innerHTML='<li class="list-group-item text-muted">No recent uninstalls</li>';
                items.forEach(it=>{
                    const li = document.createElement('li'); li.className='list-group-item';
                    const unAt = it.uninstalled_at || it.deleted_at || null;
                    const when = unAt ? new Date(unAt).toLocaleString() : 'Unknown';
                    li.innerHTML = `<div class="d-flex justify-content-between"><div><a href="{{ url('/agent/shops') }}/${it.id}/view">${it.shop}</a><div class="small text-muted">${it.domain || ''}</div></div><div class="small text-danger">${when}</div></div>`;
                    el.appendChild(li);
                });
            }).catch(()=>{ document.getElementById('latest-uninstalls-list').innerHTML='<li class="list-group-item text-muted">Error loading</li>'; });
    }

    loadLatestLists();

    // refresh chart every 5 minutes in background
    setInterval(loadActivityChart, 5 * 60 * 1000);
});
</script>
@endsection