@extends('msdev2::layout.agent')
@section('content')
<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @php
        $logsArr = $data['logs'] ?? [];
        $totalLogs = is_countable($logsArr) ? count($logsArr) : 0;
        $errorsCount = 0; $warningsCount = 0; $infoCount = 0;
        foreach($logsArr as $ll){
            $t = strtolower($ll['type'] ?? ($ll->type ?? ''));
            if(str_contains($t,'error') || str_contains($t,'exception')) $errorsCount++;
            elseif(str_contains($t,'warn')) $warningsCount++;
            else $infoCount++;
        }
    @endphp

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Total Logs</div>
                    <div class="fw-bold">{{ $totalLogs }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Errors</div>
                    <div class="fw-bold text-danger">{{ $errorsCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Warnings</div>
                    <div class="fw-bold text-warning">{{ $warningsCount }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="small text-muted">Info / Other</div>
                    <div class="fw-bold">{{ $infoCount }}</div>
                </div>
            </div>
        </div>
    </div>
    <div class="top_content">
        <div class="top_content_left">
            <div>
                <strong style="padding-top: 7px;display: inline-block;"> <span >Showing Log</span></strong>
                @if(!empty($data['selected_shop']))
                    <div style="display:inline-block;margin-left:10px;color:#555">for <strong>{{ $data['selected_shop'] }}</strong></div>
                @endif
            </div>
            <div class="log_filter w-100">
                <form method="get" class="row g-2 align-items-center" id="logFilterForm">
                    <div class="col-md-4">
                        <input type="text" name="q" id="myInput" value="{{ $data['query'] ?? '' }}" placeholder="Search message, env or timestamp..." class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2">
                        <select name="shop" class="form-control form-control-sm">
                            <option value="">All shops</option>
                            @foreach($data['shops'] as $s)
                                <option value="{{ $s['shop'] }}" {{ (isset($data['selected_shop']) && $data['selected_shop']==$s['shop']) ? 'selected' : '' }}>{{ $s['shop'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="level" class="form-control form-control-sm">
                            <option value="">All levels</option>
                            @foreach ($data["label"] as $label)
                                <option value="{{ $label }}" {{ (isset($data['level']) && $data['level']==$label) ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <input type="text" name="path" value="{{ request('path','') }}" placeholder="Filter by path" class="form-control form-control-sm">
                    </div>
                    <div class="col-md-2 d-flex justify-content-end">
                        <button class="btn btn-sm btn-primary me-2" type="submit">Filter</button>
                        <a class="btn btn-sm btn-outline-secondary me-2" href="{{ route('msdev2.agent.logs') }}">Reset</a>
                    </div>
                </form>
            </div>
        </div>

        <div class="top_content_right">
            <p class="dt_box">Select Tag:
                {{-- kept for compatibility; use the form controls on the left instead --}}
                <select class="label_selector" onchange="document.getElementById('logFilterForm').level.value=this.value; document.getElementById('logFilterForm').submit();">
                    <option value="">All</option>
                    @foreach ($data["label"] as $label)
                        <option value="{{$label}}" {{ (isset($data['level']) && $data['level']==$label) ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p class="dt_box">Select Date:
                <select class="date_selector" onchange="document.getElementById('logFilterForm').date.value=this.value; document.getElementById('logFilterForm').submit();" name="date">
                    @foreach ($data["available_log_dates"] as $availableDate)
                        <option  value="{{ $availableDate }}" {{ (isset($data['selected_date']) && $data['selected_date']==$availableDate) ? 'selected' : '' }}>{{ $availableDate }}</option>
                    @endforeach
                </select>
                <a class="btn btn-sm btn-success ms-2" href="{{ route('msdev2.agent.logs.download', array_filter(['date' => $data['selected_date'] ?? $data['available_log_dates'][0] ?? '', 'shop' => $data['selected_shop'] ?? null])) }}">Export CSV</a>
                <form method="post" action="{{ route('msdev2.agent.logs.clear') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="date" value="{{ $data['selected_date'] ?? $data['available_log_dates'][0] ?? '' }}">
                    <input type="hidden" name="shop" value="{{ $data['selected_shop'] ?? '' }}">
                    <button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm('Clear contents of this log file? This cannot be undone.')">Clear</button>
                </form>
                <form method="post" action="{{ route('msdev2.agent.logs.delete') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="date" value="{{ $data['selected_date'] ?? $data['available_log_dates'][0] ?? '' }}">
                    <input type="hidden" name="shop" value="{{ $data['selected_shop'] ?? '' }}">
                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this log file? This cannot be undone.')">Delete</button>
                </form>
                <form method="post" action="{{ route('msdev2.agent.logs.delete') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="all" value="1">
                    <button class="btn btn-sm btn-danger" type="submit" onclick="return confirm('Delete ALL logs? This cannot be undone.')">Delete All</button>
                </form>
                <input type="hidden" name="date" />
            </p>
        </div>
    </div>

    <div>
        <div class="log-list">
            @foreach ($data["logs"] as $log)
                @php
                    $ts = $log["timestamp"] ?? null;
                    try { $dt = \Carbon\Carbon::parse($ts); } catch (\Exception $e) { $dt = null; }
                    $date = $dt ? $dt->format('Y-m-d') : ($ts ?: '');
                    $time = $dt ? $dt->format('H:i:s') : '';
                    $type = strtolower($log['type'] ?? 'info');
                @endphp
                <div class="log-item mb-2">
                    <div class="log-left">
                        <div class="ts-date">{{ $date }}</div>
                        <div class="ts-time">{{ $time }}</div>
                        <div class="text-muted small mt-2">{{ $log['env'] ?? '' }}</div>
                    </div>
                    <div class="log-main">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="log-message">
                                {!! nl2br(e(
                                    strlen($log['message'] ?? '') > 500 ? substr($log['message'],0,500).'...' : ($log['message'] ?? '')
                                )) !!}
                            </div>
                            <div>
                                <span class="badge {{ $type }}">{{ strtoupper($log['type'] ?? 'INFO') }}</span>
                            </div>
                        </div>
                        @if(!empty($log['context']) || !empty($log['path']))
                            <div class="log-meta">
                                @if(!empty($log['path'])) <div>Path: {{ $log['path'] }}</div> @endif
                                @if(!empty($log['context'])) <div>Context: <small class="text-muted">{{ is_string($log['context'])? $log['context'] : json_encode($log['context']) }}</small></div> @endif
                            </div>
                        @endif
                    </div>
                    <div class="log-copy">
                        <button class="btn btn-outline-secondary copy-log" data-message="{{ e($log['message']) }}" title="Copy message">Copy</button>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    </section>
@endsection
