@extends('earn.layout')

@section('title', 'Kanji Quiz')

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="kanji_quiz"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>

    <p class="muted kanji-back"><a href="/earn">← Earn hub</a></p>

    <section class="kanji-header" aria-labelledby="kanji-page-title">
        <div class="kanji-header__bar">
            <div class="kanji-progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="5" aria-valuenow="0" id="kanji-progress-bar">
                <div id="kanji-progress-fill" class="kanji-progress-fill"></div>
            </div>
            <span id="kanji-q-counter" class="kanji-counter-pill muted">Question 1 of 5</span>
        </div>
        <h1 id="kanji-page-title" class="sr-only">Kanji Quiz</h1>
        <div id="kanji-hero-char" class="kanji-hero-char" aria-hidden="true">水</div>
    </section>

    <div class="kanji-wallet-wrap">
        <label for="claim-wallet" class="kanji-wallet-label muted">Wallet</label>
        <input type="text" id="claim-wallet" class="kanji-wallet-input" autocomplete="off" placeholder="k1…" title="">
    </div>

    <section class="kanji-challenge card" aria-labelledby="kanji-challenge-heading">
        <h2 id="kanji-challenge-heading" class="kanji-section-title">Activity challenge</h2>

        <div id="kanji-hidden-inputs" class="sr-only" aria-hidden="true">
            @foreach ($kanjiQuestions as $i => $q)
                @foreach ($q['options'] as $j => $_opt)
                    <input type="radio" name="quiz-{{ $i }}" value="{{ $j }}" id="quiz-{{ $i }}-opt-{{ $j }}">
                @endforeach
            @endforeach
        </div>

        <div id="kanji-quiz-flow">
            <div id="kanji-step-wrap">
                <div class="kanji-q-card">
                    <span class="kanji-q-card__watermark" id="kanji-watermark" aria-hidden="true">水</span>
                    <p id="kanji-q-text" class="kanji-q-text">What does 水 mean?</p>
                    <div id="kanji-options" class="kanji-options"></div>
                    <button type="button" id="kanji-next-btn" class="kanji-next-btn" disabled>Next →</button>
                </div>
            </div>

            <div id="kanji-summary" class="kanji-summary" hidden>
                <p class="kanji-summary__label muted">Your score</p>
                <p id="kanji-summary-score" class="kanji-summary__score">0/5</p>
                <p class="kanji-summary__hint muted">4/5 or better unlocks claim</p>
                <button type="button" id="kanji-submit-btn" class="kanji-submit-btn">Submit answers</button>
                <p id="kanji-grade-msg" class="muted" style="margin:0.75rem 0 0;font-size:12px;"></p>
            </div>

            <div id="kanji-result-pass" class="kanji-result kanji-result--pass" hidden>
                <div class="kanji-pass-check" aria-hidden="true">
                    <svg width="48" height="48" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <circle cx="12" cy="12" r="10" stroke="#22c55e" stroke-width="1.5" opacity="0.35"/>
                        <path class="kanji-pass-check__path" d="M7 12.5l3 3 7-7" stroke="#22c55e" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" fill="none"/>
                    </svg>
                </div>
                <p class="kanji-result__title">Claim unlocked</p>
                <p class="kanji-result__sub muted">You passed the quiz. Claim when ready.</p>
            </div>

            <div id="kanji-result-fail" class="kanji-result kanji-result--fail" hidden>
                <p class="kanji-result-fail__title">Not quite</p>
                <p class="kanji-result-fail__body muted">Try again tomorrow.</p>
                <p id="kanji-fail-cooldown" class="kanji-result-fail__cooldown muted"></p>
            </div>
        </div>

        <p id="activity-state" class="sr-only" aria-live="polite"></p>
    </section>

    <div id="earn-claim-unavailable" class="kanji-cooldown-card" style="display:none;">
        <div class="kanji-cooldown-card__icon" aria-hidden="true">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
            </svg>
        </div>
        <div>
            <p class="kanji-cooldown-card__title">Claim on cooldown</p>
            <p id="earn-claim-unavailable-msg" class="kanji-cooldown-card__msg muted"></p>
        </div>
    </div>

    <div id="earn-claim-section" class="kanji-claim card">
        <h2 class="kanji-section-title">Claim</h2>
        <p class="muted kanji-claim-slug">Activity <code>kanji_quiz</code></p>
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
        <button type="button" id="claim-btn" class="btn kanji-claim-btn" disabled>Claim {{ $rewardKoto }} KOTO</button>
        <p id="claim-result" class="kanji-claim-result muted"></p>
        <p id="claim-tx-wrap" class="kanji-tx-wrap muted" style="display:none;font-size:12px;"></p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<style>
    .sr-only { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .kanji-back { margin-bottom: 1rem; }
    .kanji-header { margin-bottom: 1.25rem; }
    .kanji-header__bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        margin-bottom: 1rem;
        flex-wrap: wrap;
    }
    .kanji-progress-track {
        flex: 1;
        min-width: 120px;
        height: 3px;
        border-radius: 2px;
        background: #1e2330;
        overflow: hidden;
    }
    .kanji-progress-fill {
        height: 100%;
        width: 0%;
        background: linear-gradient(90deg, #7c6af7, #9d8ff8);
        border-radius: 2px;
        transition: width 0.35s ease;
    }
    .kanji-counter-pill {
        font-size: 11px;
        padding: 0.25rem 0.65rem;
        border-radius: 999px;
        border: 1px solid var(--border);
        background: #111318;
        white-space: nowrap;
    }
    .kanji-hero-char {
        text-align: center;
        font-size: 120px;
        line-height: 1.05;
        font-weight: 600;
        color: #7c6af7;
        font-family: "Hiragino Sans", "Yu Gothic UI", "Meiryo", "Noto Sans JP", "MS Gothic", ui-sans-serif, system-ui, sans-serif;
        margin: 0;
        user-select: none;
    }

    .kanji-wallet-wrap { margin-bottom: 1.25rem; }
    .kanji-wallet-label { display: block; margin-bottom: 0.35rem; font-size: 12px; }
    .kanji-wallet-input {
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

    .kanji-section-title { font-size: 1rem; font-weight: 600; color: #d1d5db; margin: 0 0 1rem; }
    .kanji-challenge { margin-bottom: 1rem; }

    .kanji-q-card {
        position: relative;
        display: flex;
        flex-direction: column;
        background: #111318;
        border-radius: 10px;
        border: 1px solid var(--border);
        border-left: 4px solid #7c6af7;
        padding: 1.25rem 1.25rem 1rem;
        overflow: hidden;
    }
    .kanji-q-card__watermark {
        position: absolute;
        right: 0.5rem;
        bottom: 0.25rem;
        font-size: 8rem;
        line-height: 1;
        color: rgba(124, 106, 247, 0.1);
        font-family: "Hiragino Sans", "Yu Gothic UI", "Meiryo", "Noto Sans JP", sans-serif;
        pointer-events: none;
        user-select: none;
    }
    .kanji-q-text {
        position: relative;
        margin: 0 0 1rem;
        font-size: 16px;
        color: #e5e7eb;
        font-family: inherit;
        max-width: 90%;
    }
    .kanji-options { display: flex; flex-direction: column; gap: 0.5rem; margin-bottom: 1rem; }
    .kanji-opt {
        display: flex;
        align-items: center;
        gap: 0.65rem;
        width: 100%;
        padding: 12px 14px;
        border-radius: 8px;
        border: 1px solid var(--border);
        background: #0d0f14;
        color: var(--text);
        font-family: inherit;
        font-size: 13px;
        text-align: left;
        cursor: pointer;
        transition: border-color 0.15s ease, background 0.15s ease;
    }
    .kanji-opt:hover { border-color: rgba(124, 106, 247, 0.65); }
    .kanji-opt.is-selected {
        background: rgba(124, 106, 247, 0.18);
        border-color: #7c6af7;
    }
    .kanji-opt__check {
        flex-shrink: 0;
        width: 18px;
        height: 18px;
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .kanji-opt.is-selected .kanji-opt__check { opacity: 1; }
    .kanji-next-btn {
        align-self: flex-end;
        padding: 0.5rem 1.1rem;
        border-radius: 8px;
        border: 1px solid rgba(124, 106, 247, 0.5);
        background: transparent;
        color: #c4b5fd;
        font-family: inherit;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
    }
    .kanji-next-btn:hover:not(:disabled) { background: rgba(124, 106, 247, 0.15); color: #fff; }
    .kanji-next-btn:disabled { opacity: 0.4; cursor: not-allowed; }

    .kanji-summary { text-align: center; padding: 0.5rem 0 0; }
    .kanji-summary__label { margin: 0 0 0.35rem; font-size: 12px; }
    .kanji-summary__score {
        margin: 0 0 0.5rem;
        font-size: 2.5rem;
        font-weight: 700;
        color: #f0c040;
        font-family: inherit;
    }
    .kanji-summary__hint { margin: 0 0 1.25rem; font-size: 13px; }
    .kanji-submit-btn {
        padding: 0.65rem 1.5rem;
        border-radius: 8px;
        border: 1px solid #7c6af7;
        background: rgba(124, 106, 247, 0.2);
        color: #e9e5ff;
        font-family: inherit;
        font-size: 14px;
        font-weight: 600;
        cursor: pointer;
    }
    .kanji-submit-btn:hover { background: rgba(124, 106, 247, 0.35); color: #fff; }

    @keyframes kanji-check-draw {
        from { stroke-dashoffset: 24; }
        to { stroke-dashoffset: 0; }
    }
    .kanji-pass-check__path {
        stroke-dasharray: 24;
        stroke-dashoffset: 24;
        animation: kanji-check-draw 0.5s ease forwards 0.15s;
    }
    .kanji-result { text-align: center; padding: 1rem 0; }
    .kanji-result--pass .kanji-pass-check { margin-bottom: 0.5rem; }
    .kanji-result__title { margin: 0; font-size: 1.15rem; font-weight: 600; color: #86efac; }
    .kanji-result__sub { margin: 0.35rem 0 0; font-size: 13px; }

    .kanji-result--fail {
        padding: 1rem 1.1rem;
        border-radius: 10px;
        border: 1px solid rgba(251, 191, 36, 0.4);
        background: rgba(251, 191, 36, 0.08);
        text-align: left;
    }
    .kanji-result-fail__title { margin: 0 0 0.35rem; font-size: 1rem; font-weight: 600; color: #fcd34d; }
    .kanji-result-fail__body { margin: 0; font-size: 13px; }
    .kanji-result-fail__cooldown { margin: 0.5rem 0 0; font-size: 12px; }

    .kanji-cooldown-card {
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
    .kanji-cooldown-card__icon { flex-shrink: 0; opacity: 0.9; margin-top: 0.1rem; }
    .kanji-cooldown-card__title { margin: 0 0 0.25rem; font-size: 14px; font-weight: 600; color: #fcd34d; }
    .kanji-cooldown-card__msg { margin: 0; font-size: 13px; line-height: 1.45; }

    .kanji-claim { margin-top: 0.5rem; }
    .kanji-claim-slug { font-size: 12px; margin: 0 0 0.75rem; }
    .kanji-claim-btn {
        margin-top: 0.85rem;
        background: linear-gradient(135deg, #f0c040 0%, #d4a017 100%);
        color: #141720;
        font-weight: 700;
        border: none;
    }
    .kanji-claim-btn:hover:not(:disabled) { filter: brightness(1.06); color: #141720; }
    .kanji-claim-btn:disabled { opacity: 0.5; }
    .kanji-claim-result { margin: 0.85rem 0 0; min-height: 1.25rem; }
    .kanji-tx-wrap a { color: var(--accent); word-break: break-all; }
</style>
@endpush

@push('scripts')
<script>
(function () {
    var EXPLORER_TX = @json($explorerTxBase);
    var questions = @json($kanjiQuestions);
    var passScore = 4;
    var doneMessage = 'Kanji quiz passed.';
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = 'kanji_quiz';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;
    var currentIdx = 0;

    function el(id) { return document.getElementById(id); }

    function extractKanji(qText) {
        var m = qText && qText.match(/[\u4e00-\u9fff\u3400-\u4dbf]/);
        return m ? m[0] : '\u6f22';
    }

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
        var line = el('kanji-fail-cooldown');
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
        var fill = el('kanji-progress-fill');
        var bar = el('kanji-progress-bar');
        if (fill) fill.style.width = (n / questions.length * 100) + '%';
        if (bar) bar.setAttribute('aria-valuenow', String(n));
    }

    function renderOptions() {
        var q = questions[currentIdx];
        var wrap = el('kanji-options');
        if (!wrap || !q) return;
        wrap.innerHTML = '';
        var selected = document.querySelector('input[name="quiz-' + currentIdx + '"]:checked');
        var selVal = selected ? selected.value : null;
        for (var j = 0; j < q.options.length; j++) {
            (function (jj) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'kanji-opt' + (selVal !== null && String(selVal) === String(jj) ? ' is-selected' : '');
                btn.setAttribute('data-q', String(currentIdx));
                btn.setAttribute('data-j', String(jj));
                var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
                svg.setAttribute('class', 'kanji-opt__check');
                svg.setAttribute('width', '18');
                svg.setAttribute('height', '18');
                svg.setAttribute('viewBox', '0 0 24 24');
                svg.setAttribute('fill', 'none');
                var path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                path.setAttribute('d', 'M20 6L9 17l-5-5');
                path.setAttribute('stroke', '#a78bfa');
                path.setAttribute('stroke-width', '2');
                path.setAttribute('stroke-linecap', 'round');
                svg.appendChild(path);
                btn.appendChild(svg);
                var span = document.createElement('span');
                span.textContent = q.options[jj];
                btn.appendChild(span);
                btn.addEventListener('click', function () {
                    var inp = document.querySelector('input[name="quiz-' + currentIdx + '"][value="' + jj + '"]');
                    if (inp) {
                        inp.checked = true;
                        inp.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    renderOptions();
                    var nextBtn = el('kanji-next-btn');
                    if (nextBtn) nextBtn.disabled = false;
                    updateProgressUI();
                });
                wrap.appendChild(btn);
            })(j);
        }
    }

    function showStep() {
        var stepWrap = el('kanji-step-wrap');
        var summary = el('kanji-summary');
        var passEl = el('kanji-result-pass');
        var failEl = el('kanji-result-fail');
        if (summary) summary.setAttribute('hidden', 'hidden');
        if (passEl) passEl.setAttribute('hidden', 'hidden');
        if (failEl) failEl.setAttribute('hidden', 'hidden');
        if (stepWrap) stepWrap.removeAttribute('hidden');

        var q = questions[currentIdx];
        var hero = el('kanji-hero-char');
        var wm = el('kanji-watermark');
        var qt = el('kanji-q-text');
        var counter = el('kanji-q-counter');
        var nextBtn = el('kanji-next-btn');
        var k = extractKanji(q.q);
        if (hero) hero.textContent = k;
        if (wm) wm.textContent = k;
        if (qt) qt.textContent = q.q;
        if (counter) counter.textContent = 'Question ' + (currentIdx + 1) + ' of ' + questions.length;

        var has = !!document.querySelector('input[name="quiz-' + currentIdx + '"]:checked');
        if (nextBtn) {
            nextBtn.disabled = !has;
            nextBtn.textContent = currentIdx >= questions.length - 1 ? 'Review \u2192' : 'Next \u2192';
        }
        renderOptions();
        updateProgressUI();
    }

    function showSummary() {
        var stepWrap = el('kanji-step-wrap');
        var summary = el('kanji-summary');
        var scoreEl = el('kanji-summary-score');
        if (stepWrap) stepWrap.setAttribute('hidden', 'hidden');
        if (summary) summary.removeAttribute('hidden');
        var sc = computeScore();
        if (scoreEl) scoreEl.textContent = sc + '/' + questions.length;
        var hero = el('kanji-hero-char');
        if (hero) hero.textContent = '\u8a66';
        var counter = el('kanji-q-counter');
        if (counter) counter.textContent = 'Review';
        updateProgressUI();
    }

    function runGrade() {
        var scoreEl = el('kanji-grade-msg');
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
            var summary = el('kanji-summary');
            var passEl = el('kanji-result-pass');
            if (summary) summary.setAttribute('hidden', 'hidden');
            if (passEl) {
                passEl.removeAttribute('hidden');
                var path = passEl.querySelector('.kanji-pass-check__path');
                if (path) {
                    path.style.animation = 'none';
                    void path.offsetWidth;
                    path.style.animation = '';
                }
            }
        } else {
            setActivityDone(false, 'Need ' + passScore + '/' + questions.length + ' to claim. You scored ' + score + '.');
            var summary = el('kanji-summary');
            var failEl = el('kanji-result-fail');
            if (summary) summary.setAttribute('hidden', 'hidden');
            if (failEl) failEl.removeAttribute('hidden');
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
        var nextBtn = el('kanji-next-btn');
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
        var subBtn = el('kanji-submit-btn');
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
