<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'KOTO Explorer') — isekai-pool.com</title>
    <link rel="icon" href="https://isekai-pool.com/favicon.ico?v=2" sizes="any" />
    <link rel="icon" type="image/png" href="https://isekai-pool.com/favicon-32x32.png?v=2" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://isekai-pool.com/favicon-16x16.png?v=2" sizes="16x16" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg: #0d0f14;
            --card: #141720;
            --border: #1e2330;
            --accent: #7c6af7;
            --gold: #f0c040;
            --muted: #6b7280;
            --text: #e5e7eb;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            background: var(--bg);
            color: var(--text);
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap { max-width: 1100px; margin: 0 auto; padding: 1rem 1.25rem 3rem; }
        nav {
            border-bottom: 1px solid var(--border);
            background: rgba(13, 15, 20, 0.85);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .nav-inner {
            max-width: 1100px;
            margin: 0 auto;
            padding: 1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .nav-inner img { height: 2rem; width: auto; }
        .nav-links { display: flex; flex-wrap: wrap; gap: 1rem; font-size: 13px; color: var(--muted); }
        .nav-links a { color: var(--muted); }
        .nav-links a:hover { color: #fff; }
        h1 { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.5rem; }
        h2 { font-size: 1rem; font-weight: 600; margin: 2rem 0 0.75rem; color: #d1d5db; }
        .muted { color: var(--muted); font-size: 13px; }
        .bar {
            display: flex; flex-wrap: wrap; gap: 1rem 1.5rem;
            padding: 1rem;
            border: 1px solid var(--border);
            border-radius: 8px;
            background: var(--card);
            margin-bottom: 1.5rem;
            font-size: 13px;
        }
        .bar strong { color: #fff; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; font-size: 13px; }
        th, td { text-align: left; padding: 0.5rem 0.6rem; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 400; }
        .table-scroll { overflow-x: auto; border: 1px solid var(--border); border-radius: 8px; }
        .mono { font-family: inherit; word-break: break-all; }
        .gold { color: var(--gold); }
        form.search { display: flex; gap: 0.5rem; flex-wrap: wrap; margin: 1rem 0 0; }
        form.search input[type="text"] {
            flex: 1; min-width: 200px;
            padding: 0.5rem 0.75rem;
            border-radius: 6px;
            border: 1px solid var(--border);
            background: #0d0f14;
            color: var(--text);
            font-family: inherit;
        }
        form.search button {
            padding: 0.5rem 1rem;
            border-radius: 6px;
            border: none;
            background: var(--accent);
            color: #fff;
            font-family: inherit;
            cursor: pointer;
        }
        .alert {
            padding: 0.75rem 1rem;
            border-radius: 8px;
            background: rgba(248, 113, 113, 0.12);
            border: 1px solid rgba(248, 113, 113, 0.35);
            color: #fecaca;
            margin-bottom: 1rem;
            font-size: 13px;
        }
        dl.grid { display: grid; grid-template-columns: 160px 1fr; gap: 0.35rem 1rem; font-size: 13px; }
        dl.grid dt { color: var(--muted); }
        @media (max-width: 640px) {
            dl.grid { grid-template-columns: 1fr; }
        }
    </style>
    @stack('head')
</head>
<body>
    <nav>
        <div class="nav-inner">
            <a href="https://isekai-pool.com/" title="isekai-pool.com">
                <img src="https://isekai-pool.com/assets/IsekaiPool_WhiteText.svg" alt="Isekai Pool" width="2000" height="600" decoding="async">
            </a>
            <div class="nav-links">
                <a href="{{ route('explorer.home') }}">Explorer home</a>
                <a href="https://isekai-pool.com/koto.html">KOTO mining</a>
                <a href="https://www.reddit.com/r/KotoCoin/" target="_blank" rel="noopener">Reddit</a>
                <a href="https://isekai-pool.com/">isekai-pool.com</a>
            </div>
        </div>
    </nav>
    <div class="wrap">
        @if (session('error'))
            <div class="alert">{{ session('error') }}</div>
        @endif
        @yield('content')
    </div>
    @include('partials.analytics-tracker')
</body>
</html>
