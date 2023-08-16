@extends('msdev2::layout.master')
@section('content')
<section class="content">
    <div class="top_content">
        <div class="top_content_left">
            <div>
                <strong style="padding-top: 7px;display: inline-block;"> <span >Showing Log</span></strong>
            </div>
            <div class="log_filter" >
                <input type="text" id="myInput" onkeyup="searchName()" placeholder="Search for names.." title="Type in a name">
            </div>
        </div>

        <div class="top_content_right">
            <p class="dt_box">Select Tag:
                <select class="label_selector" onChange="filterTag()">
                    @foreach ($data["label"] as $label)
                        <option value="{{$label}}">{{ $label }}</option>
                    @endforeach
                </select>
            </p>
            <p class="dt_box">Select Date:
                <select class="date_selector" onChange="init(selectedDate)">
                    @foreach ($data["available_log_dates"] as $availableDate)
                        <option  value="{{ $availableDate }}">{{ $availableDate }}</option>
                    @endforeach
                </select>
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
                @foreach ($data["logs"] as $log)
                <tr class="{{ $log["type"] }}">
                    <td>{{ $log["timestamp"] }}</td>
                    <td>{{$log["env"]}}</td>
                    <td><span class="badge {{ $log["type"] }}">{{ $log["type"] }}</span></td>
                    <td class="no-wrap"><div class="show_more">{{ $log["message"] }}</div></td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
</section>
@endsection
@section('scripts')
<script>
const boxes = document.querySelectorAll('.show_more');
boxes.forEach(box => {
  box.addEventListener('click', function handleClick(event) {
    // box.setAttribute('style', 'background-color: yellow;');
    box.classList.toggle("no-max-height");
  });
});
function searchName(){
    let input, filter, table, tr, td, i, txtValue;
    input = document.getElementById("myInput");
    filter = input.value.toUpperCase();
    table = document.getElementById("myTable");
    tr = table.getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++) {
        txtValue = tr[i].textContent || tr[i].innerText;
        if (txtValue) {
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
            } else {
                tr[i].style.display = "none";
            }
        }
    }
}
function filterTag(){
    let lable= document.querySelector('.label_selector').value
    let filter = lable.toUpperCase();
    let table = document.getElementById("myTable");
    let tr = table.getElementsByTagName("tr");
    for (i = 0; i < tr.length; i++) {
        if (tr[i].querySelectorAll('.'+filter)) {
            tr[i].style.display = "";
        } else {
            tr[i].style.display = "none";
        }
    }
}
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
