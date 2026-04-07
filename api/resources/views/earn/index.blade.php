@extends('earn.layout')

@section('title', 'Earn KOTO')

@php
    $hubPaths = [
        'shrine_visit' => '/earn/shrine',
        'kanji_quiz' => '/earn/kanji',
        'retro_trivia' => '/earn/retro',
        'yokai_match' => '/earn/yokai-match',
        'yokai_quiz' => '/earn/yokai-quiz',
        'shrine_puzzle' => '/earn/shrine-puzzle',
        'map_explore' => '/earn/map-explore',
        'coffee_quiz' => '/earn/coffee-quiz',
        'daily_bonus' => '/earn/daily-bonus',
    ];

    /** @var array<string, array{title: string, desc: string, accent: string}> */
    $earnCardMeta = [
        'shrine_visit' => ['title' => 'Daily Shrine Visit', 'desc' => 'Pause for a short shrine moment, then claim.', 'accent' => 'violet'],
        'kanji_quiz' => ['title' => 'Kanji Quiz', 'desc' => 'Five multiple-choice kanji — score 4/5 or better.', 'accent' => 'teal'],
        'retro_trivia' => ['title' => 'Retro Game Trivia', 'desc' => 'Japanese retro consoles & games — 4/5 to pass.', 'accent' => 'coral'],
        'yokai_match' => ['title' => 'Yokai Match', 'desc' => 'Match all yokai pairs from folklore.', 'accent' => 'teal'],
        'yokai_quiz' => ['title' => 'Yokai Quiz', 'desc' => 'Five folklore questions — 4/5 to pass.', 'accent' => 'teal'],
        'shrine_puzzle' => ['title' => 'Shrine Puzzle', 'desc' => 'Put ritual steps in the correct order.', 'accent' => 'amber'],
        'map_explore' => ['title' => 'Map Explore', 'desc' => 'Visit checkpoints in order on the map.', 'accent' => 'amber'],
        'coffee_quiz' => ['title' => 'Coffee Quiz', 'desc' => 'Japanese coffee culture — 4/5 to pass.', 'accent' => 'teal'],
        'daily_bonus' => ['title' => 'Daily Bonus', 'desc' => 'Quick daily check-in for extra KOTO.', 'accent' => 'violet'],
    ];
@endphp

