<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Earn KOTO') — Isekai Pool</title>
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
        .wrap { max-width: 960px; margin: 0 auto; padding: 1rem 1.25rem 3rem; }
        nav {
            border-bottom: 1px solid var(--border);
            background: rgba(13, 15, 20, 0.85);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 40;
        }
        .nav-inner {
            max-width: 960px;
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
        .card {
            border: 1px solid var(--border);
            border-radius: 10px;
            background: var(--card);
            padding: 1rem 1.25rem;
            margin-bottom: 1rem;
        }
        .btn {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            background: var(--accent);
            color: #fff;
            font-size: 13px;
            border: none;
            cursor: pointer;
            font-family: inherit;
        }
        .btn:hover { filter: brightness(1.08); color: #fff; text-decoration: none; }
        .btn-ghost {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text);
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
                <a href="/earn">Earn hub</a>
                <a href="/earn/shrine">Shrine</a>
                <a href="/earn/kanji">Kanji</a>
                <a href="/earn/retro">Retro</a>
                <a href="/faucet.html">Faucet (full test)</a>
                <a href="https://explorer.isekai-pool.com/">Explorer</a>
                <a href="https://api.isekai-pool.com/transparency/analytics" target="_blank" rel="noopener">Public Analytics</a>
                <a href="https://www.reddit.com/r/KotoCoin/" target="_blank" rel="noopener">Reddit</a>
            </div>
        </div>
    </nav>
    <div class="wrap">
        @yield('content')
    </div>
    @include('partials.analytics-tracker')
    @stack('scripts')
</body>
</html>
