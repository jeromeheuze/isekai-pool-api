@extends('earn.layout')

@section('title', 'Earn KOTO')

@php
    $hubPaths = [
        'shrine_visit' => '/earn/shrine',
        'kanji_quiz' => '/earn/kanji',
        'retro_trivia' => '/earn/retro',
    ];
@endphp

@section('content')
    <h1>Earn KOTO</h1>
    <p class="muted" style="max-width: 42rem; margin-bottom: 1.5rem;">
        Private CPU coin from Japan — learn, play, and claim rewards funded by the pool.
        Same faucet API as <a href="/faucet.html">/faucet.html</a>; this hub will grow into games and quizzes per the roadmap.
    </p>

    <div class="card" id="earn-stats">
        <p class="muted" style="margin:0;">Loading faucet stats…</p>
    </div>

    <h2>Wallet (saved in browser)</h2>
    <div class="card">
        <label for="earn-wallet" class="muted" style="display:block;margin-bottom:0.35rem;">KOTO address</label>
        <input id="earn-wallet" type="text" autocomplete="off" placeholder="k1…"
            class="muted"
            style="width:100%;max-width:100%;padding:0.5rem 0.75rem;border-radius:6px;border:1px solid var(--border);background:#0d0f14;color:var(--text);font-family:inherit;font-size:13px;">
        <p class="muted" style="margin:0.75rem 0 0;font-size:12px;">Used to show per-activity availability from the API. Not sent to our server on this page except when you claim (Turnstile + POST).</p>
    </div>

    <h2>Activities</h2>
    <div style="display:grid;gap:0.75rem;">
        @foreach ($activities as $slug => $meta)
            @php
                $reward = $meta['reward'];
                $hubPath = $hubPaths[$slug] ?? null;
            @endphp
            <div class="card" style="display:flex;flex-wrap:wrap;align-items:center;justify-content:space-between;gap:0.75rem;">
                <div>
                    <strong style="color:#fff;">{{ $slug }}</strong>
                    <span class="muted"> — {{ $reward }} KOTO</span>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;">
                    @if ($hubPath)
                        <a href="{{ $hubPath }}" class="btn">Open</a>
                    @else
                        <span class="muted" style="font-size:12px;">Claim via API / main faucet</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
@endsection

@push('head')
<script>
(function () {
    var API = @json($apiBase);
    var walletInput = null;

    function el(id) { return document.getElementById(id); }

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
                    '<p style="margin:0 0 0.5rem;"><strong style="color:#fff;">Book balance</strong> · ' + bal + ' KOTO</p>' +
                    '<p class="muted" style="margin:0;font-size:12px;">Paid (24h): ' + paid + ' · Total claims: ' + claims + ' · Last sync: ' + sync + '</p>';
            })
            .catch(function () {
                box.innerHTML = '<p class="muted" style="margin:0;">Could not load <code>/faucet/balance</code>.</p>';
            });
    }

    function loadStatus() {
        var w = walletInput && walletInput.value ? walletInput.value.trim() : '';
        if (!w || w.length < 8) return;
        fetch(API + '/faucet/status?wallet=' + encodeURIComponent(w), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function () { /* optional: merge into UI later */ })
            .catch(function () {});
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
        }
        loadStats();
        loadStatus();
    });
})();
</script>
@endpush
