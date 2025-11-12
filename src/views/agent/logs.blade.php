@extends('msdev2::layout.agent')
@section('content')
<section class="content">
    @if($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    <div class="top_content">
        <div class="top_content_left">
            <div>
                <strong style="padding-top: 7px;display: inline-block;"> <span >Showing Log</span></strong>
            </div>
            <div class="log_filter" >
                <form method="get" class="d-flex" id="logFilterForm">
                    <input type="text" name="q" id="myInput" value="{{ $data['query'] ?? '' }}" placeholder="Search in message, env or timestamp..." class="form-control form-control-sm me-2">
                    <select name="level" class="form-control form-control-sm me-2">
                        <option value="">All levels</option>
                        @foreach ($data["label"] as $label)
                            <option value="{{ $label }}" {{ (isset($data['level']) && $data['level']==$label) ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <button class="btn btn-sm btn-primary me-2" type="submit">Filter</button>
                    <a class="btn btn-sm btn-outline-secondary" href="{{ route('msdev2.agent.logs') }}">Reset</a>
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
                <a class="btn btn-sm btn-outline-success ms-2" href="{{ route('msdev2.agent.logs.download', ['date' => $data['selected_date'] ?? $data['available_log_dates'][0] ?? '']) }}">Download</a>
                <form method="post" action="{{ route('msdev2.agent.logs.clear') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="date" value="{{ $data['selected_date'] ?? $data['available_log_dates'][0] ?? '' }}">
                    <button class="btn btn-sm btn-outline-warning" type="submit" onclick="return confirm('Clear contents of this log file? This cannot be undone.')">Clear</button>
                </form>
                <form method="post" action="{{ route('msdev2.agent.logs.delete') }}" class="d-inline ms-2">
                    @csrf
                    <input type="hidden" name="date" value="{{ $data['selected_date'] ?? $data['available_log_dates'][0] ?? '' }}">
                    <button class="btn btn-sm btn-outline-danger" type="submit" onclick="return confirm('Delete this log file? This cannot be undone.')">Delete</button>
                </form>
                <input type="hidden" name="date" />
            </p>
        </div>
    </div>

    <div>
        <div class="responsive_table">
            <table id="myTable">
                <thead>
                <tr>
                    <td width="140">Timestamp</td>
                    <td width="120">Env</td>
                    <td width="120">Type</td>
                    <td>Message</td>
                </tr>
                </thead>
                <tbody>
                @foreach ($data["logs"] as $log)
                <tr class="{{ strtolower($log["type"]) }}">
                    <td>{{ $log["timestamp"] }}</td>
                    <td>{{$log["env"]}}</td>
                    <td><span class="badge {{ strtolower($log["type"]) }}">{{ $log["type"] }}</span></td>
                    <td class="no-wrap"><div class="show_more">{{ $log["message"] }}</div></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function(){
    // Expand/collapse long messages
    const boxes = document.querySelectorAll('.show_more');
    boxes.forEach(box => box.addEventListener('click', ()=> box.classList.toggle('no-max-height')));

    // Client-side quick filter while typing (non-destructive)
    const input = document.getElementById('myInput');
    const table = document.getElementById('myTable');
    if(input){
        input.addEventListener('input', function(){
            const filter = input.value.trim().toLowerCase();
            const rows = table.querySelectorAll('tbody tr');
            rows.forEach(r => {
                const text = r.textContent.toLowerCase();
                r.style.display = (filter === '' || text.indexOf(filter) !== -1) ? '' : 'none';
            });
        });
    }

    // If there is a selected level from server, reflect it
    const level = '{{ $data['level'] ?? '' }}';
    if(level){
        const sel = document.querySelector('.label_selector');
        if(sel) sel.value = level;
    }
});
</script>
@endsection
@section('styles')
<style>
    .show_more {
        max-height: 32px;
        overflow: hidden;
        cursor: pointer;
    }
    .no-max-height{
        max-height: initial;
    }
    body {
        margin: 0;
        padding: 0;
        background: #f4f4f4;
        font-family: sans-serif;
    }
    .no-wrap{
        word-break: break-word;
    }
    p.dt_box {
        display: inline-block;
    }
    .btn {
        text-decoration: none;
        background: antiquewhite;
        padding: 5px 12px;
        border-radius: 25px;
    }

    .content {
        display: block;
        margin-top: 65px;
        padding: 15px;
        background: #fff;
        min-height: 100px;
    }

    .content .date_selector,.content .label_selector {
        min-height: 26px;
        min-width: 130px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .top_content {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .top_content .top_content_left {
        display: flex;
    }

    .top_content .top_content_left .log_filter {
        display: flex;
        align-items: center;
        margin-left: 15px;
    }

    .top_content .top_content_left .log_filter .log_type_item {
        margin-right: 4px;
        background: #eae9e9;
        max-height: 20px;
        font-size: 11px;
        box-sizing: border-box;
        padding: 4px 6px;
        cursor: pointer;
    }

    .top_content .top_content_left .log_filter .log_type_item.active {
        background: #2f2e2f;
        color: white;
    }

    .top_content .top_content_left .log_filter .log_type_item.clear {
        background: #607D8B;
        color: white;
    }

    table {
        border: 1px solid #ccc;
        border-collapse: collapse;
        margin: 0;
        padding: 0;
        width: 100%;
    }

    table tr {
        border: 1px solid #e8e8e8;
        padding: 5px;
    }
    table tr:hover {
        background: #f4f4f4;
    }
    thead tr td {
        background: #717171;
        color: #fff;
    }

    table th,
    table td {
        padding: 5px;
        font-size: 14px;
        color: #666;
    }

    table th {
        font-size: 14px;
        letter-spacing: 1px;
        text-transform: uppercase;
    }

    @media screen and (max-width: 700px) {
        .top_content {
            flex-direction: column;
        }

        .top_content .top_content_left {
            flex-direction: column;
        }

        .top_content .log_filter {
            flex-wrap: wrap;
        }

        .top_content .log_filter .log_type_item {
            margin-bottom: 3px;
        }
    }

    @media screen and (max-width: 600px) {

        .content {
            margin-top: 90px;
        }

        .btn {
            font-size: 13px;
        }

        .dt_box,
        .selected_date {
            text-align: center;
        }

        .responsive_table {
            max-width: 100%;
            overflow-x: auto;
        }

        table {
            border: 0;
        }

        table thead {
            display: none;
        }

        table tr {
            border-bottom: 2px solid #ddd;
            display: block;
            margin-bottom: 10px;
        }

        table td {
            border-bottom: 1px dotted #ccc;
            display: block;
            font-size: 15px;
        }

        table td:last-child {
            border-bottom: 0;
        }

        table td:before {
            content: attr(data-label);
            float: left;
            font-weight: bold;
            text-transform: uppercase;
        }
    }

    .badge {
        padding: 2px 8px;
        -webkit-border-radius: 25px;
        -moz-border-radius: 25px;
        border-radius: 25px;
        font-size: 11px;
    }

    .badge.info {
        background: #6bb5b5;
        color: #fff;
    }

    .badge.warning {
        background: #f7be57;
    }

    .badge.critical {
        background: #de4f4f;
        color: #fff;
    }

    .badge.emergency {
        background: #ff6060;
        color: white;
    }

    .badge.notice {
        background: bisque;
    }

    .badge.debug {
        background: #8e8c8c;
        color: white;
    }

    .badge.alert {
        background: #4ba4ea;
        color: white;
    }

    .badge.error {
        background: #c36a6a;
        color: white;
    }
</style>
@endsection
