@extends('earn.layout')

@section('title', 'Yokai Quiz')

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="yokai_quiz"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>

    <p class="muted yq-back"><a href="/earn">← Earn hub</a></p>

    <header class="yq-header">
        <div class="yq-header__title-row">
            <span class="yq-header__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/>
                    <path d="M12 17h.01"/>
                </svg>
            </span>
            <h1 class="yq-header__title">Yokai Quiz</h1>
            <span id="yq-q-pill" class="yq-q-pill">Q 1 / 5</span>
        </div>
        <p class="yq-header__sub muted">Five questions about Japanese folklore — score 4/5 or better</p>
        <div class="yq-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0" id="yq-progress-bar">
            <div id="yq-progress-fill" class="yq-progress-fill"></div>
        </div>
    </header>

    <div class="yq-wallet-wrap">
        <label for="claim-wallet" class="yq-wallet-label muted">Wallet</label>
        <input type="text" id="claim-wallet" class="yq-wallet-input" autocomplete="off" placeholder="k1…" title="">
    </div>

    <section class="yq-challenge card" aria-labelledby="yq-challenge-heading">
        <h2 id="yq-challenge-heading" class="sr-only">Quiz</h2>

        <div id="yq-pass-banner" class="yq-pass-banner" aria-hidden="true">
            <span class="yq-pass-banner__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                    <path d="m9 11 3 3L22 4"/>
                </svg>
            </span>
            <p class="yq-pass-banner__text">Folklore knowledge confirmed — claim your reward below.</p>
        </div>

        <div id="yq-quiz-root">
            <div id="yq-hidden-inputs" class="sr-only" aria-hidden="true">
                @foreach ($yokaiQuestions as $i => $q)
                    @foreach ($q['options'] as $j => $_opt)
                        <input type="radio" name="quiz-{{ $i }}" value="{{ $j }}" id="quiz-{{ $i }}-opt-{{ $j }}">
                    @endforeach
                @endforeach
            </div>

            <div id="yq-step-wrap">
                <div class="yq-q-card">
                    <span class="yq-q-card__watermark" id="yq-watermark" aria-hidden="true">轆轤首</span>
                    <p id="yq-q-text" class="yq-q-text"></p>
                    <div id="yq-options" class="yq-options"></div>
                    <button type="button" id="yq-next-btn" class="yq-next-btn" disabled>Next →</button>
                </div>
            </div>

            <div id="yq-summary" class="yq-summary" hidden>
                <p class="yq-summary__label muted">Your score</p>
                <p id="yq-summary-score" class="yq-summary__score">0 / 5</p>

                <div id="yq-summary-pass-msg" class="yq-summary-msg yq-summary-msg--pass" hidden>
                    <span class="yq-summary-msg__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg>
                    </span>
                    <span>Folklore knowledge confirmed</span>
                </div>
                <div id="yq-summary-fail-msg" class="yq-summary-msg yq-summary-msg--fail" hidden>
                    <span class="yq-summary-msg__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    </span>
                    <span>Not quite — try again tomorrow</span>
                </div>

                <button type="button" id="yq-submit-btn" class="yq-submit-btn">Submit answers</button>
                <p id="yq-grade-msg" class="muted" style="margin:0.75rem 0 0;font-size:12px;"></p>
            </div>
        </div>

        <div id="yq-fail-panel" class="yq-fail-panel" hidden>
            <div class="yq-fail-panel__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            </div>
            <div>
                <p class="yq-fail-panel__title">Keep studying folklore</p>
                <p class="yq-fail-panel__body muted">Not quite — try again tomorrow.</p>
                <p id="yq-fail-cooldown" class="yq-fail-panel__cooldown muted"></p>
            </div>
        </div>

        <p id="activity-state" class="sr-only" aria-live="polite"></p>
    </section>

    <div id="earn-claim-unavailable" class="yq-cooldown-card" style="display:none;">
        <div class="yq-cooldown-card__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </div>
        <div>
            <p class="yq-cooldown-card__title">Claim on cooldown</p>
            <p id="earn-claim-unavailable-msg" class="yq-cooldown-card__msg muted"></p>
        </div>
    </div>

    <div id="earn-claim-section" class="yq-claim card">
        <h2 class="yq-claim-heading">Claim</h2>
        @if (!empty($turnstileSiteKey))
            <div class="cf-turnstile"
                 data-sitekey="{{ $turnstileSiteKey }}"
                 data-callback="onEarnTurnstile"
                 data-expired-callback="onEarnTurnstileExpired"
                 data-error-callback="onEarnTurnstileExpired"></div>
            <p class="muted yq-claim-hint">Verify before claiming.</p>
        @else
            <p class="muted yq-claim-hint">Turnstile not configured.</p>
        @endif
        <button type="button" id="claim-btn" class="yq-claim-btn" disabled>Claim {{ $rewardKoto }} KOTO</button>
        <p id="claim-result" class="yq-claim-result muted"></p>
        <p id="claim-tx-wrap" class="yq-tx-wrap muted" style="display:none;font-size:12px;"></p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .yq-back { margin-bottom: 1rem; }
    .yq-header { margin-bottom: 1.25rem; }
    .yq-header__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem 1rem;
        margin-bottom: 0.5rem;
    }
    .yq-header__icon { display: flex; flex-shrink: 0; }
    .yq-header__title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        font-family: inherit;
        flex: 1;
        min-width: 0;
    }
    .yq-q-pill {
        font-size: 11px;
        font-family: inherit;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        border: 1px solid rgba(29, 158, 117, 0.55);
        background: rgba(29, 158, 117, 0.1);
        color: #5eead4;
        white-space: nowrap;
    }
    .yq-header__sub { margin: 0 0 0.75rem; font-size: 13px; max-width: 38rem; line-height: 1.45; }
    .yq-progress-track {
        height: 3px;
        border-radius: 2px;
        background: #1e2030;
        overflow: hidden;
    }
    .yq-progress-fill {
        height: 100%;
        width: 0%;
        background: #1D9E75;
        border-radius: 2px;
        transition: width 0.35s ease;
    }

    .yq-wallet-wrap { margin-bottom: 1.25rem; }
    .yq-wallet-label { display: block; margin-bottom: 0.35rem; font-size: 12px; }
    .yq-wallet-input {
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

    .yq-challenge { margin-bottom: 1rem; overflow: hidden; }

    .yq-pass-banner {
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
        background: linear-gradient(135deg, rgba(29, 158, 117, 0.2) 0%, rgba(13, 31, 24, 0.95) 100%);
        color: #a7f3d0;
        transition: opacity 0.45s ease, max-height 0.5s ease, margin 0.45s ease, padding 0.45s ease, border-color 0.45s ease;
    }
    .yq-pass-banner.is-visible {
        max-height: 5rem;
        opacity: 1;
        margin-bottom: 1rem;
        padding: 1rem 1.1rem;
        border-color: rgba(29, 158, 117, 0.45);
    }
    .yq-pass-banner__icon { flex-shrink: 0; color: #1D9E75; }
    .yq-pass-banner__text { margin: 0; font-size: 15px; font-weight: 600; color: #ecfdf5; }

    .yq-q-card {
        position: relative;
        background: #111318;
        border: 1px solid #1e2030;
        border-left: 3px solid #1D9E75;
        border-radius: 8px;
        padding: 1.5rem;
        overflow: hidden;
    }
    .yq-q-card__watermark {
        position: absolute;
        right: 0.25rem;
        bottom: 0;
        font-size: 80px;
        line-height: 1;
        color: rgba(29, 158, 117, 0.06);
        font-family: "Hiragino Sans", "Yu Gothic UI", "Meiryo", "Noto Sans JP", sans-serif;
        pointer-events: none;
        user-select: none;
    }
    .yq-q-text {
        position: relative;
        margin: 0 0 1.1rem;
        font-size: 15px;
        color: #fff;
        font-family: inherit;
        line-height: 1.45;
        max-width: 100%;
    }
    .yq-options { margin-bottom: 1rem; }
    .yq-opt {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        width: 100%;
        margin: 0 0 6px;
        padding: 10px 16px;
        text-align: left;
        font-family: inherit;
        font-size: 13px;
        color: #888;
        cursor: pointer;
        border: 1px solid #1e2030;
        border-radius: 6px;
        background: #0d0f14;
        transition: border-color 0.15s ease, background 0.15s ease, color 0.15s ease;
    }
    .yq-opt:hover {
        border-color: rgba(29, 158, 117, 0.5);
        color: #fff;
    }
    .yq-opt.is-selected {
        border-color: #1D9E75;
        background: #0d1f18;
        color: #fff;
    }
    .yq-opt__dot {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .yq-opt.is-selected .yq-opt__dot { opacity: 1; }
    .yq-opt__label { flex: 1; min-width: 0; }
    .yq-next-btn {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        padding: 0.6rem 1rem;
        border-radius: 8px;
        border: 1px solid #1D9E75;
        background: rgba(29, 158, 117, 0.12);
        color: #a7f3d0;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s ease, color 0.15s ease;
    }
    .yq-next-btn:hover:not(:disabled) { background: rgba(29, 158, 117, 0.25); color: #fff; }
    .yq-next-btn:disabled { opacity: 0.45; cursor: not-allowed; }

    .yq-summary { text-align: center; padding: 0.5rem 0 0; }
    .yq-summary__label { margin: 0 0 0.35rem; font-size: 12px; }
    .yq-summary__score {
        margin: 0 0 1rem;
        font-size: 48px;
        font-weight: 700;
        color: #f0c040;
        font-family: inherit;
        line-height: 1.1;
    }
    .yq-summary-msg {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin: 0 0 1.25rem;
        font-size: 14px;
        font-weight: 500;
    }
    .yq-summary-msg--pass { color: #6ee7b7; }
    .yq-summary-msg--fail { color: #fcd34d; }
    .yq-summary-msg__icon { display: flex; flex-shrink: 0; }
    .yq-submit-btn {
        width: 100%;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        border: 1px solid rgba(29, 158, 117, 0.55);
        background: rgba(29, 158, 117, 0.15);
        color: #ecfdf5;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .yq-submit-btn:hover { background: rgba(29, 158, 117, 0.28); }

    .yq-fail-panel {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 1rem 1.15rem;
        margin-top: 0.5rem;
        border-radius: 10px;
        border: 1px solid rgba(251, 191, 36, 0.4);
        background: rgba(251, 191, 36, 0.08);
    }
    .yq-fail-panel__icon { flex-shrink: 0; margin-top: 0.1rem; }
    .yq-fail-panel__title { margin: 0 0 0.35rem; font-size: 1rem; font-weight: 600; color: #fcd34d; }
    .yq-fail-panel__body { margin: 0; font-size: 13px; }
    .yq-fail-panel__cooldown { margin: 0.5rem 0 0; font-size: 12px; }

    .yq-cooldown-card {
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
    .yq-cooldown-card__icon { flex-shrink: 0; opacity: 0.9; margin-top: 0.1rem; }
    .yq-cooldown-card__title { margin: 0 0 0.25rem; font-size: 14px; font-weight: 600; color: #fcd34d; }
    .yq-cooldown-card__msg { margin: 0; font-size: 13px; line-height: 1.45; }

    .yq-claim { margin-top: 0.5rem; border-color: var(--border); transition: border-color 0.35s ease, box-shadow 0.35s ease, background 0.35s ease; }
    .yq-claim-heading { font-size: 1rem; font-weight: 600; color: #d1d5db; margin: 0 0 1rem; }
    .yq-claim-hint { margin: 0.6rem 0 0; font-size: 12px; }
    .yq-claim--ready {
        border-color: rgba(29, 158, 117, 0.45);
        box-shadow: 0 0 0 1px rgba(29, 158, 117, 0.12);
        background: rgba(13, 31, 24, 0.35);
    }
    .yq-claim-btn {
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
    .yq-claim-btn:hover:not(:disabled) { background: #178f6a; }
    .yq-claim-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    .yq-claim-result { margin: 0.85rem 0 0; min-height: 1.25rem; }
    .yq-tx-wrap a { color: var(--accent); word-break: break-all; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var EXPLORER_TX = @json($explorerTxBase);
    var questions = @json($yokaiQuestions);
    var watermarks = @json($yokaiWatermarks);
    var passScore = 4;
    var doneMessage = 'Yokai quiz passed.';
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = 'yokai_quiz';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;
    var currentIdx = 0;

    var DOT_SVG = '<svg class="yq-opt__dot" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1D9E75" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3" fill="#1D9E75" stroke="none"/></svg>';

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

    function fetchFailCooldownLine() {
        var line = el('yq-fail-cooldown');
        if (!line) return;
        var w = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        if (!w || w.length < 20) {
            line.textContent = '';
            return;
        }
        fetch(API + '/faucet/status?wallet=' + encodeURIComponent(w), { headers: { 'Accept': 'application/json' } })
            .then(function (r) { return r.json(); })
            .then(function (data) {
                if (data.error || !data.activities) {
                    line.textContent = '';
                    return;
                }
                var mine = null;
                for (var i = 0; i < data.activities.length; i++) {
                    if (data.activities[i].slug === slug) { mine = data.activities[i]; break; }
                }
                if (mine && mine.next_claim_at) {
                    line.textContent = 'Next eligible claim: ' + formatWhen(mine.next_claim_at);
                } else {
                    line.textContent = '';
                }
            })
            .catch(function () { line.textContent = ''; });
    }

    function setActivityDone(done, message, proof) {
        activityDone = !!done;
        if (done && proof) proofPayload = proof;
        if (!done) proofPayload = null;
        var state = el('activity-state');
        if (state) state.textContent = message || '';
        var claimSec = el('earn-claim-section');
        if (claimSec) {
            if (done) claimSec.classList.add('yq-claim--ready');
            else claimSec.classList.remove('yq-claim--ready');
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

    function countAnswered() {
        var n = 0;
        for (var i = 0; i < questions.length; i++) {
            if (document.querySelector('input[name="quiz-' + i + '"]:checked')) n += 1;
        }
        return n;
    }

    function computeScore() {
        var score = 0;
        for (var i = 0; i < questions.length; i++) {
            var checked = document.querySelector('input[name="quiz-' + i + '"]:checked');
            if (checked && parseInt(checked.value, 10) === questions[i].answer) score += 1;
        }
        return score;
    }

    function updateProgressUI() {
        var n = countAnswered();
        var fill = el('yq-progress-fill');
        var bar = el('yq-progress-bar');
        if (fill) fill.style.width = (n / questions.length * 100) + '%';
        if (bar) bar.setAttribute('aria-valuenow', String(n));
    }

    function renderOptions() {
        var q = questions[currentIdx];
        var wrap = el('yq-options');
        if (!wrap || !q) return;
        wrap.innerHTML = '';
        var selected = document.querySelector('input[name="quiz-' + currentIdx + '"]:checked');
        var selVal = selected ? selected.value : null;
        for (var j = 0; j < q.options.length; j++) {
            (function (jj) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'yq-opt' + (selVal !== null && String(selVal) === String(jj) ? ' is-selected' : '');
                btn.innerHTML = DOT_SVG + '<span class="yq-opt__label"></span>';
                btn.querySelector('.yq-opt__label').textContent = q.options[jj];
                btn.addEventListener('click', function () {
                    var inp = document.querySelector('input[name="quiz-' + currentIdx + '"][value="' + jj + '"]');
                    if (inp) {
                        inp.checked = true;
                        inp.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    renderOptions();
                    var nextBtn = el('yq-next-btn');
                    if (nextBtn) nextBtn.disabled = false;
                    updateProgressUI();
                });
                wrap.appendChild(btn);
            })(j);
        }
    }

    function showStep() {
        var stepWrap = el('yq-step-wrap');
        var summary = el('yq-summary');
        if (summary) summary.setAttribute('hidden', 'hidden');

        var q = questions[currentIdx];
        var wm = el('yq-watermark');
        var qt = el('yq-q-text');
        var pill = el('yq-q-pill');
        var nextBtn = el('yq-next-btn');
        if (wm && watermarks[currentIdx] !== undefined) wm.textContent = watermarks[currentIdx];
        if (qt) qt.textContent = q.q;
        if (pill) pill.textContent = 'Q ' + (currentIdx + 1) + ' / ' + questions.length;

        var has = !!document.querySelector('input[name="quiz-' + currentIdx + '"]:checked');
        if (nextBtn) {
            nextBtn.disabled = !has;
            nextBtn.textContent = currentIdx >= questions.length - 1 ? 'Review \u2192' : 'Next \u2192';
        }
        if (stepWrap) stepWrap.removeAttribute('hidden');
        renderOptions();
        updateProgressUI();
    }

    function showSummary() {
        var stepWrap = el('yq-step-wrap');
        var summary = el('yq-summary');
        var scoreEl = el('yq-summary-score');
        var passMsg = el('yq-summary-pass-msg');
        var failMsg = el('yq-summary-fail-msg');
        if (stepWrap) stepWrap.setAttribute('hidden', 'hidden');
        if (summary) summary.removeAttribute('hidden');
        var sc = computeScore();
        if (scoreEl) scoreEl.textContent = sc + ' / ' + questions.length;
        if (passMsg && failMsg) {
            if (sc >= passScore) {
                passMsg.removeAttribute('hidden');
                failMsg.setAttribute('hidden', 'hidden');
            } else {
                passMsg.setAttribute('hidden', 'hidden');
                failMsg.removeAttribute('hidden');
            }
        }
        var pill = el('yq-q-pill');
        if (pill) pill.textContent = 'Review';
        updateProgressUI();
    }

    function runGrade() {
        var scoreEl = el('yq-grade-msg');
        var score = 0;
        var unanswered = 0;
        for (var i = 0; i < questions.length; i++) {
            var checked = document.querySelector('input[name="quiz-' + i + '"]:checked');
            if (!checked) {
                unanswered += 1;
                continue;
            }
            if (parseInt(checked.value, 10) === questions[i].answer) {
                score += 1;
            }
        }
        if (unanswered > 0) {
            if (scoreEl) scoreEl.textContent = 'Please answer all questions.';
            setActivityDone(false, 'Answer every question before grading.');
            return;
        }
        if (scoreEl) scoreEl.textContent = 'Score: ' + score + '/' + questions.length;
        if (score >= passScore) {
            var ans = [];
            for (var ai = 0; ai < questions.length; ai++) {
                var chk = document.querySelector('input[name="quiz-' + ai + '"]:checked');
                ans.push(chk ? parseInt(chk.value, 10) : -1);
            }
            setActivityDone(true, doneMessage + ' Score: ' + score + '/' + questions.length + '.', { answers: ans });
            var root = el('yq-quiz-root');
            var banner = el('yq-pass-banner');
            if (root) root.setAttribute('hidden', 'hidden');
            if (banner) {
                banner.classList.add('is-visible');
                banner.setAttribute('aria-hidden', 'false');
            }
        } else {
            setActivityDone(false, 'Need ' + passScore + '/' + questions.length + ' to claim. You scored ' + score + '.');
            var root = el('yq-quiz-root');
            var failPanel = el('yq-fail-panel');
            if (root) root.setAttribute('hidden', 'hidden');
            if (failPanel) failPanel.removeAttribute('hidden');
            fetchFailCooldownLine();
            fetchClaimAvailability();
        }
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
            if (result) result.textContent = 'Finish the quiz first.';
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
        var nextBtn = el('yq-next-btn');
        if (nextBtn) {
            nextBtn.addEventListener('click', function () {
                if (currentIdx < questions.length - 1) {
                    currentIdx += 1;
                    showStep();
                } else {
                    showSummary();
                }
            });
        }
        var subBtn = el('yq-submit-btn');
        if (subBtn) subBtn.addEventListener('click', runGrade);

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

        showStep();
        recordLastVisit();
        fetchClaimAvailability();
        updateClaimButton();
    });
})();
</script>
@endpush
