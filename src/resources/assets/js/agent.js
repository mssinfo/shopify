(function(){
    // agent.js - consolidated UI scripts for agent views
    function loadScript(src, cb){
        const s = document.createElement('script'); s.src = src; s.async = true;
        s.onload = cb; s.onerror = cb; document.head.appendChild(s);
    }

    function safeFetch(url){ return fetch(url).then(r=>r.json()).catch(()=>[]); }

    function initDashboard(){
        const input = document.getElementById('agent-shop-search');
        if(!input) return;
        const suggestions = document.getElementById('agent-shop-suggestions');
        const detailCard = document.getElementById('agent-shop-detail');
        const detailBody = document.getElementById('agent-shop-detail-body');
        const clearBtn = document.getElementById('agent-shop-clear');
        const recentTableBody = document.querySelector('#recent-shops-table tbody');
        const chartCanvas = document.getElementById('shop-activity-chart');

        let timeout = null; let activityChart = null;

        input.addEventListener('input', function(e){
            const q = e.target.value.trim(); clearTimeout(timeout);
            if(q.length < 2){ if(suggestions) suggestions.innerHTML = ''; return; }
            timeout = setTimeout(()=>{
                safeFetch(window.AgentConfig.urls.shopsSearch + '?q='+encodeURIComponent(q)+'&limit=8')
                    .then(data=>{
                        if(!suggestions) return; suggestions.innerHTML='';
                        if(Array.isArray(data) && data.length){
                            data.forEach(item=>{
                                const el = document.createElement('button');
                                el.type='button'; el.className='list-group-item list-group-item-action';
                                el.innerHTML = `<div class="d-flex justify-content-between"><div>${(item.name||item.shop)} <a href="https://${item.shop}" target="_blank">🔗</a> <div class="small text-muted">${item.domain||''}</div></div><div class="small text-muted">ID ${item.id}</div></div>`;
                                el.addEventListener('click', ()=>{
                                    safeFetch(window.AgentConfig.urls.shopsBase + '/' + item.id)
                                        .then(sd=>{ suggestions.innerHTML=''; input.value=''; renderInlineDetail(sd); })
                                        .catch(()=>{ window.location.href = window.AgentConfig.urls.shopsBase + '/' + item.id + '/view'; });
                                });
                                suggestions.appendChild(el);
                            });
                        }
                    }).catch(()=>{ if(suggestions) suggestions.innerHTML=''; });
            }, 250);
        });

        document.addEventListener('click', function(e){ if(!document.querySelector('.agent-search-wrapper')?.contains(e.target)){ if(suggestions) suggestions.innerHTML=''; } });
        input.addEventListener('keydown', function(e){ if(e.key === 'Enter'){ const q = input.value.trim(); window.location.href = window.AgentConfig.urls.shopsRoute + '?q='+encodeURIComponent(q); } });
        clearBtn?.addEventListener('click', function(){ input.value=''; suggestions.innerHTML=''; detailCard?.classList.add('d-none'); detailBody && (detailBody.innerHTML=''); });

        function renderInlineDetail(sd){ if(!sd) return; detailCard?.classList.remove('d-none');
            let html=''; html += `<h5>${sd.shop} <small class="text-muted">ID ${sd.id}</small></h5>`;
            html += `<p class="mb-1"><strong>Domain:</strong> ${sd.domain||'N/A'}</p>`;
            html += `<p class="mb-1"><strong>Active Plan:</strong> ${sd.activeCharge?.name||'N/A'}</p>`;
            const isUn = sd.uninstalled || sd.is_uninstalled || false; const unAt = sd.uninstalled_at || sd.deleted_at || null;
            if(isUn) html += `<p class="mb-1"><strong>Status:</strong> <span class="badge bg-danger">Uninstalled</span> ${unAt?'<div class="small text-muted">On '+new Date(unAt).toLocaleString()+'</div>':''}</p>`; else html += `<p class="mb-1"><strong>Status:</strong> <span class="badge bg-success">Active</span></p>`;
            const token = sd.access_token || sd.token || null;
            if(token){ const isUrl = /^https?:\/\//i.test(token); if(isUrl) html += `<p class="mb-1"><strong>Access Token:</strong> <a href="${token}" target="_blank">Open token URL</a></p>`; else html += `<p class="mb-1"><strong>Access Token:</strong> <code id="inline-token">${token}</code> <button class="btn btn-sm btn-outline-secondary ms-2" id="copy-inline-token">Copy</button></p>`; }
            html += `<div class="mt-3"><a class="btn btn-sm btn-primary me-2" href="${window.AgentConfig.urls.shopsBase}/${sd.id}/view">View details</a><a class="btn btn-sm btn-outline-secondary" href="${window.AgentConfig.urls.shopsBase}/${sd.id}/direct" target="_blank">Direct login</a></div>`;
            if(sd.metadata && sd.metadata.length){ html += `<hr><h6>Metadata</h6><div class="small">`; sd.metadata.forEach(m=>{ let v=m.value; try{ v=JSON.parse(m.value); v=JSON.stringify(v);}catch(e){}; html += `<div class="mb-1"><strong>${m.key}:</strong> <code style="white-space:pre-wrap">${v}</code></div>`; }); html += `</div>`; }
            detailBody.innerHTML = html; const copyBtn = document.getElementById('copy-inline-token'); if(copyBtn){ copyBtn.addEventListener('click', ()=>{ navigator.clipboard.writeText(document.getElementById('inline-token').textContent); copyBtn.innerText='Copied'; setTimeout(()=>copyBtn.innerText='Copy',1500); }); }
        }

        function loadActivityChart(){ if(!chartCanvas) return;
            const doChart = ()=>{
                safeFetch(window.AgentConfig.urls.shopsStats).then(json=>{
                    const labels = json.labels || (json.map? json.map(x=>x.label) : []);
                    const installs = json.installs || (json.map? json.map(x=>x.installs||0) : []);
                    const uninstalls = json.uninstalls || (json.map? json.map(x=>x.uninstalls||0) : []);
                    if(!labels.length && Array.isArray(json) && json.length){ json.forEach(row=>{ labels.push(row.date||row.label||''); installs.push(row.installs||0); uninstalls.push(row.uninstalls||0); }); }
                    const data = { labels: labels, datasets: [ { label:'Installs', data:installs, borderColor:'#198754', backgroundColor:'rgba(25,135,84,0.08)', tension:.2 }, { label:'Uninstalls', data:uninstalls, borderColor:'#dc3545', backgroundColor:'rgba(220,53,69,0.06)', tension:.2 } ] };
                    const cfg = { type:'line', data:data, options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{ position:'top' } }, scales:{ x:{ ticks:{ maxRotation:0 } }, y:{ beginAtZero:true } } } };
                    if(window._agentActivityChart){ try{ window._agentActivityChart.destroy(); }catch(e){} }
                    window._agentActivityChart = new Chart(chartCanvas, cfg);
                }).catch(()=>{ const parent = chartCanvas.closest('.card-body'); if(parent){ parent.querySelector('canvas').style.display = 'none'; let el = parent.querySelector('.text-muted.placeholder'); if(!el){ el = document.createElement('div'); el.className='text-muted placeholder'; el.innerText='No activity data available.'; parent.appendChild(el);} } });
            };
            if(typeof Chart === 'undefined'){ loadScript('https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js', doChart); } else doChart();
        }

        function loadRecentShops(){ safeFetch(window.AgentConfig.urls.shopsRecent).then(data=> renderRecent(data)).catch(()=>{ safeFetch(window.AgentConfig.urls.shopsSearch+'?limit=6').then(data=> renderRecent(data)).catch(()=>{}); }); }
        function renderRecent(items){ if(!recentTableBody) return; recentTableBody.innerHTML=''; if(!Array.isArray(items)||!items.length){ const tr=document.createElement('tr'); tr.innerHTML='<td colspan="4" class="text-muted small">No recent shops</td>'; recentTableBody.appendChild(tr); return; } items.slice(0,6).forEach(it=>{ const tr=document.createElement('tr'); const name = it.name||it.shop||'—'; const domain = it.domain||''; const plan = (it.plan && it.plan.name) || it.plan || '—'; const isUn = it.uninstalled||it.is_uninstalled||false; const unAt = it.uninstalled_at||it.deleted_at||null; const status = isUn? '<span class="badge bg-danger">Uninstalled</span>' : '<span class="badge bg-success">Active</span>'; const detailDate = unAt? '<div class="small text-muted">'+new Date(unAt).toLocaleString()+'</div>':''; tr.innerHTML = `<td><a href="${window.AgentConfig.urls.shopsBase}/${it.id}/view">${name}</a></td><td class="d-none d-md-table-cell">${domain}</td><td class="d-none d-lg-table-cell">${plan}</td><td class="text-end">${status}${detailDate}</td>`; recentTableBody.appendChild(tr); }); }

        function loadLatestLists(){ safeFetch(window.AgentConfig.urls.shopsLatestInstalls).then(items=>{ const el = document.getElementById('latest-installs-list'); if(!el) return; el.innerHTML=''; if(!items.length) el.innerHTML='<li class="list-group-item text-muted">No recent installs</li>'; items.forEach(it=>{ const li=document.createElement('li'); li.className='list-group-item'; li.innerHTML = `<div class="d-flex justify-content-between"><div><a href="${window.AgentConfig.urls.shopsBase}/${it.id}/view">${it.shop}</a><div class="small text-muted">${it.domain||''}</div></div><div class="small text-muted">${new Date(it.installed_at).toLocaleString()}</div></div>`; el.appendChild(li); }); }).catch(()=>{ const el = document.getElementById('latest-installs-list'); if(el) el.innerHTML='<li class="list-group-item text-muted">Error loading</li>'; });
            safeFetch(window.AgentConfig.urls.shopsLatestUninstalls).then(items=>{ const el = document.getElementById('latest-uninstalls-list'); if(!el) return; el.innerHTML=''; if(!items.length) el.innerHTML='<li class="list-group-item text-muted">No recent uninstalls</li>'; items.forEach(it=>{ const li=document.createElement('li'); li.className='list-group-item'; const unAt = it.uninstalled_at || it.deleted_at || null; const when = unAt? new Date(unAt).toLocaleString() : 'Unknown'; li.innerHTML = `<div class="d-flex justify-content-between"><div><a href="${window.AgentConfig.urls.shopsBase}/${it.id}/view">${it.shop}</a><div class="small text-muted">${it.domain||''}</div></div><div class="small text-danger">${when}</div></div>`; el.appendChild(li); }); }).catch(()=>{ const el = document.getElementById('latest-uninstalls-list'); if(el) el.innerHTML='<li class="list-group-item text-muted">Error loading</li>'; }); }

        function loadSubscriptionShops(){ const tb = document.querySelector('#subscription-shops-table tbody'); if(!tb) return; safeFetch(window.AgentConfig.urls.shopsSearch + '?limit=8').then(data=>{ const items = data.items || data || []; tb.innerHTML=''; if(!items.length){ tb.innerHTML = '<tr><td colspan="4" class="text-muted">No shops found</td></tr>'; return; } items.forEach(it=>{ const tr = document.createElement('tr'); const name = it.name || it.shop || '—'; const domain = it.domain || ''; const plan = (it.activeCharge && it.activeCharge.name) || (it.plan && it.plan.name) || '—'; const actions = []; actions.push(`<a class="btn btn-sm btn-outline-primary me-1" href="${window.AgentConfig.urls.shopsBase}/${it.id}/view">View</a>`); actions.push(`<a class="btn btn-sm btn-outline-secondary me-1" href="https://${domain || it.shop}" target="_blank">Open</a>`); actions.push(`<a class="btn btn-sm btn-outline-success me-1" href="${window.AgentConfig.urls.shopsBase}/${it.id}/direct" target="_blank">Direct login</a>`); actions.push(`<a class="btn btn-sm btn-outline-dark" href="${window.AgentConfig.urls.ticketsRoute}?shop=${encodeURIComponent(it.shop||it.domain||'')}">Ticket</a>`); tr.innerHTML = `<td><strong>${name}</strong><div class="small text-muted">ID ${it.id}</div></td><td class="d-none d-md-table-cell">${domain}</td><td>${plan}</td><td class="text-end">${actions.join('')}</td>`; tb.appendChild(tr); }); }).catch(()=>{ const tb2 = document.querySelector('#subscription-shops-table tbody'); if(tb2) tb2.innerHTML = '<tr><td colspan="4" class="text-muted">Unable to load</td></tr>'; }); }

        loadActivityChart(); loadRecentShops(); loadLatestLists(); loadSubscriptionShops(); setInterval(loadActivityChart, 5 * 60 * 1000);
    }

    function initShops(){
        const searchInput = document.getElementById('shops-search');
        if(!searchInput) return;
        const planInput = document.getElementById('shops-plan');
        const searchBtn = document.getElementById('shops-search-btn');
        const clearBtn = document.getElementById('shops-clear-btn');
        const tableBody = document.querySelector('#shops-table tbody');
        const pagination = document.getElementById('shops-pagination');
        let currentPage = 1;

        function fetchShops(q='', plan='', page=1, status=''){
            const url = new URL(window.AgentConfig.urls.shopsSearch, window.location.origin);
            if(q) url.searchParams.set('q', q);
            if(plan) url.searchParams.set('plan', plan);
            if(status) url.searchParams.set('status', status);
            url.searchParams.set('page', page);
            url.searchParams.set('limit', 15);
            if(tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="text-muted">Loading...</td></tr>';
            safeFetch(url.toString()).then(data=>{ renderTable(data.items || data || []); renderPagination(data); }).catch(err=>{ if(tableBody) tableBody.innerHTML = '<tr><td colspan="6" class="text-danger">Error loading results</td></tr>'; if(pagination) pagination.innerHTML = ''; });
        }

        function renderTable(items){ if(!tableBody) return; tableBody.innerHTML=''; if(!Array.isArray(items) || !items.length){ tableBody.innerHTML = '<tr><td colspan="7" class="text-muted">No shops found</td></tr>'; return; }
            items.forEach(it=>{ const tr = document.createElement('tr'); const name = it.name || it.shop || '—'; const domain = it.domain || ''; const plan = (it.plan && it.plan.name) || it.plan || '—'; const status = it.uninstalled ? '<span class="badge bg-danger">Uninstalled</span>' : '<span class="badge bg-success">Installed</span>'; const uninstalledAt = it.uninstalled_at || it.deleted_at || null; const uninstalledDisplay = uninstalledAt ? new Date(uninstalledAt).toLocaleString() : '—'; const actionsArr = []; actionsArr.push(`<a class="btn btn-sm btn-outline-primary me-1" href="${window.AgentConfig.urls.shopsBase}/${it.id}/view">View</a>`); actionsArr.push(`<a class="btn btn-sm btn-outline-success me-1" href="${window.AgentConfig.urls.shopsBase}/${it.id}/direct" target="_blank">Direct</a>`); actionsArr.push(`<a class="btn btn-sm btn-outline-secondary me-1" href="https://${domain || it.shop}" target="_blank">Open</a>`); actionsArr.push(`<button class="btn btn-sm btn-outline-dark copy-domain" data-domain="${domain || it.shop}">Copy</button>`); const actions = actionsArr.join(''); tr.innerHTML = `<td>${it.id}</td><td>${name}</td><td class="d-none d-md-table-cell">${domain}</td><td class="d-none d-lg-table-cell">${plan}</td><td>${status}</td><td class="d-none d-lg-table-cell">${uninstalledDisplay}</td><td class="text-end">${actions}</td>`; tableBody.appendChild(tr); }); }

        function renderPagination(data){ if(!pagination) return; pagination.innerHTML=''; const total = data.total || (data.meta && data.meta.total) || 0; const per = data.per_page || (data.meta && data.meta.per_page) || 15; const current = data.current_page || (data.meta && data.meta.current_page) || currentPage; const last = data.last_page || (data.meta && data.meta.last_page) || Math.max(1, Math.ceil(total / per)); const prevLi = document.createElement('li'); prevLi.className = 'page-item ' + (current <= 1 ? 'disabled' : ''); prevLi.innerHTML = `<a class="page-link" href="#" data-page="${current-1}">Previous</a>`; pagination.appendChild(prevLi); for(let p=1;p<=Math.min(last,7);p++){ const li=document.createElement('li'); li.className = 'page-item ' + (p===current? 'active':''); li.innerHTML = `<a class="page-link" href="#" data-page="${p}">${p}</a>`; pagination.appendChild(li); } const nextLi = document.createElement('li'); nextLi.className = 'page-item ' + (current >= last ? 'disabled' : ''); nextLi.innerHTML = `<a class="page-link" href="#" data-page="${current+1}">Next</a>`; pagination.appendChild(nextLi); pagination.querySelectorAll('a.page-link').forEach(a=>{ a.addEventListener('click', function(e){ e.preventDefault(); const p = Number(this.getAttribute('data-page')) || 1; if(p<1) return; currentPage = p; fetchShops(searchInput.value.trim(), planInput.value.trim(), currentPage); }); }); }

        searchBtn.addEventListener('click', ()=>{ currentPage=1; fetchShops(searchInput.value.trim(), planInput.value.trim(), 1, document.getElementById('shops-status').value); });
        clearBtn.addEventListener('click', ()=>{ searchInput.value=''; planInput.value=''; document.getElementById('shops-status').value=''; currentPage=1; fetchShops('', '', 1); });
        searchInput.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); searchBtn.click(); } }); planInput.addEventListener('keydown', function(e){ if(e.key==='Enter'){ e.preventDefault(); searchBtn.click(); } }); document.getElementById('shops-status').addEventListener('change', function(){ currentPage=1; fetchShops(searchInput.value.trim(), planInput.value.trim(), 1, this.value); });

        fetchShops();
        document.querySelector('#shops-table tbody').addEventListener('click', function(e){ const btn = e.target.closest('.copy-domain'); if(!btn) return; const domain = btn.getAttribute('data-domain')||''; navigator.clipboard.writeText(domain).then(()=>{ const old = btn.innerText; btn.innerText='Copied'; setTimeout(()=> btn.innerText=old, 1200); }).catch(()=> alert('Copy failed')); });
    }

    function initLogs(){
        const boxes = document.querySelectorAll('.show_more'); boxes.forEach(box => box.addEventListener('click', ()=> box.classList.toggle('no-max-height')));
        const input = document.getElementById('myInput'); const table = document.getElementById('myTable');
        if(input && table){ input.addEventListener('input', function(){ const filter = input.value.trim().toLowerCase(); const rows = table.querySelectorAll('tbody tr'); rows.forEach(r=>{ const text = r.textContent.toLowerCase(); r.style.display = (filter==='' || text.indexOf(filter)!==-1) ? '' : 'none'; }); }); }
        const level = (document.querySelector('select[name="level"]') || {}).value || '';
        if(level){ const sel = document.querySelector('.label_selector'); if(sel) sel.value = level; }
        document.querySelectorAll('.copy-log').forEach(btn=>{ btn.addEventListener('click', function(){ const msg = this.getAttribute('data-message') || ''; navigator.clipboard.writeText(msg).then(()=>{ const orig = this.innerText; this.innerText='Copied'; setTimeout(()=> this.innerText = orig, 1400); }).catch(()=>{ alert('Copy failed'); }); }); });
    }

    function initShopDetail(){ const copyBtns = document.querySelectorAll('.copy-field'); copyBtns.forEach(btn=> btn.addEventListener('click', function(){ const v = this.getAttribute('data-value')||''; navigator.clipboard.writeText(v).then(()=>{ const old = this.innerText; this.innerText='Copied'; setTimeout(()=> this.innerText=old, 1200); }).catch(()=> alert('Copy failed')); })); }

    function initTickets(){ const toggles = document.querySelectorAll('.toggle-ticket-details'); toggles.forEach(btn=> btn.addEventListener('click', function(){ const tr = this.closest('tr'); if(!tr) return; const next = tr.nextElementSibling; if(next) next.style.display = (next.style.display === 'none' || next.style.display === '') ? '' : 'none'; })); }

    document.addEventListener('DOMContentLoaded', function(){ try{ initDashboard(); }catch(e){} try{ initShops(); }catch(e){} try{ initLogs(); }catch(e){} try{ initShopDetail(); }catch(e){} try{ initTickets(); }catch(e){} });
})();
