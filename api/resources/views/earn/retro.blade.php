@extends('earn.layout')

@section('title', 'Retro Game Trivia')

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="retro_trivia"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>

    <p class="muted rt-back"><a href="/earn">← Earn hub</a></p>

    <header class="rt-header">
        <div class="rt-header__title-row">
            <span class="rt-header__icon" aria-hidden="true">
                {{-- Lucide gamepad-2 style --}}
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#D85A30" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="6" x2="10" y1="11" y2="11"/>
                    <line x1="8" x2="8" y1="9" y2="13"/>
                    <line x1="15" x2="15.01" y1="12" y2="12"/>
                    <line x1="18" x2="18.01" y1="10" y2="10"/>
                    <rect width="20" height="12" x="2" y="6" rx="2"/>
                </svg>
            </span>
            <h1 class="rt-header__title">Retro Game Trivia</h1>
            <span id="rt-q-pill" class="rt-q-pill">Q 1 / 5</span>
        </div>
        <p class="rt-header__sub muted">Classic JP retro trivia — score 4/5 or better to unlock claim</p>
        <div class="rt-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0" id="rt-progress-bar">
            <div id="rt-progress-fill" class="rt-progress-fill"></div>
        </div>
    </header>

    <div class="rt-wallet-wrap">
        <label for="claim-wallet" class="rt-wallet-label muted">Wallet</label>
        <input type="text" id="claim-wallet" class="rt-wallet-input" autocomplete="off" placeholder="k1…" title="">
    </div>

    <section class="rt-challenge card" aria-labelledby="rt-challenge-heading">
        <h2 id="rt-challenge-heading" class="sr-only">Quiz</h2>

        <div id="rt-pass-banner" class="rt-pass-banner" aria-hidden="true">
            <span class="rt-pass-banner__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#f0c040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                    <path d="M4 22h16"/>
                    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                    <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                </svg>
            </span>
            <p class="rt-pass-banner__text">Insert coin — claim your reward below.</p>
        </div>

        <div id="rt-quiz-root">
            <div id="rt-hidden-inputs" class="sr-only" aria-hidden="true">
                @foreach ($retroQuestions as $i => $q)
                    @foreach ($q['options'] as $j => $_opt)
                        <input type="radio" name="quiz-{{ $i }}" value="{{ $j }}" id="quiz-{{ $i }}-opt-{{ $j }}">
                    @endforeach
                @endforeach
            </div>

            <div id="rt-step-wrap">
                <div class="rt-q-card">
                    <div class="rt-q-card__crt" aria-hidden="true"></div>
                    <span class="rt-q-card__watermark" id="rt-watermark" aria-hidden="true">ファミコン</span>
                    <p id="rt-q-text" class="rt-q-text"></p>
                    <div id="rt-options" class="rt-options"></div>
                    <button type="button" id="rt-next-btn" class="rt-next-btn" disabled>Next →</button>
                </div>
            </div>

            <div id="rt-summary" class="rt-summary" hidden>
                <p class="rt-summary__label muted">Your score</p>
                <p id="rt-summary-score" class="rt-summary__score">0 / 5</p>

                <div id="rt-summary-pass-msg" class="rt-summary-msg rt-summary-msg--pass" hidden>
                    <span class="rt-summary-msg__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#f0c040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
                            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
                            <path d="M4 22h16"/>
                            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
                            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
                            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
                        </svg>
                    </span>
                    <span>Insert coin — reward unlocked</span>
                </div>
                <div id="rt-summary-fail-msg" class="rt-summary-msg rt-summary-msg--fail" hidden>
                    <span class="rt-summary-msg__icon" aria-hidden="true">
                        <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D85A30" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <path d="m15 9-6 6"/>
                            <path d="m9 9 6 6"/>
                        </svg>
                    </span>
                    <span>Game over — try again tomorrow</span>
                </div>

                <button type="button" id="rt-submit-btn" class="rt-submit-btn">Submit answers</button>
                <p id="rt-grade-msg" class="muted" style="margin:0.75rem 0 0;font-size:12px;"></p>
            </div>
        </div>

        <div id="rt-fail-panel" class="rt-fail-panel" hidden>
            <div class="rt-fail-panel__icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#D85A30" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <path d="m15 9-6 6"/>
                    <path d="m9 9 6 6"/>
                </svg>
            </div>
            <div>
                <p class="rt-fail-panel__title">Game over</p>
                <p class="rt-fail-panel__body muted">You need 4/5 — try again tomorrow.</p>
                <p id="rt-fail-cooldown" class="rt-fail-panel__cooldown muted"></p>
            </div>
        </div>

        <p id="activity-state" class="sr-only" aria-live="polite"></p>
    </section>

    <div id="earn-claim-unavailable" class="rt-cooldown-card" style="display:none;">
        <div class="rt-cooldown-card__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </div>
        <div>
            <p class="rt-cooldown-card__title">Claim on cooldown</p>
            <p id="earn-claim-unavailable-msg" class="rt-cooldown-card__msg muted"></p>
        </div>
    </div>

    <div id="earn-claim-section" class="rt-claim card">
        <h2 class="rt-claim-heading">Claim</h2>
        @if (!empty($turnstileSiteKey))
            <div class="cf-turnstile"
                 data-sitekey="{{ $turnstileSiteKey }}"
                 data-callback="onEarnTurnstile"
                 data-expired-callback="onEarnTurnstileExpired"
                 data-error-callback="onEarnTurnstileExpired"></div>
            <p class="muted rt-claim-hint">Verify before claiming.</p>
        @else
            <p class="muted rt-claim-hint">Turnstile not configured.</p>
        @endif
        <button type="button" id="claim-btn" class="rt-claim-btn" disabled>Claim {{ $rewardKoto }} KOTO</button>
        <p id="claim-result" class="rt-claim-result muted"></p>
        <p id="claim-tx-wrap" class="rt-tx-wrap muted" style="display:none;font-size:12px;"></p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .rt-back { margin-bottom: 1rem; }
    .rt-header { margin-bottom: 1.25rem; }
    .rt-header__title-row {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.65rem 1rem;
        margin-bottom: 0.5rem;
    }
    .rt-header__icon { display: flex; flex-shrink: 0; filter: drop-shadow(0 0 12px rgba(216, 90, 48, 0.35)); }
    .rt-header__title {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        flex: 1;
        min-width: 0;
        color: #fde8dc;
        text-shadow: 0 0 20px rgba(216, 90, 48, 0.35);
    }
    .rt-q-pill {
        font-size: 11px;
        font-family: inherit;
        padding: 0.3rem 0.65rem;
        border-radius: 999px;
        border: 1px solid rgba(216, 90, 48, 0.55);
        background: rgba(216, 90, 48, 0.12);
        color: #fbbf8a;
        white-space: nowrap;
    }
    .rt-header__sub { margin: 0 0 0.75rem; font-size: 13px; max-width: 38rem; line-height: 1.45; }
    .rt-progress-track {
        height: 3px;
        border-radius: 2px;
        background: #1e2030;
        overflow: hidden;
        box-shadow: inset 0 1px 2px rgba(0,0,0,0.5);
    }
    .rt-progress-fill {
        height: 100%;
        width: 0%;
        border-radius: 2px;
        background: #D85A30;
        transition: width 0.35s ease;
    }

    .rt-wallet-wrap { margin-bottom: 1.25rem; }
    .rt-wallet-label { display: block; margin-bottom: 0.35rem; font-size: 12px; }
    .rt-wallet-input {
        width: 100%;
        max-width: 100%;
        padding: 0.5rem 1rem;
        border-radius: 999px;
        border: 1px solid #2a2118;
        background: #111318;
        color: var(--text);
        font-family: inherit;
        font-size: 13px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .rt-challenge {
        margin-bottom: 1rem;
        overflow: hidden;
        border-color: #1e2330;
        background: var(--card);
        box-shadow: inset 0 0 0 1px rgba(216, 90, 48, 0.06), 0 12px 40px rgba(0, 0, 0, 0.35);
    }

    .rt-pass-banner {
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
        background: linear-gradient(135deg, rgba(216, 90, 48, 0.22) 0%, rgba(40, 22, 14, 0.95) 100%);
        color: #fed7aa;
        transition: opacity 0.45s ease, max-height 0.5s ease, margin 0.45s ease, padding 0.45s ease, border-color 0.45s ease;
    }
    .rt-pass-banner.is-visible {
        max-height: 5rem;
        opacity: 1;
        margin-bottom: 1rem;
        padding: 1rem 1.1rem;
        border-color: rgba(232, 148, 58, 0.45);
    }
    .rt-pass-banner__icon { flex-shrink: 0; display: flex; align-items: center; }
    .rt-pass-banner__text { margin: 0; font-size: 15px; font-weight: 700; color: #fff7ed; letter-spacing: 0.02em; }

    .rt-q-card {
        position: relative;
        padding: 1.5rem;
        overflow: hidden;
        border-radius: 8px;
        border: 1px solid #1e2030;
        border-left: 3px solid #D85A30;
        background: #111318;
        z-index: 0;
    }
    .rt-q-card__crt {
        position: absolute;
        inset: 0;
        pointer-events: none;
        z-index: 1;
        border-radius: 8px;
        background: repeating-linear-gradient(
            0deg,
            transparent,
            transparent 2px,
            rgba(0, 0, 0, 0.11) 2px,
            rgba(0, 0, 0, 0.11) 4px
        );
        opacity: 0.4;
    }
    .rt-q-card__watermark {
        position: absolute;
        right: 0.15rem;
        bottom: 0;
        font-size: 80px;
        line-height: 1;
        font-weight: 700;
        color: rgba(216, 90, 48, 0.05);
        font-family: 'Noto Sans JP', 'Hiragino Sans', 'Yu Gothic UI', 'Meiryo', ui-sans-serif, sans-serif;
        letter-spacing: -0.02em;
        pointer-events: none;
        user-select: none;
        z-index: 0;
    }
    .rt-q-text {
        position: relative;
        margin: 0 0 1.1rem;
        font-size: 15px;
        color: #fff;
        font-family: inherit;
        line-height: 1.45;
        max-width: 100%;
        z-index: 2;
    }
    .rt-options { position: relative; z-index: 2; margin-bottom: 1rem; }
    .rt-opt {
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
    .rt-opt:hover {
        border-color: rgba(216, 90, 48, 0.5);
        color: #fff;
    }
    .rt-opt.is-selected {
        border-color: #D85A30;
        background: #1a0e0a;
        color: #fff;
    }
    .rt-opt__dot {
        flex-shrink: 0;
        width: 16px;
        height: 16px;
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .rt-opt.is-selected .rt-opt__dot { opacity: 1; }
    .rt-opt__label { flex: 1; min-width: 0; }
    .rt-next-btn {
        position: relative;
        z-index: 2;
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        padding: 0.6rem 1rem;
        border-radius: 6px;
        border: 1px solid #D85A30;
        background: #2a1810;
        color: #a89a90;
        font-family: inherit;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        letter-spacing: 0.04em;
        transition: background 0.15s ease, color 0.15s ease, opacity 0.15s ease;
    }
    .rt-next-btn:not(:disabled) {
        background: #D85A30;
        color: #fff;
        border-color: #D85A30;
    }
    .rt-next-btn:hover:not(:disabled) { filter: brightness(1.06); }
    .rt-next-btn:disabled { opacity: 0.45; cursor: not-allowed; }

    .rt-summary { text-align: center; padding: 0.75rem 0 0; }
    .rt-summary__label { margin: 0 0 0.35rem; font-size: 12px; }
    .rt-summary__score {
        margin: 0 0 1rem;
        font-size: 48px;
        font-weight: 700;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        line-height: 1.1;
    }
    .rt-summary__score--pass { color: #f0c040; text-shadow: 0 0 20px rgba(240, 192, 64, 0.25); }
    .rt-summary__score--fail { color: #D85A30; text-shadow: none; }
    .rt-summary-msg {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin: 0 0 1.25rem;
        font-size: 14px;
        font-weight: 600;
    }
    .rt-summary-msg--pass { color: #fde68a; }
    .rt-summary-msg--fail { color: #fca5a5; }
    .rt-summary-msg__icon { display: flex; flex-shrink: 0; }
    .rt-submit-btn {
        width: 100%;
        padding: 0.65rem 1rem;
        border-radius: 8px;
        border: 1px solid rgba(216, 90, 48, 0.6);
        background: rgba(216, 90, 48, 0.18);
        color: #fff7ed;
        font-family: inherit;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        letter-spacing: 0.03em;
    }
    .rt-submit-btn:hover { background: rgba(232, 148, 58, 0.28); }

    .rt-fail-panel {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 1rem 1.15rem;
        margin-top: 0.5rem;
        border-radius: 10px;
        border: 1px solid rgba(245, 158, 11, 0.45);
        background: rgba(60, 28, 8, 0.45);
    }
    .rt-fail-panel__icon { flex-shrink: 0; margin-top: 0.1rem; }
    .rt-fail-panel__title { margin: 0 0 0.35rem; font-size: 1rem; font-weight: 800; color: #fbbf24; letter-spacing: 0.12em; }
    .rt-fail-panel__body { margin: 0; font-size: 13px; }
    .rt-fail-panel__cooldown { margin: 0.5rem 0 0; font-size: 12px; }

    .rt-cooldown-card {
        display: flex;
        gap: 0.85rem;
        align-items: flex-start;
        padding: 1rem 1.15rem;
        margin-bottom: 1rem;
        border-radius: 10px;
        border: 1px solid rgba(245, 158, 11, 0.35);
        background: rgba(60, 32, 12, 0.35);
        color: #fde68a;
    }
    .rt-cooldown-card__icon { flex-shrink: 0; opacity: 0.9; margin-top: 0.1rem; }
    .rt-cooldown-card__title { margin: 0 0 0.25rem; font-size: 14px; font-weight: 600; color: #fbbf24; }
    .rt-cooldown-card__msg { margin: 0; font-size: 13px; line-height: 1.45; }

    .rt-claim { margin-top: 0.5rem; border-color: #2a2118; transition: border-color 0.35s ease, box-shadow 0.35s ease, background 0.35s ease; }
    .rt-claim-heading { font-size: 1rem; font-weight: 600; color: #e7e5e4; margin: 0 0 1rem; }
    .rt-claim-hint { margin: 0.6rem 0 0; font-size: 12px; }
    .rt-claim--ready {
        border-color: rgba(216, 90, 48, 0.5);
        box-shadow: 0 0 0 1px rgba(232, 148, 58, 0.12), 0 0 28px rgba(216, 90, 48, 0.12);
        background: rgba(40, 22, 12, 0.4);
    }
    .rt-claim-btn {
        display: block;
        width: 100%;
        margin-top: 0.85rem;
        padding: 0.7rem 1rem;
        border-radius: 8px;
        border: none;
        background: #D85A30;
        color: #fff8f0;
        font-family: ui-monospace, 'JetBrains Mono', monospace;
        font-size: 14px;
        font-weight: 700;
        cursor: pointer;
        letter-spacing: 0.03em;
        transition: filter 0.15s ease, opacity 0.15s ease;
        box-shadow: 0 4px 16px rgba(216, 90, 48, 0.3);
    }
    .rt-claim-btn:hover:not(:disabled) { filter: brightness(1.07); }
    .rt-claim-btn:disabled { opacity: 0.45; cursor: not-allowed; box-shadow: none; }
    .rt-claim-result { margin: 0.85rem 0 0; min-height: 1.25rem; }
    .rt-tx-wrap a { color: #fb923c; word-break: break-all; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var EXPLORER_TX = @json($explorerTxBase);
    var questions = @json($retroQuestions);
    var watermarks = @json($retroWatermarks);
    var passScore = 4;
    var doneMessage = 'Retro trivia passed.';
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = 'retro_trivia';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;
    var currentIdx = 0;

    var DOT_SVG = '<svg class="rt-opt__dot" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#D85A30" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="3" fill="#e8943a" stroke="none"/></svg>';

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
        var line = el('rt-fail-cooldown');
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
            if (done) claimSec.classList.add('rt-claim--ready');
            else claimSec.classList.remove('rt-claim--ready');
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
        var fill = el('rt-progress-fill');
        var bar = el('rt-progress-bar');
        if (fill) fill.style.width = (n / questions.length * 100) + '%';
        if (bar) bar.setAttribute('aria-valuenow', String(n));
    }

    function renderOptions() {
        var q = questions[currentIdx];
        var wrap = el('rt-options');
        if (!wrap || !q) return;
        wrap.innerHTML = '';
        var selected = document.querySelector('input[name="quiz-' + currentIdx + '"]:checked');
        var selVal = selected ? selected.value : null;
        for (var j = 0; j < q.options.length; j++) {
            (function (jj) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'rt-opt' + (selVal !== null && String(selVal) === String(jj) ? ' is-selected' : '');
                btn.innerHTML = DOT_SVG + '<span class="rt-opt__label"></span>';
                btn.querySelector('.rt-opt__label').textContent = q.options[jj];
                btn.addEventListener('click', function () {
                    var inp = document.querySelector('input[name="quiz-' + currentIdx + '"][value="' + jj + '"]');
                    if (inp) {
                        inp.checked = true;
                        inp.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    renderOptions();
                    var nextBtn = el('rt-next-btn');
                    if (nextBtn) nextBtn.disabled = false;
                    updateProgressUI();
                });
                wrap.appendChild(btn);
            })(j);
        }
    }

    function showStep() {
        var stepWrap = el('rt-step-wrap');
        var summary = el('rt-summary');
        if (summary) summary.setAttribute('hidden', 'hidden');
        if (stepWrap) stepWrap.removeAttribute('hidden');

        var q = questions[currentIdx];
        var wm = el('rt-watermark');
        var qt = el('rt-q-text');
        var pill = el('rt-q-pill');
        var nextBtn = el('rt-next-btn');
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
        var stepWrap = el('rt-step-wrap');
        var summary = el('rt-summary');
        var scoreEl = el('rt-summary-score');
        var passMsg = el('rt-summary-pass-msg');
        var failMsg = el('rt-summary-fail-msg');
        if (stepWrap) stepWrap.setAttribute('hidden', 'hidden');
        if (summary) summary.removeAttribute('hidden');
        var sc = computeScore();
        if (scoreEl) {
            scoreEl.textContent = sc + ' / ' + questions.length;
            scoreEl.classList.remove('rt-summary__score--pass', 'rt-summary__score--fail');
            if (sc >= passScore) scoreEl.classList.add('rt-summary__score--pass');
            else scoreEl.classList.add('rt-summary__score--fail');
        }
        if (passMsg && failMsg) {
            if (sc >= passScore) {
                passMsg.removeAttribute('hidden');
                failMsg.setAttribute('hidden', 'hidden');
            } else {
                passMsg.setAttribute('hidden', 'hidden');
                failMsg.removeAttribute('hidden');
            }
        }
        var pill = el('rt-q-pill');
        if (pill) pill.textContent = 'Review';
        updateProgressUI();
    }

    function runGrade() {
        var scoreEl = el('rt-grade-msg');
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
            var root = el('rt-quiz-root');
            var banner = el('rt-pass-banner');
            if (root) root.setAttribute('hidden', 'hidden');
            if (banner) {
                banner.classList.add('is-visible');
                banner.setAttribute('aria-hidden', 'false');
            }
        } else {
            setActivityDone(false, 'Need ' + passScore + '/' + questions.length + ' to claim. You scored ' + score + '.');
            var root = el('rt-quiz-root');
            var failPanel = el('rt-fail-panel');
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
        var nextBtn = el('rt-next-btn');
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
        var subBtn = el('rt-submit-btn');
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
