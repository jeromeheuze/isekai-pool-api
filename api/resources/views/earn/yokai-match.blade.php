@extends('earn.layout')

@section('title', 'Yokai Match')

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="yokai_match"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>

    <p class="muted ym-back"><a href="/earn">← Earn hub</a></p>

    <header class="ym-header">
        <div class="ym-header__title-row">
            <span class="ym-header__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/>
                </svg>
            </span>
            <h1 class="ym-header__title">Yokai Match</h1>
            <span id="ym-progress" class="ym-progress-pill muted" aria-live="polite">0 / 4 matched</span>
        </div>
        <p class="ym-header__sub muted">Match each yokai to its description — all 4 pairs to unlock claim</p>
    </header>

    <div class="ym-wallet-wrap">
        <label for="claim-wallet" class="ym-wallet-label muted">Wallet</label>
        <input type="text" id="claim-wallet" class="ym-wallet-input" autocomplete="off" placeholder="k1…" title="">
    </div>

    <section class="ym-challenge card" aria-labelledby="ym-challenge-heading">
        <h2 id="ym-challenge-heading" class="sr-only">Matching board</h2>
        <div id="ym-success-banner" class="ym-success-banner" aria-hidden="true">
            <span class="ym-success-banner__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="m9 11 3 3L22 4"/>
                </svg>
            </span>
            <p class="ym-success-banner__text">All yokai matched! Claim your reward.</p>
        </div>

        <div class="ym-board" id="ym-board">
            <svg class="ym-board__svg" id="ym-line-svg" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"></svg>
            <div class="ym-board__cols">
                <div class="ym-col ym-col--left" id="ym-left"></div>
                <div class="ym-bridge" aria-hidden="true">
                    <div class="ym-bridge__vline"></div>
                </div>
                <div class="ym-col ym-col--right" id="ym-right"></div>
            </div>
        </div>

        <p id="activity-state" class="sr-only" aria-live="polite"></p>
    </section>

    <div id="earn-claim-unavailable" class="ym-cooldown-card" style="display:none;">
        <div class="ym-cooldown-card__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </div>
        <div>
            <p class="ym-cooldown-card__title">Claim on cooldown</p>
            <p id="earn-claim-unavailable-msg" class="ym-cooldown-card__msg muted"></p>
        </div>
    </div>

    <div id="earn-claim-section" class="ym-claim card">
        <h2 class="ym-claim-heading">Claim</h2>
        @if (!empty($turnstileSiteKey))
            <div class="cf-turnstile"
                 data-sitekey="{{ $turnstileSiteKey }}"
                 data-callback="onEarnTurnstile"
                 data-expired-callback="onEarnTurnstileExpired"
                 data-error-callback="onEarnTurnstileExpired"></div>
            <p class="muted ym-claim-hint">Verify before claiming.</p>
        @else
            <p class="muted ym-claim-hint">Turnstile not configured.</p>
        @endif
        <button type="button" id="claim-btn" class="ym-claim-btn" disabled>Claim {{ $rewardKoto }} KOTO</button>
        <p id="claim-result" class="ym-claim-result muted"></p>
        <p id="claim-tx-wrap" class="ym-tx-wrap muted" style="display:none;font-size:12px;"></p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .ym-back { margin-bottom: 1rem; }
    .ym-header { margin-bottom: 1.25rem; }
    .ym-header__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem 1rem;
        margin-bottom: 0.5rem;
    }
    .ym-header__icon { display: flex; flex-shrink: 0; }
    .ym-header__title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        font-family: inherit;
        flex: 1;
        min-width: 0;
    }
    .ym-progress-pill {
        font-size: 12px;
        padding: 0.3rem 0.75rem;
        border-radius: 999px;
        border: 1px solid rgba(29, 158, 117, 0.45);
        background: rgba(29, 158, 117, 0.12);
        color: #5eead4;
    }
    .ym-header__sub { margin: 0; font-size: 13px; max-width: 36rem; line-height: 1.45; }

    .ym-wallet-wrap { margin-bottom: 1.25rem; }
    .ym-wallet-label { display: block; margin-bottom: 0.35rem; font-size: 12px; }
    .ym-wallet-input {
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

    .ym-challenge { margin-bottom: 1rem; position: relative; overflow: hidden; }

    .ym-success-banner {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0 1.15rem;
        margin-bottom: 0;
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        transform: translateY(-6px);
        border-radius: 10px;
        border: 1px solid transparent;
        background: linear-gradient(135deg, rgba(29, 158, 117, 0.22) 0%, rgba(13, 31, 24, 0.95) 100%);
        color: #a7f3d0;
        pointer-events: none;
        transition: opacity 0.45s ease, transform 0.45s ease, max-height 0.5s ease, margin 0.45s ease, padding 0.45s ease, border-color 0.45s ease;
    }
    .ym-success-banner.is-visible {
        max-height: 6rem;
        opacity: 1;
        margin-bottom: 1rem;
        padding: 1rem 1.15rem;
        transform: translateY(0);
        border-color: rgba(29, 158, 117, 0.45);
        pointer-events: auto;
    }
    .ym-success-banner__icon { flex-shrink: 0; color: #1D9E75; }
    .ym-success-banner__text { margin: 0; font-size: 15px; font-weight: 600; color: #ecfdf5; }

    .ym-board {
        position: relative;
        min-height: 12rem;
    }
    .ym-board__svg {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
        z-index: 0;
    }
    .ym-board__cols {
        position: relative;
        z-index: 1;
        display: grid;
        grid-template-columns: minmax(0, 1fr) 28px minmax(0, 1fr);
        gap: 0;
        align-items: start;
    }
    .ym-bridge {
        position: relative;
        min-height: 120px;
        align-self: stretch;
    }
    .ym-bridge__vline {
        position: absolute;
        left: 50%;
        top: 0;
        bottom: 0;
        width: 1px;
        margin-left: -0.5px;
        background: #1e2030;
    }
    .ym-col {
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .ym-card {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        width: 100%;
        margin: 0;
        padding: 12px 16px;
        text-align: left;
        font-family: inherit;
        font-size: 14px;
        line-height: 1.35;
        cursor: pointer;
        border-radius: 8px;
        border: 1px solid #1e2030;
        background: #111318;
        color: #888;
        transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease, opacity 0.15s ease, box-shadow 0.15s ease;
    }
    .ym-card:hover:not(:disabled) {
        border-color: rgba(29, 158, 117, 0.5);
    }
    .ym-card--selected {
        border-color: #1D9E75;
        background: #0d1f18;
        color: #fff;
        box-shadow: inset 3px 0 0 0 #1D9E75;
    }
    .ym-card--matched {
        border-color: #1D9E75;
        background: #0a1a12;
        color: #1D9E75;
        cursor: default;
        opacity: 0.88;
    }
    .ym-card--wrong {
        border-color: #D85A30 !important;
        animation: ym-wrong-flash 0.4s ease;
    }
    @keyframes ym-wrong-flash {
        0%, 100% { border-color: #D85A30; }
        50% { border-color: rgba(216, 90, 48, 0.6); }
    }
    .ym-card__body { flex: 1; min-width: 0; }
    .ym-card__title { display: block; color: inherit; font-weight: 500; }
    .ym-card__sub {
        display: block;
        margin-top: 0.2rem;
        font-size: 11px;
        color: rgba(29, 158, 117, 0.75);
    }
    .ym-card--matched .ym-card__sub { color: rgba(29, 158, 117, 0.9); }
    .ym-card__check { flex-shrink: 0; display: flex; align-items: center; }
    .ym-card--right .ym-card__title { color: inherit; }

    .ym-cooldown-card {
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
    .ym-cooldown-card__icon { flex-shrink: 0; opacity: 0.9; margin-top: 0.1rem; }
    .ym-cooldown-card__title { margin: 0 0 0.25rem; font-size: 14px; font-weight: 600; color: #fcd34d; }
    .ym-cooldown-card__msg { margin: 0; font-size: 13px; line-height: 1.45; }

    .ym-claim { margin-top: 0.5rem; border-color: var(--border); transition: border-color 0.35s ease, box-shadow 0.35s ease, background 0.35s ease; }
    .ym-claim-heading { font-size: 1rem; font-weight: 600; color: #d1d5db; margin: 0 0 1rem; }
    .ym-claim-hint { margin: 0.6rem 0 0; font-size: 12px; }
    .ym-claim--ready {
        border-color: rgba(29, 158, 117, 0.45);
        box-shadow: 0 0 0 1px rgba(29, 158, 117, 0.15);
        background: rgba(13, 31, 24, 0.35);
    }
    .ym-claim-btn {
        display: block;
        width: 100%;
        margin-top: 0.85rem;
        padding: 0.7rem 1rem;
        border-radius: 8px;
        border: none;
        background: #1D9E75;
        color: #fff;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: background 0.15s ease, opacity 0.15s ease;
    }
    .ym-claim-btn:hover:not(:disabled) { background: #178f6a; }
    .ym-claim-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .ym-claim-result { margin: 0.85rem 0 0; min-height: 1.25rem; }
    .ym-tx-wrap a { color: var(--accent); word-break: break-all; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var EXPLORER_TX = @json($explorerTxBase);
    var CHECK_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>';

    var pairs = [
        ['Kappa', 'river imp'],
        ['Tengu', 'mountain spirit'],
        ['Rokurokubi', 'long-neck yokai'],
        ['Nurarihyon', 'old visitor yokai']
    ];
    var yokaiJa = {
        'Kappa': '河童',
        'Rokurokubi': '轆轤首',
        'Nurarihyon': 'ぬらりひょん',
        'Tengu': '天狗'
    };

    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = 'yokai_match';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;
    var matchedPairEls = [];

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
        if (!iso) return '\u2014';
        try { return new Date(iso).toLocaleString(); } catch (e) { return String(iso); }
    }

    function shortTx(txid) {
        if (!txid || txid.length < 20) return txid;
        return txid.slice(0, 10) + '\u2026' + txid.slice(-8);
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
        var claimSec = el('earn-claim-section');
        if (claimSec) {
            if (done) claimSec.classList.add('ym-claim--ready');
            else claimSec.classList.remove('ym-claim--ready');
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

    function updateProgressPill(n) {
        var pill = el('ym-progress');
        if (pill) pill.textContent = n + ' / 4 matched';
    }

    function addConnectorLine(a, b, animate) {
        var board = el('ym-board');
        var svg = el('ym-line-svg');
        if (!board || !svg || !a || !b) return;
        var w = board.clientWidth;
        var h = Math.max(board.clientHeight, 1);
        svg.setAttribute('width', String(w));
        svg.setAttribute('height', String(h));
        svg.setAttribute('viewBox', '0 0 ' + w + ' ' + h);
        var br = board.getBoundingClientRect();
        var ar = a.getBoundingClientRect();
        var br2 = b.getBoundingClientRect();
        var x1 = ar.right - br.left;
        var y1 = ar.top + ar.height / 2 - br.top;
        var x2 = br2.left - br.left;
        var y2 = br2.top + br2.height / 2 - br.top;
        var line = document.createElementNS('http://www.w3.org/2000/svg', 'line');
        line.setAttribute('x1', String(x1));
        line.setAttribute('y1', String(y1));
        line.setAttribute('x2', String(x2));
        line.setAttribute('y2', String(y2));
        line.setAttribute('stroke', '#1D9E75');
        line.setAttribute('stroke-width', '2');
        line.setAttribute('stroke-linecap', 'round');
        if (animate) {
            var len = Math.hypot(x2 - x1, y2 - y1);
            line.style.strokeDasharray = String(len);
            line.style.strokeDashoffset = String(len);
            line.style.transition = 'stroke-dashoffset 0.45s ease';
        }
        svg.appendChild(line);
        if (animate) {
            requestAnimationFrame(function () {
                requestAnimationFrame(function () {
                    line.style.strokeDashoffset = '0';
                });
            });
        }
    }

    function redrawConnectorLines() {
        var svg = el('ym-line-svg');
        if (!svg) return;
        while (svg.firstChild) svg.removeChild(svg.firstChild);
        matchedPairEls.forEach(function (pair) {
            addConnectorLine(pair[0], pair[1], false);
        });
    }

    function appendCheck(btn) {
        var span = document.createElement('span');
        span.className = 'ym-card__check';
        span.innerHTML = CHECK_SVG;
        btn.appendChild(span);
    }

    function initYokaiMatch() {
        var leftCol = el('ym-left');
        var rightCol = el('ym-right');
        if (!leftCol || !rightCol) return;

        var leftItems = [];
        var rightItems = [];
        for (var i = 0; i < pairs.length; i++) {
            leftItems.push({ key: i, label: pairs[i][0] });
            rightItems.push({ key: i, label: pairs[i][1] });
        }
        leftItems.sort(function () { return Math.random() - 0.5; });
        rightItems.sort(function () { return Math.random() - 0.5; });

        leftItems.forEach(function (c) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'ym-card ym-card--left';
            b.dataset.key = String(c.key);
            b.dataset.side = 'left';
            var body = document.createElement('span');
            body.className = 'ym-card__body';
            var t = document.createElement('span');
            t.className = 'ym-card__title';
            t.textContent = c.label;
            body.appendChild(t);
            var sub = document.createElement('span');
            sub.className = 'ym-card__sub';
            sub.textContent = yokaiJa[c.label] || '';
            body.appendChild(sub);
            b.appendChild(body);
            leftCol.appendChild(b);
        });

        rightItems.forEach(function (c) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'ym-card ym-card--right';
            b.dataset.key = String(c.key);
            b.dataset.side = 'right';
            var body = document.createElement('span');
            body.className = 'ym-card__body';
            var t = document.createElement('span');
            t.className = 'ym-card__title';
            t.textContent = c.label;
            body.appendChild(t);
            b.appendChild(body);
            rightCol.appendChild(b);
        });

        var first = null;
        var matched = 0;
        updateProgressPill(0);

        function wireClick(b) {
            b.addEventListener('click', function () {
                if (b.disabled) return;
                if (first === null) {
                    first = b;
                    b.classList.add('ym-card--selected');
                    return;
                }
                if (first === b) return;
                if (first.dataset.key === b.dataset.key) {
                    first.classList.remove('ym-card--selected');
                    first.disabled = true;
                    b.disabled = true;
                    first.classList.add('ym-card--matched');
                    b.classList.add('ym-card--matched');
                    appendCheck(first);
                    appendCheck(b);
                    matchedPairEls.push([first, b]);
                    requestAnimationFrame(function () {
                        addConnectorLine(first, b, true);
                    });
                    matched += 1;
                    updateProgressPill(matched);
                    first = null;
                    if (matched === 4) {
                        setActivityDone(true, 'Yokai match complete. You can claim now.', {
                            matches: [[0, 0], [1, 1], [2, 2], [3, 3]]
                        });
                        var banner = el('ym-success-banner');
                        if (banner) {
                            banner.classList.add('is-visible');
                            banner.setAttribute('aria-hidden', 'false');
                        }
                    }
                    return;
                }
                var prev = first;
                first = null;
                prev.classList.remove('ym-card--selected');
                b.classList.add('ym-card--wrong');
                setTimeout(function () {
                    b.classList.remove('ym-card--wrong');
                }, 400);
            });
        }

        Array.prototype.forEach.call(leftCol.querySelectorAll('.ym-card'), wireClick);
        Array.prototype.forEach.call(rightCol.querySelectorAll('.ym-card'), wireClick);

        window.addEventListener('resize', function () {
            if (!matchedPairEls.length) return;
            requestAnimationFrame(redrawConnectorLines);
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
            if (result) result.textContent = 'Match all pairs first.';
            return;
        }
        if (hasTurnstile && !turnstileToken) {
            if (result) result.textContent = 'Complete verification first.';
            return;
        }
        var idem = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + '-' + String(Math.random()).slice(2);
        if (result) result.textContent = 'Verifying\u2026';

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
            if (result) result.textContent = 'Submitting claim\u2026';
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
                if (result) result.textContent = 'Claim accepted (pending). Amount: ' + (data.amount || '\u2014') + ' KOTO.';
                fetchClaimAvailability();
                return;
            }
            if (data.success) {
                var amt = data.amount || '\u2014';
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
        initYokaiMatch();
        recordLastVisit();
        fetchClaimAvailability();
        updateClaimButton();
    });
})();
</script>
@endpush
