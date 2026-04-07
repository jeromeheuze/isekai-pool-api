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
            <div class="card earn-activity-row" data-earn-slug="{{ $slug }}" style="display:flex;flex-wrap:wrap;align-items:flex-start;justify-content:space-between;gap:0.75rem;">
                <div style="min-width:0;">
                    <strong style="color:#fff;">{{ $slug }}</strong>
                    <span class="muted"> — {{ $reward }} KOTO</span>
                    <p class="muted" data-role="last-used" style="margin:0.35rem 0 0;font-size:11px;line-height:1.4;"></p>
                    <p class="muted" data-role="claim-status" style="margin:0.2rem 0 0;font-size:11px;line-height:1.4;"></p>
                </div>
                <div style="display:flex;flex-wrap:wrap;gap:0.5rem;align-items:center;">
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
                lastEl.textContent = lu ? ('Last opened: ' + formatWhen(lu)) : 'Last opened: — (open activity page to record)');
            }
        }
    }

    function applyStatusToRows(data) {
        var acts = data && data.activities ? data.activities : [];
        for (var j = 0; j < acts.length; j++) {
            var a = acts[j];
            var row = document.querySelector('[data-earn-slug="' + a.slug + '"]');
            if (!row) continue;
            var claimEl = row.querySelector('[data-role="claim-status"]');
            if (!claimEl) continue;
            if (a.available) {
                claimEl.textContent = 'Claim status: eligible';
                claimEl.style.color = 'var(--muted)';
            } else {
                claimEl.textContent = 'Claim status: on cooldown — next ' + formatWhen(a.next_claim_at);
                claimEl.style.color = '#f0a8a8';
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
                    '<p style="margin:0 0 0.5rem;"><strong style="color:#fff;">Book balance</strong> · ' + bal + ' KOTO</p>' +
                    '<p class="muted" style="margin:0;font-size:12px;">Paid (24h): ' + paid + ' · Total claims: ' + claims + ' · Last sync: ' + sync + '</p>';
            })
            .catch(function () {
                box.innerHTML = '<p class="muted" style="margin:0;">Could not load <code>/faucet/balance</code>.</p>';
            });
    }

    function loadStatus() {
        var w = walletInput && walletInput.value ? walletInput.value.trim() : '';
        refreshLastOpenedLabels();
        if (!w || w.length < 8) {
            var rows = document.querySelectorAll('[data-earn-slug] [data-role="claim-status"]');
            for (var i = 0; i < rows.length; i++) {
                rows[i].textContent = 'Claim status: enter wallet to check';
                rows[i].style.color = 'var(--muted)';
            }
            return;
        }
        fetch(API + '/faucet/status?wallet=' + encodeURIComponent(w), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    var rows = document.querySelectorAll('[data-earn-slug] [data-role="claim-status"]');
                    for (var i = 0; i < rows.length; i++) {
                        rows[i].textContent = 'Claim status: —';
                    }
                    return;
                }
                applyStatusToRows(data);
            })
            .catch(function () {
                var rows = document.querySelectorAll('[data-earn-slug] [data-role="claim-status"]');
                for (var i = 0; i < rows.length; i++) {
                    rows[i].textContent = 'Claim status: could not load';
                }
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
