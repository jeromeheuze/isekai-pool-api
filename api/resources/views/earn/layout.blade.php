<!DOCTYPE html>
<html lang="en" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Earn KOTO') — Isekai Pool</title>
    <link rel="icon" href="https://isekai-pool.com/favicon.ico?v=2" sizes="any" />
    <link rel="icon" type="image/png" href="https://isekai-pool.com/favicon-32x32.png?v=2" sizes="32x32" />
    <link rel="icon" type="image/png" href="https://isekai-pool.com/favicon-16x16.png?v=2" sizes="16x16" />
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @php
        $site = 'https://isekai-pool.com';
        $isEarn = request()->is('earn*');
    @endphp
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
            display: flex;
            flex-direction: column;
            background: var(--bg);
            color: var(--text);
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 14px;
            line-height: 1.5;
        }
        a { color: var(--accent); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .wrap {
            flex: 1;
            width: 100%;
            max-width: 960px;
            margin: 0 auto;
            padding: 1rem 1.25rem 3rem;
        }
        nav.site-nav {
            border-bottom: 1px solid var(--border);
            background: rgba(13, 15, 20, 0.85);
            backdrop-filter: blur(8px);
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .nav-inner {
            max-width: 72rem;
            margin: 0 auto;
            padding: 1rem 1.25rem;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }
        .nav-inner > a:first-child img {
            height: 2rem;
            width: auto;
            display: block;
        }
        @media (min-width: 640px) {
            .nav-inner > a:first-child img { height: 2.25rem; }
        }
        .nav-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 1rem 1.5rem;
            font-size: 13px;
            color: var(--muted);
        }
        .nav-links a {
            color: var(--muted);
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .nav-links a:hover { color: #fff; text-decoration: none; }
        .nav-links a.nav-active { color: #fff; }
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
        .site-footer {
            border-top: 1px solid var(--border);
            background: rgba(13, 15, 20, 0.5);
            margin-top: auto;
        }
        .footer-inner {
            max-width: 72rem;
            margin: 0 auto;
            padding: 3rem 1.25rem 3.5rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2.5rem;
        }
        @media (min-width: 640px) {
            .footer-grid { grid-template-columns: repeat(2, 1fr); }
        }
        @media (min-width: 1024px) {
            .footer-grid {
                grid-template-columns: minmax(0, 1.2fr) repeat(4, 1fr);
                gap: 2.5rem;
            }
        }
        .footer-brand img {
            height: 2rem;
            width: auto;
            opacity: 0.9;
            margin-bottom: 1rem;
        }
        .footer-brand p {
            font-size: 12px;
            color: var(--muted);
            line-height: 1.6;
            max-width: 22rem;
            margin: 0;
        }
        .footer-col h3 {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #fff;
            margin: 0 0 1rem;
        }
        .footer-col ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .footer-col li { margin-bottom: 0.65rem; }
        .footer-col a {
            color: var(--muted);
            font-size: 13px;
            text-decoration: none;
        }
        .footer-col a:hover { color: #fff; text-decoration: none; }
        .footer-bottom {
            margin-top: 3rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border);
            display: flex;
            flex-direction: column;
            gap: 1.25rem;
            font-size: 12px;
            color: var(--muted);
        }
        @media (min-width: 768px) {
            .footer-bottom {
                flex-direction: row;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
            }
        }
        .footer-bottom a {
            color: var(--muted);
            text-decoration: none;
        }
        .footer-bottom a:hover { color: #fff; }
        .footer-bottom-links {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem 1.25rem;
        }
    </style>
    @stack('head')
</head>
<body>
    <nav class="site-nav" aria-label="Primary">
        <div class="nav-inner">
            <a href="{{ $site }}/" aria-label="isekai-pool.com — home" title="isekai-pool.com">
                <img src="{{ $site }}/assets/IsekaiPool_WhiteText.svg" alt="Isekai Pool" width="2000" height="600" decoding="async">
            </a>
            <div class="nav-links">
                <a href="{{ $site }}/" @class(['nav-active' => ! $isEarn])>Home</a>
                <a href="{{ $site }}/coins.html">Coins</a>
                <a href="{{ $site }}/faucet.html">KOTO Faucet</a>
                <a href="{{ url('/earn') }}" @class(['nav-active' => $isEarn])>Earn KOTO</a>
                <a href="{{ $site }}/koto-network.html">KOTO Network</a>
                <a href="{{ $site }}/guide.html">Mining Guide</a>
            </div>
        </div>
    </nav>
    <div class="wrap">
        @yield('content')
    </div>

    <footer class="site-footer">
        <div class="footer-inner">
            <div class="footer-grid">
                <div class="footer-brand">
                    <a href="{{ $site }}/" aria-label="isekai-pool.com home">
                        <img src="{{ $site }}/assets/IsekaiPool_WhiteText.svg" alt="Isekai Pool" width="2000" height="600" decoding="async">
                    </a>
                    <p>Public RPC nodes for CPU-only yespower and yescrypt coins. Solo mine or build tools — no API key required.</p>
                </div>
                <div class="footer-col">
                    <h3>Explore</h3>
                    <ul>
                        <li><a href="{{ $site }}/coins.html">Coins</a></li>
                        <li><a href="{{ $site }}/guide.html">Mining guide</a></li>
                        <li><a href="{{ $site }}/status.html">Node status</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>KOTO</h3>
                    <ul>
                        <li><a href="https://explorer.isekai-pool.com" target="_blank" rel="noopener">KOTO Explorer</a></li>
                        <li><a href="{{ $site }}/koto.html">KOTO mining</a></li>
                        <li><a href="{{ $site }}/faucet.html">KOTO faucet</a></li>
                        <li><a href="{{ $site }}/koto-network.html">KOTO network</a></li>
                        <li><a href="https://koto.isekai-pool.com" target="_blank" rel="noopener">KOTO pool (stratum)</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Coins</h3>
                    <ul>
                        <li><a href="{{ $site }}/ytn.html">Yenten (YTN)</a></li>
                        <li><a href="{{ $site }}/koto.html">Koto (KOTO)</a></li>
                        <li><a href="{{ $site }}/tdc.html">Tidecoin (TDC)</a></li>
                    </ul>
                </div>
                <div class="footer-col">
                    <h3>Project</h3>
                    <ul>
                        <li><a href="{{ $site }}/about.html">About</a></li>
                        <li><a href="{{ $site }}/pages/api-docs.html">API documentation</a></li>
                        <li><a href="https://github.com/jeromeheuze/isekai-pool-api" target="_blank" rel="noopener">GitHub</a></li>
                        <li><a href="https://api.isekai-pool.com/transparency/analytics" target="_blank" rel="noopener">Public Analytics</a></li>
                        <li><a href="https://www.reddit.com/r/KotoCoin/" target="_blank" rel="noopener">Reddit</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <span>isekai-pool.com — 異世界インフラ</span>
                <div class="footer-bottom-links">
                    <a href="https://gameglass.live/" target="_blank" rel="noopener" title="Partner">GameGlass.Live</a>
                    <a href="https://www.interserver.net/r/1131521" target="_blank" rel="noopener sponsored">Hosted on InterServer VPS</a>
                </div>
            </div>
        </div>
    </footer>

    @include('partials.analytics-tracker')
    @stack('scripts')
</body>
</html>