@section('content')
    {{-- Hero --}}
    <section class="earn-hub-hero" aria-labelledby="earn-hub-title">
        <div class="earn-hub-hero__torii" aria-hidden="true">
            <svg viewBox="0 0 120 100" width="120" height="100" focusable="false">
                <g fill="none" stroke="#7c6af7" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 88V72c0-18 18-28 48-28s48 10 48 28v16" />
                    <path d="M24 88V76M96 88V76" />
                    <path d="M60 44v36" />
                    <path d="M28 52c8-20 24-28 32-28s24 8 32 28" />
                    <path d="M8 44h104" />
                    <path d="M20 36h80" />
                </g>
            </svg>
        </div>
        <h1 id="earn-hub-title" class="earn-hub-hero__title">Earn KOTO</h1>
        <p class="earn-hub-hero__subtitle muted">
            Private CPU coin from Japan — learn, play, and claim rewards funded by the pool.
            Same faucet API as <a href="/faucet.html">/faucet.html</a>; this hub will grow into games and quizzes per the roadmap.
        </p>
    </section>

    {{-- Stats (content filled by existing loadStats) --}}
    <div id="earn-stats" class="earn-stats-bar">
        <p class="muted" style="margin:0;">Loading faucet stats…</p>
    </div>

    {{-- Wallet --}}
    <h2 class="earn-hub-section-title">Wallet (saved in browser)</h2>
    <div class="earn-wallet-card card">
        <label for="earn-wallet" class="muted" style="display:block;margin-bottom:0.35rem;">KOTO address</label>
        <input id="earn-wallet" type="text" autocomplete="off" placeholder="k1…"
            class="earn-wallet-input muted"
            style="width:100%;max-width:100%;padding:0.5rem 0.75rem;border-radius:6px;border:1px solid var(--border);background:#0d0f14;color:var(--text);font-family:inherit;font-size:13px;">
        <p class="muted" style="margin:0.75rem 0 0;font-size:12px;">Used to show per-activity availability from the API. Not sent to our server on this page except when you claim (Turnstile + POST).</p>
    </div>

    <h2 class="earn-hub-section-title">Activities</h2>
    <div class="earn-hub-grid">
        @foreach ($activities as $slug => $meta)
            @php
                $reward = $meta['reward'];
                $hubPath = $hubPaths[$slug] ?? null;
                $card = $earnCardMeta[$slug] ?? ['title' => $slug, 'desc' => 'Complete the activity, then claim.', 'accent' => 'violet'];
                $accent = $card['accent'];
            @endphp
            <article class="earn-hub-card earn-hub-card--accent-{{ $accent }}" data-earn-slug="{{ $slug }}">
                <span class="earn-hub-card__strip" aria-hidden="true"></span>
                <div class="earn-hub-card__body">
                    <div class="earn-hub-card__top">
                        <span class="earn-hub-card__reward">{{ $reward }} KOTO</span>
                        <div class="earn-hub-card__icon" aria-hidden="true">
                            @switch($accent)
                                @case('violet')
                                    <svg viewBox="0 0 32 32" width="28" height="28"><g fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><path d="M4 26V20c0-4 4-6 12-6s12 2 12 6v6"/><path d="M10 26v-3M22 26v-3"/><path d="M16 14v12"/><path d="M8 17c2-4 6-5 8-5s6 1 8 5"/><path d="M2 14h28"/></g></svg>
                                    @break
                                @case('teal')
                                    @if (str_contains($slug, 'kanji'))
                                        <svg viewBox="0 0 32 32" width="28" height="28"><text x="4" y="24" font-size="20" fill="currentColor" font-family="serif" font-weight="700">漢</text></svg>
                                    @elseif (str_contains($slug, 'coffee'))
                                        <svg viewBox="0 0 32 32" width="28" height="28"><path fill="currentColor" d="M8 10h12v2H8zm2 4h10c0 4-2 7-5 8v4h4v2H9v-6c-3-1-5-4-5-8h6zm12 0h2c2 0 3 1 3 3s-1 3-3 3h-2v-6z"/></svg>
                                    @else
                                        <svg viewBox="0 0 32 32" width="28" height="28"><path fill="currentColor" d="M16 4c-2 0-3 2-4 5l-1 3H9l-2 4h18l-2-4h-2l-1-3c-1-3-2-5-4-5zm-6 18c0 4 3 7 6 7s6-3 6-7v-2H10v2z"/><ellipse cx="11" cy="11" rx="2" ry="3" fill="currentColor"/><ellipse cx="21" cy="11" rx="2" ry="3" fill="currentColor"/></svg>
                                    @endif
                                    @break
                                @case('amber')
                                    @if ($slug === 'map_explore')
                                        <svg viewBox="0 0 32 32" width="28" height="28"><path fill="currentColor" d="M16 4C11 4 7 8 7 13c0 6 9 15 9 15s9-9 9-15c0-5-4-9-9-9zm0 12a4 4 0 110-8 4 4 0 010 8z"/></svg>
                                    @else
                                        <svg viewBox="0 0 32 32" width="28" height="28"><path fill="none" stroke="currentColor" stroke-width="1.5" d="M8 24l4-8 4 4 4-12 4 16"/><circle cx="8" cy="24" r="2" fill="currentColor"/><circle cx="16" cy="20" r="2" fill="currentColor"/><circle cx="20" cy="16" r="2" fill="currentColor"/><circle cx="24" cy="28" r="2" fill="currentColor"/></svg>
                                    @endif
                                    @break
                                @case('coral')
                                    <svg viewBox="0 0 32 32" width="28" height="28"><path fill="currentColor" d="M10 22c-2-2-3-5-3-8 0-6 4-10 9-10s9 4 9 10c0 3-1 6-3 8l2 6-7-3-2 2-2-2-7 3 2-6zm6-14a2 2 0 100 4 2 2 0 000-4zm6 0a2 2 0 100 4 2 2 0 000-4z"/></svg>
                                    @break
                                @default
                                    <svg viewBox="0 0 32 32" width="28" height="28"><circle cx="16" cy="16" r="10" fill="none" stroke="currentColor" stroke-width="1.5"/></svg>
                            @endswitch
                        </div>
                    </div>
                    <h3 class="earn-hub-card__name">{{ $card['title'] }}</h3>
                    <p class="earn-hub-card__desc muted">{{ $card['desc'] }}</p>
                    <p class="earn-hub-card__last muted" data-role="last-used"></p>
                    <div class="earn-hub-card__status" data-role="earn-cooldown"></div>
                    @if ($hubPath)
                        <a href="{{ $hubPath }}" class="earn-hub-card__btn">Open</a>
                    @else
                        <span class="earn-hub-card__btn earn-hub-card__btn--disabled">Claim via API</span>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
