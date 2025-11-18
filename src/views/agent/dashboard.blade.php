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
<!-- Subscription shops quick list -->
<div class="col-12 mt-3">
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <div>
                <div class="small text-muted">Quick Access</div>
                <div class="fw-bold">Subscription Shops</div>
            </div>
            <a href="{{ route('msdev2.agent.shops') }}" class="btn btn-sm btn-outline-primary">Full shops list</a>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0" id="subscription-shops-table">
                    <thead>
                        <tr>
                            <th>Shop</th>
                            <th class="d-none d-md-table-cell">Domain</th>
                            <th>Plan</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr><td colspan="4" class="text-muted">Loading subscription shops...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection