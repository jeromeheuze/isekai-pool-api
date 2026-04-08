@extends('earn.layout')

@section('title', 'Shrine Puzzle')

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="shrine_puzzle"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>

    <p class="muted sp-back"><a href="/earn">← Earn hub</a></p>

    <header class="sp-header">
        <div class="sp-header__title-row">
            <span class="sp-header__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#7c6af7" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="5" height="5" x="3" y="3" rx="1"/><rect width="5" height="5" x="9.5" y="3" rx="1"/><rect width="5" height="5" x="16" y="3" rx="1"/>
                    <rect width="5" height="5" x="3" y="9.5" rx="1"/><rect width="5" height="5" x="9.5" y="9.5" rx="1"/><rect width="5" height="5" x="16" y="9.5" rx="1"/>
                    <rect width="5" height="5" x="3" y="16" rx="1"/><rect width="5" height="5" x="9.5" y="16" rx="1"/><rect width="5" height="5" x="16" y="16" rx="1"/>
                </svg>
            </span>
            <h1 class="sp-header__title">Shrine Puzzle</h1>
        </div>
        <p class="sp-header__sub muted">Arrange the ritual steps in the correct order</p>
        <p class="sp-header__pill muted">Drag to reorder — or use the arrows</p>
    </header>

    <div class="sp-wallet-wrap">
        <label for="claim-wallet" class="sp-wallet-label muted">Wallet</label>
        <input type="text" id="claim-wallet" class="sp-wallet-input" autocomplete="off" placeholder="k1…" title="">
    </div>

    <section class="sp-challenge card" aria-labelledby="sp-challenge-heading">
        <h2 id="sp-challenge-heading" class="sr-only">Ritual steps</h2>

        <div id="sp-puzzle-root">
            <div id="sp-success-banner" class="sp-success-banner" aria-hidden="true">
                <span class="sp-success-banner__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#c4b5fd" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m12 3-1.912 5.813a2 2 0 0 1-1.275 1.275L3 12l5.813 1.912a2 2 0 0 1 1.275 1.275L12 21l1.912-5.813a2 2 0 0 1 1.275-1.275L21 12l-5.813-1.912a2 2 0 0 1-1.275-1.275L12 3Z"/>
                        <path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/>
                    </svg>
                </span>
                <p class="sp-success-banner__text">Ritual order confirmed — claim your reward</p>
            </div>

            <div id="sp-fail-card" class="sp-fail-card" hidden>
                <span class="sp-fail-card__icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fbbf24" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/>
                        <path d="M12 9v4"/><path d="M12 17h.01"/>
                    </svg>
                </span>
                <p class="sp-fail-card__text">The ritual order is not correct — try again</p>
            </div>

            <div id="sp-puzzle-active">
                <ul id="puzzle-list" class="sp-puzzle-list" role="list"></ul>
                <button type="button" id="verify-puzzle" class="sp-verify-btn">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M20 6 9 17l-5-5"/>
                    </svg>
                    Verify order
                </button>
            </div>
        </div>

        <p id="activity-state" class="sr-only" aria-live="polite"></p>
    </section>

    <div id="earn-claim-unavailable" class="sp-cooldown-card" style="display:none;">
        <div class="sp-cooldown-card__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </div>
        <div>
            <p class="sp-cooldown-card__title">Claim on cooldown</p>
            <p id="earn-claim-unavailable-msg" class="sp-cooldown-card__msg muted"></p>
        </div>
    </div>

    <div id="earn-claim-section" class="sp-claim card">
        <h2 class="sp-claim-heading">Claim</h2>
        @if (!empty($turnstileSiteKey))
            <div class="cf-turnstile"
                 data-sitekey="{{ $turnstileSiteKey }}"
                 data-callback="onEarnTurnstile"
                 data-expired-callback="onEarnTurnstileExpired"
                 data-error-callback="onEarnTurnstileExpired"></div>
            <p class="muted sp-claim-hint">Verify before claiming.</p>
        @else
            <p class="muted sp-claim-hint">Turnstile not configured.</p>
        @endif
        <button type="button" id="claim-btn" class="sp-claim-btn" disabled>Claim {{ $rewardKoto }} KOTO</button>
        <p id="claim-result" class="sp-claim-result muted"></p>
        <p id="claim-tx-wrap" class="sp-tx-wrap muted" style="display:none;font-size:12px;"></p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .sp-back { margin-bottom: 1rem; }
    .sp-header { margin-bottom: 1.25rem; }
    .sp-header__title-row { display: flex; align-items: center; gap: 0.65rem; margin-bottom: 0.5rem; flex-wrap: wrap; }
    .sp-header__icon { display: flex; flex-shrink: 0; }
    .sp-header__title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        color: #e5e7eb;
    }
    .sp-header__sub { margin: 0 0 0.5rem; font-size: 14px; max-width: 36rem; line-height: 1.45; }
    .sp-header__pill {
        display: inline-block;
        margin: 0;
        padding: 0.25rem 0.65rem;
        font-size: 12px;
        border-radius: 6px;
        border: 1px solid var(--border);
        background: #111318;
        color: var(--muted);
    }

    .sp-wallet-wrap { margin-bottom: 1.25rem; }
    .sp-wallet-label { display: block; margin-bottom: 0.35rem; font-size: 12px; }
    .sp-wallet-input {
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

    .sp-challenge { margin-bottom: 1rem; border-color: #1e2330; }

    .sp-puzzle-list {
        list-style: none;
        margin: 0 0 1rem;
        padding: 0;
    }
    .sp-step-card {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 8px;
        padding: 12px 16px;
        border-radius: 8px;
        border: 1px solid #1e2030;
        border-left: 3px solid #7c6af7;
        background: #111318;
        cursor: grab;
        transition: opacity 0.15s ease, transform 0.15s ease, border-color 0.15s ease;
    }
    .sp-step-card:active { cursor: grabbing; }
    .sp-step-card--dragging {
        opacity: 0.6;
        border-color: #7c6af7;
        border-left-color: #7c6af7;
        transform: scale(1.02);
    }
    .sp-step-card--correct { border-left-color: #1D9E75 !important; }
    .sp-step-card--wrong { border-left-color: #D85A30 !important; }

    .sp-step-card__grip {
        flex-shrink: 0;
        display: flex;
        color: #444;
        cursor: grab;
    }
    .sp-step-card__grip svg { display: block; }
    .sp-step-card__badge {
        flex-shrink: 0;
        width: 26px;
        height: 26px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 1px solid #7c6af7;
        font-size: 12px;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        color: #c4b5fd;
    }
    .sp-step-card__body { flex: 1; min-width: 0; display: flex; flex-direction: column; gap: 0.15rem; }
    .sp-step-card__title { font-size: 14px; color: #fff; font-family: ui-monospace, 'JetBrains Mono', monospace; }
    .sp-step-card__ja { font-size: 11px; color: rgba(29, 158, 117, 0.75); }
    .sp-step-card__arrows { flex-shrink: 0; display: flex; flex-direction: column; gap: 2px; }
    .sp-step-card__btn {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 26px;
        padding: 0;
        border: none;
        border-radius: 4px;
        background: transparent;
        color: #6b7280;
        cursor: pointer;
        transition: color 0.15s ease, background 0.15s ease;
    }
    .sp-step-card__btn:hover { color: #7c6af7; background: rgba(124, 106, 247, 0.12); }
    .sp-step-card__btn svg { display: block; }

    .sp-verify-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        width: 100%;
        margin-top: 0.25rem;
        padding: 0.7rem 1rem;
        border: none;
        border-radius: 8px;
        background: #7c6af7;
        color: #fff;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: filter 0.15s ease, opacity 0.15s ease;
    }
    .sp-verify-btn:hover:not(:disabled) { filter: brightness(1.06); }
    .sp-verify-btn:disabled { opacity: 0.5; cursor: not-allowed; }

    .sp-success-banner {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0 1rem;
        margin-bottom: 0;
        max-height: 0;
        opacity: 0;
        overflow: hidden;
        border-radius: 10px;
        border: 1px solid transparent;
        background: linear-gradient(135deg, rgba(124, 106, 247, 0.2) 0%, rgba(30, 27, 45, 0.95) 100%);
        transition: opacity 0.45s ease, max-height 0.5s ease, margin 0.45s ease, padding 0.45s ease, border-color 0.45s ease;
    }
    .sp-success-banner.is-visible {
        max-height: 5rem;
        opacity: 1;
        margin-bottom: 1rem;
        padding: 1rem 1.1rem;
        border-color: rgba(124, 106, 247, 0.45);
    }
    .sp-success-banner__icon { flex-shrink: 0; display: flex; color: #a78bfa; }
    .sp-success-banner__text { margin: 0; font-size: 15px; font-weight: 600; color: #e9e5ff; }

    .sp-fail-card {
        display: flex;
        align-items: flex-start;
        gap: 0.65rem;
        padding: 0.85rem 1rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        border: 1px solid rgba(251, 191, 36, 0.4);
        background: rgba(251, 191, 36, 0.08);
    }
    .sp-fail-card__icon { flex-shrink: 0; margin-top: 0.1rem; }
    .sp-fail-card__text { margin: 0; font-size: 13px; color: #fde68a; line-height: 1.45; }

    .sp-cooldown-card {
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
    .sp-cooldown-card__icon { flex-shrink: 0; opacity: 0.9; margin-top: 0.1rem; }
    .sp-cooldown-card__title { margin: 0 0 0.25rem; font-size: 14px; font-weight: 600; color: #fcd34d; }
    .sp-cooldown-card__msg { margin: 0; font-size: 13px; line-height: 1.45; }

    .sp-claim { margin-top: 0.5rem; border-color: var(--border); transition: border-color 0.35s ease, box-shadow 0.35s ease, background 0.35s ease; }
    .sp-claim-heading { font-size: 1rem; font-weight: 600; color: #d1d5db; margin: 0 0 1rem; }
    .sp-claim-hint { margin: 0.6rem 0 0; font-size: 12px; }
    .sp-claim--ready {
        border-color: rgba(124, 106, 247, 0.45);
        box-shadow: 0 0 0 1px rgba(124, 106, 247, 0.1);
        background: rgba(30, 27, 45, 0.35);
    }
    .sp-claim-btn {
        display: block;
        width: 100%;
        margin-top: 0.85rem;
        padding: 0.7rem 1rem;
        border: none;
        border-radius: 8px;
        background: #7c6af7;
        color: #fff;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        transition: filter 0.15s ease, opacity 0.15s ease;
    }
    .sp-claim-btn:hover:not(:disabled) { filter: brightness(1.06); }
    .sp-claim-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .sp-claim-result { margin: 0.85rem 0 0; min-height: 1.25rem; }
    .sp-tx-wrap a { color: var(--accent); word-break: break-all; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    // TODO: add drag-and-drop later; ↑/↓ reorder only for now.
    var EXPLORER_TX = @json($explorerTxBase);
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = 'shrine_puzzle';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;

    var steps = ['Bow', 'Cleanse hands', 'Offer prayer', 'Final bow'];
    var stepJa = {
        'Bow': 'お辞儀',
        'Cleanse hands': '手を清める',
        'Offer prayer': '祈りを捧げる',
        'Final bow': '最後のお辞儀'
    };
    var proofOrder = ['Bow', 'Cleanse hands', 'Offer prayer', 'Final bow'];

    var GRIP_SVG = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="12" r="1"/><circle cx="9" cy="5" r="1"/><circle cx="9" cy="19" r="1"/><circle cx="15" cy="12" r="1"/><circle cx="15" cy="5" r="1"/><circle cx="15" cy="19" r="1"/></svg>';
    var CHEV_UP = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m18 15-6-6-6 6"/></svg>';
    var CHEV_DN = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>';

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
            if (done) claimSec.classList.add('sp-claim--ready');
            else claimSec.classList.remove('sp-claim--ready');
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

    function refreshBadges(list) {
        var lis = list.querySelectorAll('.sp-step-card');
        for (var i = 0; i < lis.length; i++) {
            var badge = lis[i].querySelector('.sp-step-card__badge');
            if (badge) badge.textContent = String(i + 1);
        }
    }

    function clearStepFeedback(list) {
        var lis = list.querySelectorAll('.sp-step-card');
        for (var i = 0; i < lis.length; i++) {
            lis[i].classList.remove('sp-step-card--correct', 'sp-step-card--wrong');
        }
        var failCard = el('sp-fail-card');
        if (failCard) failCard.setAttribute('hidden', 'hidden');
    }

    function initShrinePuzzle() {
        var list = el('puzzle-list');
        var verify = el('verify-puzzle');
        if (!list || !verify) return;

        var order = [0, 1, 2, 3].sort(function () { return Math.random() - 0.5; });

        order.forEach(function (idx) {
            var li = document.createElement('li');
            li.className = 'sp-step-card';
            li.dataset.step = steps[idx];
            li.setAttribute('role', 'listitem');

            var grip = document.createElement('span');
            grip.className = 'sp-step-card__grip';
            grip.setAttribute('aria-hidden', 'true');
            grip.innerHTML = GRIP_SVG;

            var badge = document.createElement('span');
            badge.className = 'sp-step-card__badge';
            badge.textContent = '0';

            var body = document.createElement('div');
            body.className = 'sp-step-card__body';
            var title = document.createElement('span');
            title.className = 'sp-step-card__title';
            title.textContent = steps[idx];
            var ja = document.createElement('span');
            ja.className = 'sp-step-card__ja';
            ja.textContent = stepJa[steps[idx]] || '';
            body.appendChild(title);
            body.appendChild(ja);

            var arrows = document.createElement('div');
            arrows.className = 'sp-step-card__arrows';
            var up = document.createElement('button');
            up.type = 'button';
            up.className = 'sp-step-card__btn';
            up.setAttribute('aria-label', 'Move step up');
            up.innerHTML = CHEV_UP;
            up.addEventListener('click', function () {
                clearStepFeedback(list);
                var prev = li.previousElementSibling;
                if (prev) list.insertBefore(li, prev);
                refreshBadges(list);
            });
            var down = document.createElement('button');
            down.type = 'button';
            down.className = 'sp-step-card__btn';
            down.setAttribute('aria-label', 'Move step down');
            down.innerHTML = CHEV_DN;
            down.addEventListener('click', function () {
                clearStepFeedback(list);
                var next = li.nextElementSibling;
                if (next) list.insertBefore(next, li);
                refreshBadges(list);
            });
            arrows.appendChild(up);
            arrows.appendChild(down);

            li.appendChild(grip);
            li.appendChild(badge);
            li.appendChild(body);
            li.appendChild(arrows);
            list.appendChild(li);
        });

        refreshBadges(list);

        verify.addEventListener('click', function () {
            var items = Array.prototype.map.call(list.querySelectorAll('li'), function (item) {
                return item.dataset.step;
            });
            var ok = items.join('|') === steps.join('|');
            if (ok) {
                clearStepFeedback(list);
                var lis = list.querySelectorAll('.sp-step-card');
                for (var j = 0; j < lis.length; j++) {
                    lis[j].classList.add('sp-step-card--correct');
                }
                setActivityDone(true, 'Shrine puzzle solved. You can claim now.', { order: proofOrder });
                var active = el('sp-puzzle-active');
                var banner = el('sp-success-banner');
                if (active) active.setAttribute('hidden', 'hidden');
                if (banner) {
                    banner.classList.add('is-visible');
                    banner.setAttribute('aria-hidden', 'false');
                }
                verify.disabled = true;
            } else {
                setActivityDone(false, 'Reorder the steps and verify again.');
                var lis = list.querySelectorAll('li');
                for (var i = 0; i < lis.length; i++) {
                    lis[i].classList.remove('sp-step-card--correct', 'sp-step-card--wrong');
                    if (lis[i].dataset.step === steps[i]) {
                        lis[i].classList.add('sp-step-card--correct');
                    } else {
                        lis[i].classList.add('sp-step-card--wrong');
                    }
                }
                var failCard = el('sp-fail-card');
                if (failCard) failCard.removeAttribute('hidden');
            }
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
            if (result) result.textContent = 'Complete the puzzle first.';
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
        initShrinePuzzle();
        recordLastVisit();
        fetchClaimAvailability();
        updateClaimButton();
    });
})();
</script>
@endpush
