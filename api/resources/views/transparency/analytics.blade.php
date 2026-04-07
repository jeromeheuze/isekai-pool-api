<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Public Analytics - isekai-pool.com</title>
    <style>
        :root { --bg:#0d0f14; --card:#141720; --border:#1e2330; --text:#e5e7eb; --muted:#6b7280; --accent:#7c6af7; }
        body { margin:0; font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; background:var(--bg); color:var(--text); }
        .wrap { max-width: 980px; margin: 0 auto; padding: 1.25rem; }
        .card { background: var(--card); border:1px solid var(--border); border-radius:10px; padding: 1rem; margin-bottom: 1rem; }
        table { width:100%; border-collapse: collapse; font-size:13px; }
        th, td { text-align:left; border-bottom: 1px solid var(--border); padding: .5rem .6rem; }
        th { color: var(--muted); font-weight: 400; }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .muted { color: var(--muted); font-size: 13px; }
        .grid { display:grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: .8rem; }
    </style>
</head>
<body>
<div class="wrap">
    <h1>Public minimal analytics</h1>
    <p class="muted">
        Window: last 30 days (from {{ $fromDate }}). Public JSON: <a href="{{ url('/transparency/analytics.json') }}">/transparency/analytics.json</a>.
    </p>

    <div class="grid">
        <div class="card">
            <div class="muted">Total page views (30d)</div>
            <div>{{ number_format((int) ($totals->views ?? 0)) }}</div>
        </div>
        <div class="card">
            <div class="muted">Daily page buckets</div>
            <div>{{ number_format((int) ($totals->rows_count ?? 0)) }}</div>
        </div>
    </div>

    <div class="card">
        <h2>What we collect</h2>
        <ul>
            <li>Path + host + date + aggregate view count.</li>
            <li>No cookies, no user IDs, no wallet addresses, no fingerprinting.</li>
            <li>No personal profiling.</li>
        </ul>
    </div>

    <div class="card">
        <h2>Views by host (30d)</h2>
        <table>
            <thead><tr><th>Host</th><th>Views</th></tr></thead>
            <tbody>
            @forelse($hosts as $row)
                <tr><td>{{ $row->host }}</td><td>{{ number_format((int) $row->views) }}</td></tr>
            @empty
                <tr><td colspan="2" class="muted">No data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Top pages (30d)</h2>
        <table>
            <thead><tr><th>Host</th><th>Path</th><th>Views</th></tr></thead>
            <tbody>
            @forelse($topPages as $row)
                <tr>
                    <td>{{ $row->host }}</td>
                    <td>{{ $row->path }}</td>
                    <td>{{ number_format((int) $row->views) }}</td>
                </tr>
            @empty
                <tr><td colspan="3" class="muted">No data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="card">
        <h2>Daily totals</h2>
        <table>
            <thead><tr><th>Date</th><th>Views</th></tr></thead>
            <tbody>
            @forelse($daily as $row)
                <tr><td>{{ $row->event_date }}</td><td>{{ number_format((int) $row->views) }}</td></tr>
            @empty
                <tr><td colspan="2" class="muted">No data yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
