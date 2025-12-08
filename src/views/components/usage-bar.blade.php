<div class="credit-card">
    <style>
        .credit-card {
            background: #ffffff;
            border-radius: 12px;
            padding: 18px 20px;
            border: 1px solid #e5e5e5;
            margin: 24px auto;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
            max-width: 1100px;
        }
        .credit-header {
            display:flex;
            justify-content:space-between;
            align-items:center;
            margin-bottom:10px;
        }
        .credit-title {
            font-size:16px;
            font-weight:600;
        }
        .credit-sub {
            font-size:13px;
            color:#6d7175;
        }
        .credit-remaining {
            font-size:22px;
            font-weight:700;
            color:#008060;
            text-align:right;
        }
        .credit-remaining small {
            font-size:12px;
            color:#6d7175;
            font-weight:400;
        }
        .credit-bar-wrap {
            width:100%;
            background:#e4e5e7;
            border-radius:999px;
            overflow:hidden;
            height:10px;
            margin:8px 0 6px;
        }
        .credit-bar-fill {
            height:10px;
            background:#008060;
            transition:width .25s ease;
        }
        .credit-meta {
            display:flex;
            justify-content:space-between;
            font-size:12px;
            color:#6d7175;
        }
        .credit-tags {
            display:flex;
            gap:8px;
            margin-top:8px;
            font-size:11px;
        }
        .credit-tag {
            padding:4px 8px;
            border-radius:999px;
            border:1px solid #e2e3e5;
            background:#f6f6f7;
        }
    </style>

    <div class="credit-header">
        <div>
            <div class="credit-title">Credits Score</div>
            <div class="credit-sub">Free {{ $stats['free_limit'] }} / month + purchased credits</div>
        </div>
        <div class="credit-remaining">
            {{ $stats['total_remaining'] }}
            <small>&nbsp;credits remaining</small>
        </div>
    </div>

    <div class="credit-bar-wrap">
        <div class="credit-bar-fill" style="width: {{ $stats['percent'] }}%"></div>
    </div>

    <div class="credit-meta">
        <div>Used: {{ $stats['total_used'] }} credits</div>
        <div>{{ $stats['percent'] }}% of total consumed</div>
    </div>

    <div class="credit-tags">
        <div class="credit-tag">Free this month: {{ $stats['free_remaining'] }} left</div>
        <div class="credit-tag">Paid credits: {{ $stats['purchased_remaining'] }} left</div>
    </div>
</div>