@endsection

@push('head')
<style>
    .earn-hub-hero {
        text-align: center;
        padding: 0.5rem 0 1.5rem;
        margin-bottom: 0.25rem;
    }
    .earn-hub-hero__torii {
        display: flex;
        justify-content: center;
        margin-bottom: 0.75rem;
    }
    .earn-hub-hero__torii svg { display: block; }
    .earn-hub-hero__title {
        font-size: clamp(1.75rem, 4vw, 2.25rem);
        font-weight: 700;
        margin: 0 0 0.75rem;
        letter-spacing: -0.02em;
    }
    .earn-hub-hero__subtitle {
        max-width: 42rem;
        margin: 0 auto;
        line-height: 1.55;
    }
    .earn-stats-bar {
        margin-bottom: 1.75rem;
    }
    .earn-stats-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 0.65rem;
        align-items: stretch;
    }
    .earn-stats-pill {
        flex: 1 1 140px;
        min-width: 0;
        padding: 0.65rem 1rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #111318;
        font-size: 12px;
        color: var(--muted);
    }
    .earn-stats-pill strong {
        display: block;
        font-size: 1.1rem;
        font-weight: 700;
        color: #f0c040;
        margin-top: 0.2rem;
        font-variant-numeric: tabular-nums;
    }
    .earn-stats-pill--wide { flex: 1 1 100%; }
    .earn-hub-section-title {
        font-size: 1rem;
        font-weight: 600;
        color: #d1d5db;
        margin: 2rem 0 0.75rem;
    }
    .earn-wallet-card .earn-wallet-input {
        border-left: 4px solid #7c6af7 !important;
        padding-left: 0.85rem !important;
    }
    .earn-hub-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 1rem;
    }
    @media (min-width: 768px) {
        .earn-hub-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .earn-hub-card {
        position: relative;
        display: flex;
        background: #111318;
        border: 1px solid #1e2030;
        border-radius: 10px;
        overflow: hidden;
        min-height: 100%;
    }
    .earn-hub-card__strip {
        width: 4px;
        flex-shrink: 0;
        background: #7c6af7;
    }
    .earn-hub-card--accent-violet .earn-hub-card__strip { background: #7c6af7; }
    .earn-hub-card--accent-teal .earn-hub-card__strip { background: #1D9E75; }
    .earn-hub-card--accent-amber .earn-hub-card__strip { background: #f0c040; }
    .earn-hub-card--accent-coral .earn-hub-card__strip { background: #D85A30; }
    .earn-hub-card__body {
        flex: 1;
        padding: 1rem 1rem 0.85rem;
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .earn-hub-card__top {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.5rem;
        margin-bottom: 0.35rem;
    }
    .earn-hub-card__reward {
        font-size: 12px;
        font-weight: 700;
        color: #f0c040;
        background: rgba(240, 192, 64, 0.1);
        border: 1px solid rgba(240, 192, 64, 0.25);
        padding: 0.2rem 0.5rem;
        border-radius: 6px;
        white-space: nowrap;
    }
    .earn-hub-card__icon {
        color: rgba(229, 231, 235, 0.85);
        opacity: 0.9;
    }
    .earn-hub-card__name {
        font-size: 15px;
        font-weight: 600;
        color: #fff;
        margin: 0 0 0.35rem;
        font-family: inherit;
        line-height: 1.3;
    }
    .earn-hub-card__desc {
        font-size: 12px;
        line-height: 1.45;
        margin: 0 0 0.5rem;
    }
    .earn-hub-card__last {
        font-size: 11px;
        margin: 0 0 0.45rem;
        line-height: 1.4;
    }
    .earn-hub-card__status {
        font-size: 12px;
        min-height: 1.35rem;
        margin: 0 0 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        flex-wrap: wrap;
    }
    .earn-hub-card__status .earn-cd-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: #4ade80;
        flex-shrink: 0;
    }
    .earn-hub-card__status .earn-cd-dot--wait {
        background: #fb923c;
    }
    .earn-hub-card__status .earn-cd-clock {
        width: 14px;
        height: 14px;
        flex-shrink: 0;
        opacity: 0.9;
    }
    .earn-hub-card__status--muted { color: var(--muted); }
    .earn-hub-card__status--ok { color: #86efac; }
    .earn-hub-card__status--wait { color: #fdba74; }
    .earn-hub-card__btn {
        display: block;
        width: 100%;
        text-align: center;
        margin-top: auto;
        padding: 0.55rem 0.75rem;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        background: #1e2330;
        color: #e5e7eb;
        border: 1px solid var(--border);
        text-decoration: none;
        transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
    }
    .earn-hub-card__btn:hover {
        background: #7c6af7;
        border-color: #7c6af7;
        color: #fff;
        text-decoration: none;
    }
    .earn-hub-card__btn--disabled {
        cursor: default;
        opacity: 0.65;
    }
    .earn-hub-card__btn--disabled:hover {
        background: #1e2330;
        border-color: var(--border);
        color: #e5e7eb;
    }
</style>
<script>
(function () {
    var API = @json($apiBase);
    var walletInput = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';

    function el(id) { return document.getElementById(id); }

    function readLastUsedMap() {
        try {
            var raw = localStorage.getItem(LAST_USED_KEY);
            var o = raw ? JSON.parse(raw) : {};
            return o && typeof o === 'object' ? o : {};
        } catch (e) {
            return {};
        }
    }

    function getLastUsed(wallet, slug) {
        if (!wallet || !slug) return null;
        var m = readLastUsedMap();
        return m[wallet] && m[wallet][slug] ? m[wallet][slug] : null;
    }

    function formatWhen(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString();
        } catch (e) {
            return String(iso);
        }
    }

    function clockSvg() {
        return '<svg class="earn-cd-clock" viewBox="0 0 16 16" aria-hidden="true"><circle cx="8" cy="8" r="7" fill="none" stroke="currentColor" stroke-width="1.2"/><path d="M8 4.5V8l3 1.5" stroke="currentColor" stroke-width="1.2" fill="none" stroke-linecap="round"/></svg>';
    }

    function setCooldownVisual(card, mode, text) {
        var row = card.querySelector('[data-role="earn-cooldown"]');
        if (!row) return;
        row.className = 'earn-hub-card__status';
        if (mode === 'available') {
            row.classList.add('earn-hub-card__status--ok');
            row.innerHTML = '<span class="earn-cd-dot" aria-hidden="true"></span><span>Available</span>';
            return;
        }
        if (mode === 'cooldown') {
            row.classList.add('earn-hub-card__status--wait');
            row.innerHTML = clockSvg() + '<span>' + (text || 'On cooldown') + '</span>';
            return;
        }
        if (mode === 'neutral') {
            row.classList.add('earn-hub-card__status--muted');
            row.textContent = text || '';
            return;
        }
        row.classList.add('earn-hub-card__status--muted');
        row.textContent = text || '';
    }

    function refreshLastOpenedLabels() {
        var w = walletInput && walletInput.value ? walletInput.value.trim() : '';
        var rows = document.querySelectorAll('[data-earn-slug]');
        for (var i = 0; i < rows.length; i++) {
            var row = rows[i];
            var slug = row.getAttribute('data-earn-slug');
            var lastEl = row.querySelector('[data-role="last-used"]');
            if (!lastEl) continue;
            if (!w || w.length < 20) {
                lastEl.textContent = 'Last opened: enter wallet above to track';
            } else {
                var lu = getLastUsed(w, slug);
                lastEl.textContent = lu ? ('Last opened: ' + formatWhen(lu)) : 'Last opened: — (open activity to record)';
            }
        }
    }

    function applyStatusToRows(data) {
        var acts = data && data.activities ? data.activities : [];
        for (var j = 0; j < acts.length; j++) {
            var a = acts[j];
            var card = document.querySelector('.earn-hub-card[data-earn-slug="' + a.slug + '"]');
            if (!card) continue;
            if (a.available) {
                setCooldownVisual(card, 'available');
            } else {
                var next = a.next_claim_at ? ('Next claim ' + formatWhen(a.next_claim_at)) : 'On cooldown';
                setCooldownVisual(card, 'cooldown', next);
            }
        }
    }

    function loadStats() {
        var box = el('earn-stats');
        if (!box) return;
        fetch(API + '/faucet/balance', { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    box.innerHTML = '<p class="muted" style="margin:0;">Faucet: ' + String(data.error) + '</p>';
                    return;
                }
                var bal = data.balance != null ? data.balance : '—';
                var paid = data.daily_paid != null ? data.daily_paid : '—';
                var claims = data.total_claims != null ? data.total_claims : '—';
                var sync = data.last_sync ? new Date(data.last_sync).toLocaleString() : '—';
                box.innerHTML =
                    '<div class="earn-stats-pills">' +
                    '<div class="earn-stats-pill">Book balance<strong>' + bal + ' KOTO</strong></div>' +
                    '<div class="earn-stats-pill">Paid (24h)<strong>' + paid + ' KOTO</strong></div>' +
                    '<div class="earn-stats-pill">Total claims<strong>' + claims + '</strong></div>' +
                    '</div>' +
                    '<p class="muted" style="margin:0.65rem 0 0;font-size:11px;">Last sync: ' + sync + '</p>';
            })
            .catch(function () {
                box.innerHTML = '<p class="muted" style="margin:0;">Could not load <code>/faucet/balance</code>.</p>';
            });
    }

    function resetAllCooldownRows(message) {
        var cards = document.querySelectorAll('.earn-hub-card[data-earn-slug]');
        for (var i = 0; i < cards.length; i++) {
            setCooldownVisual(cards[i], 'neutral', message);
        }
    }

    function loadStatus() {
        var w = walletInput && walletInput.value ? walletInput.value.trim() : '';
        refreshLastOpenedLabels();
        if (!w || w.length < 8) {
            resetAllCooldownRows('Enter wallet to check claim status');
            return;
        }
        fetch(API + '/faucet/status?wallet=' + encodeURIComponent(w), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    resetAllCooldownRows('Claim status: —');
                    return;
                }
                applyStatusToRows(data);
            })
            .catch(function () {
                resetAllCooldownRows('Claim status: could not load');
            });
    }

    document.addEventListener('DOMContentLoaded', function () {
        walletInput = el('earn-wallet');
        var k = 'isekai_earn_wallet';
        try {
            if (walletInput && localStorage.getItem(k)) walletInput.value = localStorage.getItem(k);
        } catch (e) {}
        if (walletInput) {
            walletInput.addEventListener('change', function () {
                try { localStorage.setItem(k, walletInput.value.trim()); } catch (e) {}
                loadStatus();
            });
            walletInput.addEventListener('input', function () {
                refreshLastOpenedLabels();
            });
        }
        loadStats();
        loadStatus();
    });
})();
</script>
@endpush
