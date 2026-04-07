@extends('earn.layout')

@section('title', 'Daily Shrine Visit')

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="shrine_visit"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>

    <p class="muted shrine-back"><a href="/earn">← Earn hub</a></p>

    <section class="shrine-hero" aria-labelledby="shrine-hero-title">
        <div class="shrine-hero__visual">
            <div class="shrine-hero__pulse" aria-hidden="true"></div>
            <svg class="shrine-hero__torii" width="222" height="200" viewBox="0 0 80 72" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <rect x="6" y="10" width="68" height="5" rx="2" fill="#7c6af7"/>
                <rect x="12" y="18" width="56" height="4" rx="2" fill="#7c6af7"/>
                <rect x="14" y="22" width="4" height="46" rx="2" fill="#7c6af7"/>
                <rect x="62" y="22" width="4" height="46" rx="2" fill="#7c6af7"/>
                <rect x="20" y="22" width="2" height="30" rx="1" fill="#7c6af7" opacity="0.4"/>
                <rect x="58" y="22" width="2" height="30" rx="1" fill="#7c6af7" opacity="0.4"/>
                <rect x="36" y="0" width="8" height="14" rx="2" fill="#7c6af7"/>
            </svg>
        </div>
        <h1 id="shrine-hero-title" class="shrine-hero__title">Daily Shrine Visit</h1>
        <p class="shrine-hero__subtitle">Pause. Breathe. Receive.</p>
    </section>

    <div class="shrine-wallet-wrap">
        <label for="claim-wallet" class="shrine-wallet-label muted">Wallet</label>
        <input type="text" id="claim-wallet" class="shrine-wallet-input" autocomplete="off" placeholder="k1…" title="">
    </div>

    <section class="shrine-ritual card" aria-labelledby="shrine-ritual-heading">
        <h2 id="shrine-ritual-heading" class="shrine-section-title">The ritual</h2>
        <div id="shrine-ritual-root" class="shrine-ritual-root">
            <div id="shrine-ritual-active" class="shrine-ritual-active">
                <div class="shrine-ring-stack">
                    <div class="shrine-pulse-ring" id="shrine-pulse-ring" aria-hidden="true"></div>
                    <svg class="shrine-ring-svg" viewBox="0 0 120 120" aria-hidden="true">
                        <circle cx="60" cy="60" r="52" fill="none" stroke="#1e2330" stroke-width="5"/>
                        <circle id="shrine-progress-arc" cx="60" cy="60" r="52" fill="none" stroke="#f0c040" stroke-width="5"
                            stroke-linecap="round" transform="rotate(-90 60 60)" stroke-dasharray="326.726" stroke-dashoffset="326.726"/>
                    </svg>
                    <div class="shrine-ring-center">
                        <div id="shrine-torii-ritual" class="shrine-torii-ritual shrine-torii--dim">
                            <svg width="100" height="90" viewBox="0 0 80 72" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect x="6" y="10" width="68" height="5" rx="2" fill="#7c6af7"/>
                                <rect x="12" y="18" width="56" height="4" rx="2" fill="#7c6af7"/>
                                <rect x="14" y="22" width="4" height="46" rx="2" fill="#7c6af7"/>
                                <rect x="62" y="22" width="4" height="46" rx="2" fill="#7c6af7"/>
                                <rect x="20" y="22" width="2" height="30" rx="1" fill="#7c6af7" opacity="0.4"/>
                                <rect x="58" y="22" width="2" height="30" rx="1" fill="#7c6af7" opacity="0.4"/>
                                <rect x="36" y="0" width="8" height="14" rx="2" fill="#7c6af7"/>
                            </svg>
                        </div>
                        <div id="shrine-count" class="shrine-count" hidden>8</div>
                    </div>
                </div>
                <p id="shrine-quote" class="shrine-quote" hidden>「静寂の中に力がある」</p>
                <button type="button" id="start-shrine" class="shrine-btn-begin">Begin ritual</button>
                <p id="shrine-session-msg" class="shrine-session-msg muted" style="margin:0.75rem 0 0;font-size:12px;"></p>
            </div>
            <div id="shrine-complete" class="shrine-complete" hidden>
                <div class="shrine-complete__check" aria-hidden="true">
                    <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="#7c6af7" stroke-width="2">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                </div>
                <p class="shrine-complete__title">Ritual complete</p>
                <p class="shrine-complete__hint muted">You may claim when ready.</p>
            </div>
        </div>
        <p id="activity-state" class="sr-only" aria-live="polite"></p>
    </section>

    <div id="earn-claim-unavailable" class="shrine-cooldown-card" style="display:none;">
        <div class="shrine-cooldown-card__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </div>
        <div>
            <p class="shrine-cooldown-card__title">Visit on cooldown</p>
            <p id="earn-claim-unavailable-msg" class="shrine-cooldown-card__msg muted"></p>
        </div>
    </div>

    <div id="earn-claim-section" class="shrine-claim card">
        <h2 class="shrine-section-title">Claim</h2>
        <p class="muted shrine-claim-slug">Activity <code>shrine_visit</code></p>
        @if (!empty($turnstileSiteKey))
            <div class="cf-turnstile"
                 data-sitekey="{{ $turnstileSiteKey }}"
                 data-callback="onEarnTurnstile"
                 data-expired-callback="onEarnTurnstileExpired"
                 data-error-callback="onEarnTurnstileExpired"></div>
            <p class="muted" style="margin:0.6rem 0 0;font-size:12px;">Verify before claiming.</p>
        @else
            <p class="muted" style="margin:0 0 0.6rem;font-size:12px;">Turnstile not configured.</p>
        @endif
        <button type="button" id="claim-btn" class="btn shrine-claim-btn" disabled>Claim {{ $rewardKoto }} KOTO</button>
        <p id="claim-result" class="shrine-claim-result muted"></p>
        <p id="claim-tx-wrap" class="shrine-tx-wrap muted" style="display:none;font-size:12px;"></p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .shrine-back { margin-bottom: 1rem; }
    .shrine-hero { text-align: center; margin-bottom: 1.75rem; }
    .shrine-hero__visual { position: relative; display: flex; justify-content: center; align-items: center; min-height: 220px; margin-bottom: 0.75rem; }
    .shrine-hero__pulse {
        position: absolute;
        width: 260px;
        height: 260px;
        border-radius: 50%;
        border: 1px solid rgba(124, 106, 247, 0.35);
        animation: shrine-pulse 3s ease-in-out infinite;
        pointer-events: none;
    }
    @keyframes shrine-pulse {
        0%, 100% { opacity: 0.15; transform: scale(1); }
        50% { opacity: 0.4; transform: scale(1.02); }
    }
    .shrine-hero__torii { position: relative; z-index: 1; display: block; margin: 0 auto; }
    .shrine-hero__title { font-size: 1.5rem; font-weight: 700; margin: 0 0 0.35rem; }
    .shrine-hero__subtitle { font-size: 13px; font-style: italic; color: var(--muted); margin: 0; }

    .shrine-wallet-wrap { margin-bottom: 1.25rem; }
    .shrine-wallet-label { display: block; margin-bottom: 0.35rem; font-size: 12px; }
    .shrine-wallet-input {
        width: 100%;
        max-width: 100%;
        padding: 0.5rem 1rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #111318;
        color: var(--text);
        font-family: inherit;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .shrine-section-title { font-size: 1rem; font-weight: 600; color: #d1d5db; margin: 0 0 1rem; }
    .shrine-ritual { margin-bottom: 1rem; }
    .shrine-ritual-root { min-height: 8rem; }
    .shrine-ring-stack { position: relative; width: 200px; height: 200px; margin: 0 auto 1rem; }
    .shrine-pulse-ring {
        position: absolute;
        inset: 0;
        margin: auto;
        width: 200px;
        height: 200px;
        border-radius: 50%;
        border: 1px solid rgba(124, 106, 247, 0.25);
        animation: shrine-pulse 3s ease-in-out infinite;
        opacity: 0.5;
        pointer-events: none;
    }
    .shrine-ring-svg { position: absolute; left: 0; top: 0; width: 200px; height: 200px; }
    .shrine-ring-center {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        width: 120px;
        height: 120px;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .shrine-torii-ritual { transition: opacity 0.6s ease, filter 0.6s ease; }
    .shrine-torii--dim { opacity: 0.4; filter: grayscale(0.2); }
    .shrine-torii--bright { opacity: 1; filter: none; }
    .shrine-torii--glow { opacity: 1; filter: drop-shadow(0 0 14px rgba(124, 106, 247, 0.85)); }
    .shrine-count {
        position: absolute;
        font-size: 2.25rem;
        font-weight: 700;
        color: #f0c040;
        text-shadow: 0 0 20px rgba(240, 192, 64, 0.25);
        pointer-events: none;
    }
    .shrine-quote { text-align: center; font-size: 12px; color: var(--muted); margin: 0 0 1rem; letter-spacing: 0.02em; }
    .shrine-btn-begin {
        display: block;
        width: 100%;
        max-width: 240px;
        margin: 0 auto;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        border: 1px solid #7c6af7;
        background: rgba(124, 106, 247, 0.15);
        color: #c4b5fd;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .shrine-btn-begin:hover { background: rgba(124, 106, 247, 0.28); color: #fff; }
    .shrine-btn-begin:disabled { opacity: 0.5; cursor: not-allowed; }
    .shrine-complete { text-align: center; padding: 1rem 0; }
    .shrine-complete__check { margin-bottom: 0.5rem; }
    .shrine-complete__title { font-size: 1.1rem; font-weight: 600; color: #fff; margin: 0 0 0.25rem; }
    .shrine-complete__hint { margin: 0; font-size: 12px; }

    .shrine-cooldown-card {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 1rem 1.15rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        border: 1px solid rgba(251, 191, 36, 0.35);
        background: rgba(251, 191, 36, 0.08);
        color: #fde68a;
    }
    .shrine-cooldown-card__icon { flex-shrink: 0; opacity: 0.9; margin-top: 0.1rem; }
    .shrine-cooldown-card__title { margin: 0 0 0.25rem; font-size: 14px; font-weight: 600; color: #fcd34d; }
    .shrine-cooldown-card__msg { margin: 0; font-size: 13px; line-height: 1.45; }

    .shrine-claim { margin-top: 0.5rem; }
    .shrine-claim-slug { font-size: 12px; margin: 0 0 0.75rem; }
    .shrine-claim-btn {
        margin-top: 0.85rem;
        background: linear-gradient(135deg, #f0c040 0%, #d4a017 100%);
        color: #141720;
        font-weight: 700;
        border: none;
    }
    .shrine-claim-btn:hover:not(:disabled) { filter: brightness(1.06); color: #141720; }
    .shrine-claim-btn:disabled { opacity: 0.5; }
    .shrine-claim-result { margin: 0.85rem 0 0; min-height: 1.25rem; }
    .shrine-tx-wrap a { color: var(--accent); word-break: break-all; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var EXPLORER_TX = @json($explorerTxBase);
    var RING_LEN = 2 * Math.PI * 52;
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = 'shrine_visit';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;

    function el(id) { return document.getElementById(id); }

    function readLastUsedMap() {
        try {
            var raw = localStorage.getItem(LAST_USED_KEY);
            var o = raw ? JSON.parse(raw) : {};
            return o && typeof o === 'object' ? o : {};
        } catch (e) { return {}; }
    }

    function recordLastVisit() {
        var w = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        if (!w || w.length < 20) return;
        try {
            var m = readLastUsedMap();
            if (!m[w]) m[w] = {};
            m[w][slug] = new Date().toISOString();
            localStorage.setItem(LAST_USED_KEY, JSON.stringify(m));
        } catch (e) {}
    }

    function formatWhen(iso) {
        if (!iso) return '—';
        try { return new Date(iso).toLocaleString(); } catch (e) { return String(iso); }
    }

    function shortTx(txid) {
        if (!txid || txid.length < 20) return txid;
        return txid.slice(0, 10) + '…' + txid.slice(-8);
    }

    function syncWalletTitle() {
        var inp = el('claim-wallet');
        if (inp && inp.value) inp.setAttribute('title', inp.value);
    }

    function fetchClaimAvailability() {
        var section = el('earn-claim-section');
        var blocked = el('earn-claim-unavailable');
        var blockedMsg = el('earn-claim-unavailable-msg');
        var w = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        if (!section) return;
        if (!w || w.length < 20) {
            claimAllowedFromApi = true;
            section.style.display = '';
            if (blocked) blocked.style.display = 'none';
            updateClaimButton();
            return;
        }
        fetch(API + '/faucet/status?wallet=' + encodeURIComponent(w), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error) {
                    claimAllowedFromApi = true;
                    section.style.display = '';
                    if (blocked) blocked.style.display = 'none';
                    updateClaimButton();
                    return;
                }
                var acts = data.activities || [];
                var mine = null;
                for (var i = 0; i < acts.length; i++) {
                    if (acts[i].slug === slug) { mine = acts[i]; break; }
                }
                if (mine && mine.available === false) {
                    claimAllowedFromApi = false;
                    section.style.display = 'none';
                    if (blocked) {
                        blocked.style.display = 'flex';
                        if (blockedMsg) {
                            blockedMsg.textContent = mine.next_claim_at
                                ? ('Next visit: ' + formatWhen(mine.next_claim_at))
                                : 'This activity is on cooldown for your wallet.';
                        }
                    }
                } else {
                    claimAllowedFromApi = true;
                    section.style.display = '';
                    if (blocked) blocked.style.display = 'none';
                }
                updateClaimButton();
            })
            .catch(function () {
                claimAllowedFromApi = true;
                section.style.display = '';
                if (blocked) blocked.style.display = 'none';
                updateClaimButton();
            });
    }

    function setActivityDone(done, message, proof) {
        activityDone = !!done;
        if (done && proof) proofPayload = proof;
        if (!done) proofPayload = null;
        var state = el('activity-state');
        if (state) state.textContent = message || '';
        var active = el('shrine-ritual-active');
        var complete = el('shrine-complete');
        var torii = el('shrine-torii-ritual');
        var beginBtn = el('start-shrine');
        if (done) {
            if (active) active.setAttribute('hidden', 'hidden');
            if (complete) complete.removeAttribute('hidden');
            if (torii) {
                torii.classList.remove('shrine-torii--dim', 'shrine-torii--bright');
                torii.classList.add('shrine-torii--glow');
            }
            if (beginBtn) beginBtn.setAttribute('hidden', 'hidden');
        }
        updateClaimButton();
    }

    function updateClaimButton() {
        var btn = el('claim-btn');
        if (!btn) return;
        var wallet = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        var captchaOk = hasTurnstile ? !!turnstileToken : true;
        btn.disabled = !(claimAllowedFromApi && activityDone && wallet.length >= 20 && captchaOk);
    }

    function setProgress(frac) {
        var arc = el('shrine-progress-arc');
        if (!arc) return;
        var off = RING_LEN * (1 - Math.min(1, Math.max(0, frac)));
        arc.setAttribute('stroke-dashoffset', String(off));
    }

    function initShrineRitual() {
        var startBtn = el('start-shrine');
        var timerSlot = el('shrine-count');
        var quote = el('shrine-quote');
        var sessionMsg = el('shrine-session-msg');
        var torii = el('shrine-torii-ritual');
        var pulseRitual = el('shrine-pulse-ring');
        if (!startBtn) return;

        startBtn.addEventListener('click', function () {
            startBtn.disabled = true;
            if (sessionMsg) sessionMsg.textContent = '';
            fetch(API + '/faucet/activity-session', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ activity_slug: 'shrine_visit' })
            }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (res) {
                    if (!res.ok || !res.data.session_id) {
                        if (sessionMsg) sessionMsg.textContent = 'Could not begin session. Try again.';
                        startBtn.disabled = false;
                        return;
                    }
                    var sessionId = res.data.session_id;
                    if (torii) {
                        torii.classList.remove('shrine-torii--dim');
                        torii.classList.add('shrine-torii--bright');
                    }
                    if (pulseRitual) pulseRitual.style.display = 'block';
                    if (quote) quote.removeAttribute('hidden');
                    if (timerSlot) {
                        timerSlot.removeAttribute('hidden');
                        timerSlot.textContent = '8';
                    }
                    setProgress(0);
                    var left = 8;
                    var t = setInterval(function () {
                        left -= 1;
                        setProgress((8 - left) / 8);
                        if (left <= 0) {
                            clearInterval(t);
                            if (timerSlot) timerSlot.setAttribute('hidden', 'hidden');
                            if (quote) quote.setAttribute('hidden', 'hidden');
                            setProgress(1);
                            setActivityDone(true, 'Ritual complete.', { session_id: sessionId });
                            return;
                        }
                        if (timerSlot) timerSlot.textContent = String(left);
                    }, 1000);
                }).catch(function () {
                    if (sessionMsg) sessionMsg.textContent = 'Network error.';
                    startBtn.disabled = false;
                });
        });
    }

    function claim() {
        var result = el('claim-result');
        var txWrap = el('claim-tx-wrap');
        if (txWrap) { txWrap.style.display = 'none'; txWrap.innerHTML = ''; }
        var wallet = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        if (!wallet) {
            if (result) result.textContent = 'Enter wallet first.';
            return;
        }
        if (!activityDone || !proofPayload) {
            if (result) result.textContent = 'Complete the ritual first.';
            return;
        }
        if (hasTurnstile && !turnstileToken) {
            if (result) result.textContent = 'Complete verification first.';
            return;
        }
        var idem = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + '-' + String(Math.random()).slice(2);
        if (result) result.textContent = 'Verifying…';

        fetch(API + '/faucet/activity-complete', {
            method: 'POST',
            headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
            body: JSON.stringify({
                wallet_address: wallet,
                activity_slug: slug,
                turnstile_token: turnstileToken,
                proof: proofPayload
            })
        }).then(function (r) {
            return r.json().then(function (data) { return { status: r.status, data: data }; });
        }).then(function (res) {
            var data = res.data || {};
            if (res.status < 200 || res.status >= 400 || data.error) {
                if (result) result.textContent = 'Verification failed: ' + (data.error || res.status);
                return;
            }
            if (!data.completion_token) {
                if (result) result.textContent = 'No completion token.';
                return;
            }
            if (result) result.textContent = 'Submitting claim…';
            return fetch(API + '/faucet/claim', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'Idempotency-Key': idem
                },
                body: JSON.stringify({
                    wallet_address: wallet,
                    activity_slug: slug,
                    turnstile_token: '',
                    completion_token: data.completion_token,
                    source_site: 'isekai-pool'
                })
            });
        }).then(function (r) {
            if (!r || !r.json) return null;
            return r.json().then(function (data) { return { status: r.status, data: data }; });
        }).then(function (res) {
            if (!res) return;
            var data = res.data || {};
            if (data.error) {
                if (result) result.textContent = 'Claim failed: ' + data.error;
                return;
            }
            if (data.pending) {
                if (result) result.textContent = 'Claim accepted (pending). Amount: ' + (data.amount || '—') + ' KOTO.';
                fetchClaimAvailability();
                return;
            }
            if (data.success) {
                var amt = data.amount || '—';
                if (result) result.textContent = 'Paid: ' + amt + ' KOTO.';
                if (data.txid && txWrap) {
                    txWrap.style.display = 'block';
                    var href = EXPLORER_TX + encodeURIComponent(data.txid);
                    txWrap.innerHTML = 'Txid: <a href="' + href + '" target="_blank" rel="noopener" class="mono">' + shortTx(data.txid) + '</a>';
                }
                fetchClaimAvailability();
                return;
            }
            if (result) result.textContent = 'Unexpected response.';
        }).catch(function () {
            if (result) result.textContent = 'Network error.';
        });
    }

    window.onEarnTurnstile = function (token) {
        turnstileToken = token || '';
        updateClaimButton();
    };
    window.onEarnTurnstileExpired = function () {
        turnstileToken = '';
        updateClaimButton();
    };

    document.addEventListener('DOMContentLoaded', function () {
        var wallet = el('claim-wallet');
        if (wallet) {
            try {
                var saved = localStorage.getItem(walletKey);
                if (saved) wallet.value = saved;
            } catch (e) {}
            syncWalletTitle();
            wallet.addEventListener('input', function () {
                syncWalletTitle();
                updateClaimButton();
                fetchClaimAvailability();
            });
            wallet.addEventListener('change', function () {
                try { localStorage.setItem(walletKey, wallet.value.trim()); } catch (e) {}
                syncWalletTitle();
                recordLastVisit();
                updateClaimButton();
                fetchClaimAvailability();
            });
        }
        var claimBtn = el('claim-btn');
        if (claimBtn) claimBtn.addEventListener('click', claim);
        initShrineRitual();
        recordLastVisit();
        fetchClaimAvailability();
        updateClaimButton();
    });
})();
</script>
@endpush
