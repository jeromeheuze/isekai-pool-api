@extends('earn.layout')

@section('title', $title)

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="{{ $slug }}"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>
    <p class="muted" style="margin-bottom:1rem;"><a href="/earn">← Earn hub</a></p>
    @if ($slug !== 'map_explore')
        <h1>{{ $title }}</h1>
        <p class="muted" style="max-width:40rem;">{{ $intro }}</p>
    @endif

    <div class="card" style="margin-top:1.25rem;">
        <p style="margin:0 0 0.6rem;"><strong style="color:#fff;">Wallet (saved in browser)</strong></p>
        <input id="claim-wallet" type="text" autocomplete="off" placeholder="k1…"
            style="width:100%;max-width:100%;padding:0.5rem 0.75rem;border-radius:6px;border:1px solid var(--border);background:#0d0f14;color:var(--text);font-family:inherit;font-size:13px;">
        <p class="muted" style="margin:0.6rem 0 0;font-size:12px;">This wallet is used only for faucet eligibility and claim submission.</p>
    </div>

    <div class="card">
        <p style="margin:0 0 0.75rem;"><strong style="color:#fff;">{{ $slug === 'map_explore' ? 'Pilgrimage path' : 'Activity challenge' }}</strong></p>
        <div id="activity-container"></div>
        <p id="activity-state" class="muted" style="margin:0.75rem 0 0;font-size:12px;">Complete this activity to unlock claim.</p>
    </div>

    <div id="earn-claim-unavailable" class="card" style="display:none;border-color:rgba(248,113,113,0.35);background:rgba(248,113,113,0.06);">
        <p style="margin:0;"><strong style="color:#fca5a5;">Claim not available for this wallet yet</strong></p>
        <p id="earn-claim-unavailable-msg" class="muted" style="margin:0.5rem 0 0;font-size:12px;"></p>
        <p class="muted" style="margin:0.6rem 0 0;font-size:11px;">You can still practice the activity. Claim unlocks after the cooldown from your last claim.</p>
    </div>

    <div id="earn-claim-section" class="card">
        <p style="margin:0 0 0.75rem;"><strong style="color:#fff;">Claim</strong></p>
        @if ($slug !== 'map_explore')
            <p style="margin:0 0 0.75rem;"><strong style="color:#fff;">Activity slug</strong> <code class="muted">{{ $slug }}</code></p>
        @endif

        @if (!empty($turnstileSiteKey))
            <div class="cf-turnstile"
                 data-sitekey="{{ $turnstileSiteKey }}"
                 data-callback="onEarnTurnstile"
                 data-expired-callback="onEarnTurnstileExpired"
                 data-error-callback="onEarnTurnstileExpired"></div>
            <p class="muted" style="margin:0.6rem 0 0;font-size:12px;">Complete Turnstile verification before claiming.</p>
        @else
            <p class="muted" style="margin:0 0 0.6rem;font-size:12px;">Turnstile site key is not configured on this environment.</p>
        @endif

        <button id="claim-btn" class="btn{{ $slug === 'map_explore' ? ' btn-map-claim' : '' }}" disabled style="margin-top:0.8rem;{{ $slug === 'map_explore' ? ' width:100%;' : '' }}">{{ $slug === 'map_explore' ? 'Claim 1 KOTO' : 'Claim reward' }}</button>
        <p id="claim-result" class="muted" style="margin:0.8rem 0 0;font-size:12px;">Not ready.</p>
        @if ($slug !== 'map_explore')
            <p class="muted" style="margin:0.5rem 0 0;font-size:12px;">
                Flow: <code>POST {{ $apiBase }}/faucet/activity-complete</code> then <code>POST {{ $apiBase }}/faucet/claim</code>
            </p>
        @endif
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@if ($slug === 'map_explore')
    <style>
        .map-explore-header {
            margin-bottom: 1.25rem;
        }
        .map-explore-header__title-row {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin: 0 0 0.5rem;
        }
        .map-explore-header__title-row h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: #fff;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
        }
        .map-explore-header__sub {
            margin: 0 0 1rem;
            font-size: 13px;
            color: var(--muted);
            max-width: 36rem;
            line-height: 1.45;
        }
        .map-explore-trail {
            display: flex;
            align-items: center;
            flex-wrap: wrap;
            gap: 0.35rem 0.25rem;
            font-size: 14px;
            line-height: 1;
        }
        .map-explore-trail__arrow {
            color: #3d4354;
            font-size: 12px;
            user-select: none;
        }
        .map-explore-trail__dot {
            display: inline-flex;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #1e2030;
            border: 1px solid #2a2f42;
            transition: background 0.35s ease, border-color 0.35s ease, box-shadow 0.35s ease;
        }
        .map-explore-trail__dot--amber {
            background: #f0c040;
            border-color: #e6b020;
            box-shadow: 0 0 10px rgba(240, 192, 64, 0.35);
        }
        .map-journey {
            position: relative;
            display: flex;
            flex-direction: column;
            gap: 0;
            margin-top: 0.25rem;
        }
        .map-journey__line-wrap {
            position: absolute;
            left: 27px;
            top: 40px;
            bottom: 40px;
            width: 2px;
            pointer-events: none;
            z-index: 0;
        }
        .map-journey__line-bg {
            position: absolute;
            inset: 0;
            border-radius: 1px;
            background: repeating-linear-gradient(
                to bottom,
                #1e2030 0,
                #1e2030 5px,
                transparent 5px,
                transparent 9px
            );
            opacity: 0.95;
        }
        .map-journey__line-fill {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 0%;
            background: linear-gradient(180deg, #f5d060 0%, #f0c040 55%, #c9a030 100%);
            border-radius: 1px;
            transition: height 0.55s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 0 12px rgba(240, 192, 64, 0.25);
        }
        .map-node-row {
            display: flex;
            align-items: flex-start;
            gap: 16px;
            padding: 16px;
            position: relative;
            z-index: 1;
        }
        .map-node-row:has(.map-node-card--active) {
            cursor: pointer;
        }
        .map-node-circle {
            flex-shrink: 0;
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: 600;
            border: 2px solid #1e2030;
            background: #0d0f14;
            color: #5c6478;
            transition: border-color 0.25s ease, background 0.25s ease, color 0.25s ease;
        }
        .map-node-circle--active {
            border-color: #f0c040;
            color: #f0c040;
            background: rgba(240, 192, 64, 0.08);
            animation: map-node-pulse 1.8s ease-in-out infinite;
        }
        .map-node-circle--visited {
            border-color: #f0c040;
            background: #f0c040;
            color: #0d0f14;
            animation: none;
        }
        .map-node-circle__num {
            display: inline;
        }
        .map-node-circle--visited .map-node-circle__num {
            display: none;
        }
        .map-node-circle__check {
            display: none;
            width: 14px;
            height: 14px;
            stroke: #fff;
            fill: none;
            stroke-width: 2.5;
            stroke-linecap: round;
            stroke-linejoin: round;
        }
        .map-node-circle--visited .map-node-circle__check {
            display: block;
        }
        @keyframes map-node-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(240, 192, 64, 0.45); }
            50% { box-shadow: 0 0 0 6px rgba(240, 192, 64, 0); }
        }
        .map-node-card {
            flex: 1;
            min-width: 0;
            background: #111318;
            border: 1px solid #1e2030;
            border-radius: 8px;
            padding: 12px 16px;
            text-align: left;
            cursor: default;
            transition: border-color 0.2s ease, background 0.2s ease;
        }
        .map-node-card--active {
            cursor: pointer;
        }
        .map-node-card--active:hover {
            border-color: rgba(240, 192, 64, 0.45);
            background: #14161c;
        }
        .map-node-card__title {
            margin: 0 0 0.35rem;
            font-size: 14px;
            font-weight: 600;
            color: #e5e7eb;
        }
        .map-node-card__desc {
            margin: 0 0 0.5rem;
            font-size: 12px;
            color: #6b7280;
            line-height: 1.4;
        }
        .map-node-card__tag {
            display: inline-block;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
        }
        .map-node-card__tag--start {
            background: rgba(240, 192, 64, 0.15);
            color: #f0c040;
        }
        .map-node-card__tag--locked {
            background: #1a1d26;
            color: #5c6478;
        }
        .map-node-card__tag--done {
            background: rgba(45, 212, 191, 0.12);
            color: #2dd4bf;
        }
        .map-node-card--shake {
            animation: map-card-error 0.5s ease;
        }
        @keyframes map-card-error {
            0%, 100% { border-color: #1e2030; background: #111318; }
            15%, 45% { border-color: rgba(248, 113, 113, 0.75); background: rgba(248, 113, 113, 0.08); }
        }
        .map-explore-banner {
            display: none;
            width: 100%;
            margin-top: 1rem;
            padding: 1rem 1.15rem;
            border-radius: 8px;
            background: linear-gradient(135deg, rgba(240, 192, 64, 0.18) 0%, rgba(240, 192, 64, 0.08) 100%);
            border: 1px solid rgba(240, 192, 64, 0.45);
            align-items: center;
            gap: 0.75rem;
            animation: map-banner-in 0.7s ease-out;
        }
        .map-explore-banner--visible {
            display: flex;
            animation: map-banner-in 0.7s ease-out, map-banner-glow 3.5s ease-in-out infinite 0.8s;
        }
        @keyframes map-banner-in {
            from { opacity: 0; transform: translateY(-6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes map-banner-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(240, 192, 64, 0.15); }
            50% { box-shadow: 0 0 28px rgba(240, 192, 64, 0.28); }
        }
        .map-explore-banner__text {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            color: #fde68a;
        }
        .map-explore-banner__icon {
            flex-shrink: 0;
        }
        .map-explore-warn {
            min-height: 1.25rem;
            margin: 0.5rem 0 0;
            font-size: 12px;
            color: #fca5a5;
        }
        .btn-map-claim {
            width: 100%;
            background: #f0c040 !important;
            color: #0d0f14 !important;
            font-family: 'JetBrains Mono', ui-monospace, monospace !important;
            font-weight: 600;
            border: none;
        }
        .btn-map-claim:hover:not(:disabled) {
            filter: brightness(1.06);
            color: #0d0f14 !important;
        }
        .btn-map-claim:disabled {
            opacity: 0.45;
            cursor: not-allowed;
        }
    </style>
@endif
@endpush

@push('scripts')
<script>
(function () {
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = cfg ? (cfg.getAttribute('data-slug') || '') : '';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';
    var proofPayload = null;
    var LAST_USED_KEY = 'isekai_earn_last_used_v1';
    var claimAllowedFromApi = true;

    function readLastUsedMap() {
        try {
            var raw = localStorage.getItem(LAST_USED_KEY);
            var o = raw ? JSON.parse(raw) : {};
            return o && typeof o === 'object' ? o : {};
        } catch (e) {
            return {};
        }
    }

    function recordLastVisit() {
        var w = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        if (!w || w.length < 20 || !slug) return;
        try {
            var m = readLastUsedMap();
            if (!m[w]) m[w] = {};
            m[w][slug] = new Date().toISOString();
            localStorage.setItem(LAST_USED_KEY, JSON.stringify(m));
        } catch (e) {}
    }

    function formatWhen(iso) {
        if (!iso) return '—';
        try {
            return new Date(iso).toLocaleString();
        } catch (e) {
            return String(iso);
        }
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
                    if (acts[i].slug === slug) {
                        mine = acts[i];
                        break;
                    }
                }
                if (mine && mine.available === false) {
                    claimAllowedFromApi = false;
                    section.style.display = 'none';
                    if (blocked) {
                        blocked.style.display = '';
                        if (blockedMsg) {
                            blockedMsg.textContent = mine.next_claim_at
                                ? ('Next eligible claim: ' + formatWhen(mine.next_claim_at) + ' (local time).')
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

    var coffeeQuestions = [
        { q: 'Which brew method uses paper filter and gravity?', options: ['Pour-over', 'Espresso', 'Cold brew'], answer: 0 },
        { q: 'Espresso extraction usually takes about...', options: ['5 seconds', '25-35 seconds', '2 minutes'], answer: 1 },
        { q: 'Arabica generally has what vs Robusta?', options: ['More caffeine', 'Less caffeine', 'Same caffeine always'], answer: 1 },
        { q: 'A common latte ratio is...', options: ['Mostly milk + espresso', 'Only espresso', 'Only foam'], answer: 0 },
        { q: 'Fresh coffee flavor is best preserved by...', options: ['Open bowl storage', 'Airtight container', 'Warm sunlight shelf'], answer: 1 }
    ];

    function el(id) { return document.getElementById(id); }

    function setActivityDone(done, message, proof) {
        activityDone = !!done;
        if (done && proof) {
            proofPayload = proof;
        }
        if (!done) {
            proofPayload = null;
        }
        var state = el('activity-state');
        if (state) {
            state.textContent = message || (done ? 'Activity complete. Claim unlocked pending Turnstile.' : 'Complete this activity to unlock claim.');
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

    function renderQuiz(questions, passScore, doneMessage) {
        var host = el('activity-container');
        if (!host) return;
        var html = '';
        for (var i = 0; i < questions.length; i++) {
            var q = questions[i];
            html += '<div style="margin-bottom:0.9rem;">';
            html += '<p style="margin:0 0 0.35rem;color:#fff;">' + (i + 1) + '. ' + q.q + '</p>';
            for (var j = 0; j < q.options.length; j++) {
                html += '<label class="muted" style="display:block;font-size:12px;margin:0.2rem 0;">';
                html += '<input type="radio" name="quiz-' + i + '" value="' + j + '"> ' + q.options[j];
                html += '</label>';
            }
            html += '</div>';
        }
        html += '<button id="grade-quiz" class="btn btn-ghost">Grade quiz</button>';
        html += '<p id="quiz-score" class="muted" style="margin:0.7rem 0 0;font-size:12px;">Not graded.</p>';
        host.innerHTML = html;

        var gradeBtn = el('grade-quiz');
        var scoreEl = el('quiz-score');
        if (!gradeBtn || !scoreEl) return;
        gradeBtn.addEventListener('click', function () {
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
                scoreEl.textContent = 'Please answer all questions.';
                setActivityDone(false, 'Answer every question before grading.');
                return;
            }
            scoreEl.textContent = 'Score: ' + score + '/' + questions.length;
            if (score >= passScore) {
                var ans = [];
                for (var ai = 0; ai < questions.length; ai++) {
                    var chk = document.querySelector('input[name="quiz-' + ai + '"]:checked');
                    ans.push(chk ? parseInt(chk.value, 10) : -1);
                }
                setActivityDone(true, doneMessage + ' Score: ' + score + '/' + questions.length + '.', { answers: ans });
            } else {
                setActivityDone(false, 'Need ' + passScore + '/' + questions.length + ' to claim. You scored ' + score + '.');
            }
        });
    }

    function renderMapExplore() {
        var host = el('activity-container');
        if (!host) return;

        var CP = [
            { n: 1, title: 'Shrine Gate — 鳥居', desc: 'The path begins beneath towering vermillion.' },
            { n: 2, title: 'Stone Lanterns — 灯籠', desc: 'Soft light guides each quiet step.' },
            { n: 3, title: 'Main Hall — 本殿', desc: 'Offer your respects before the inner sanctuary.' },
            { n: 4, title: 'Sacred Tree — 御神木', desc: 'Ancient cedar roots bind heaven and earth.' }
        ];

        var ICON_PIN = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z" stroke="#f0c040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="10" r="3" stroke="#f0c040" stroke-width="2"/></svg>';
        var ICON_FLAG = '<svg class="map-explore-banner__icon" xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" stroke="#f0c040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M4 22v-7" stroke="#f0c040" stroke-width="2" stroke-linecap="round"/></svg>';
        var ICON_CHECK = '<svg class="map-node-circle__check" viewBox="0 0 24 24" aria-hidden="true"><path d="M20 6 9 17l-5-5" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';

        var html = '';
        html += '<div class="map-explore-header">';
        html += '<div class="map-explore-header__title-row">' + ICON_PIN + '<h2>Map Explore</h2></div>';
        html += '<p class="map-explore-header__sub">Follow the pilgrimage path — visit all checkpoints in order</p>';
        html += '<div class="map-explore-trail" id="map-explore-trail" role="img" aria-label="Progress"></div>';
        html += '</div>';

        html += '<div class="map-journey" id="map-journey-root">';
        html += '<div class="map-journey__line-wrap"><div class="map-journey__line-bg"></div><div class="map-journey__line-fill" id="map-journey-line-fill"></div></div>';

        for (var i = 0; i < CP.length; i++) {
            var c = CP[i];
            html += '<div class="map-node-row" data-cp="' + c.n + '">';
            html += '<div class="map-node-circle" id="map-circle-' + c.n + '" data-cp="' + c.n + '"><span class="map-node-circle__num">' + c.n + '</span>' + ICON_CHECK + '</div>';
            html += '<div class="map-node-card" id="map-card-' + c.n + '" data-cp="' + c.n + '" tabindex="-1">';
            html += '<p class="map-node-card__title">' + c.title + '</p>';
            html += '<p class="map-node-card__desc">' + c.desc + '</p>';
            html += '<span class="map-node-card__tag" id="map-tag-' + c.n + '"></span>';
            html += '</div></div>';
        }
        html += '</div>';

        html += '<div class="map-explore-banner" id="map-explore-complete-banner" hidden>';
        html += ICON_FLAG;
        html += '<p class="map-explore-banner__text">Pilgrimage complete — claim your reward</p>';
        html += '</div>';
        html += '<p class="map-explore-warn" id="map-explore-warn" aria-live="polite"></p>';

        host.innerHTML = html;

        var next = 1;
        var lineFill = el('map-journey-line-fill');
        var trailEl = el('map-explore-trail');
        var warnEl = el('map-explore-warn');
        var bannerEl = el('map-explore-complete-banner');

        function trailHtml() {
            var bits = [];
            for (var t = 1; t <= 4; t++) {
                if (t > 1) bits.push('<span class="map-explore-trail__arrow" aria-hidden="true">→</span>');
                var done = next > t;
                bits.push('<span class="map-explore-trail__dot' + (done ? ' map-explore-trail__dot--amber' : '') + '" title="Checkpoint ' + t + '"></span>');
            }
            return bits.join('');
        }

        function setLineFill() {
            if (!lineFill) return;
            var v = next - 1;
            var pct = Math.min(100, (Math.min(Math.max(v, 0), 4) / 4) * 100);
            lineFill.style.height = pct + '%';
        }

        function syncUi() {
            if (trailEl) trailEl.innerHTML = trailHtml();
            setLineFill();
            for (var j = 0; j < CP.length; j++) {
                var n = CP[j].n;
                var circle = el('map-circle-' + n);
                var card = el('map-card-' + n);
                var tag = el('map-tag-' + n);
                if (!circle || !card || !tag) continue;

                circle.classList.remove('map-node-circle--active', 'map-node-circle--visited');
                card.classList.remove('map-node-card--active', 'map-node-card--shake');
                if (n < next) {
                    circle.classList.add('map-node-circle--visited');
                } else if (n === next) {
                    circle.classList.add('map-node-circle--active');
                    card.classList.add('map-node-card--active');
                }

                tag.className = 'map-node-card__tag';
                if (n < next) {
                    tag.className += ' map-node-card__tag--done';
                    tag.textContent = 'Visited ✓';
                } else if (n === next) {
                    tag.className += ' map-node-card__tag--start';
                    tag.textContent = n === 1 ? 'Start here' : 'Next';
                } else {
                    tag.className += ' map-node-card__tag--locked';
                    tag.textContent = 'Locked';
                }
            }
        }

        function flashError(n) {
            var card = el('map-card-' + n);
            if (!card) return;
            card.classList.remove('map-node-card--shake');
            void card.offsetWidth;
            card.classList.add('map-node-card--shake');
        }

        var journeyRoot = el('map-journey-root');
        var activityLine = el('activity-state');
        if (journeyRoot) {
            journeyRoot.addEventListener('click', function (ev) {
                var row = ev.target.closest('.map-node-row');
                if (!row) return;
                var n = parseInt(row.getAttribute('data-cp'), 10);
                if (!n) return;
                if (warnEl) warnEl.textContent = '';
                if (n < next) return;
                if (n !== next) {
                    flashError(n);
                    if (warnEl) warnEl.textContent = 'Follow the path in order';
                    return;
                }
                next += 1;
                syncUi();
                if (next === 5) {
                    if (bannerEl) {
                        bannerEl.removeAttribute('hidden');
                        bannerEl.classList.add('map-explore-banner--visible');
                    }
                    setActivityDone(true, 'Pilgrimage complete. You can claim now.', { sequence: [1, 2, 3, 4] });
                } else if (activityLine) {
                    activityLine.textContent = 'Next stop: checkpoint ' + next + '.';
                }
            });
        }

        syncUi();
        if (activityLine) activityLine.textContent = 'Begin at the shrine gate — tap the active stop when you are ready.';
    }

    function renderDailyBonus() {
        var host = el('activity-container');
        if (!host) return;
        host.innerHTML =
            '<p class="muted" style="margin:0 0 0.6rem;font-size:13px;">Click to check in for today. Faucet cooldown/caps still apply server-side.</p>' +
            '<button id="daily-check-in" class="btn btn-ghost">Check in</button>' +
            '<p id="daily-state" class="muted" style="margin:0.7rem 0 0;font-size:12px;">Not checked in.</p>';
        var btn = el('daily-check-in');
        var state = el('daily-state');
        if (!btn || !state) return;
        btn.addEventListener('click', function () {
            btn.disabled = true;
            state.textContent = 'Starting check-in...';
            fetch(API + '/faucet/activity-session', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ activity_slug: 'daily_bonus' })
            }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (res) {
                    if (!res.ok || !res.data.session_id) {
                        state.textContent = 'Could not start check-in.';
                        btn.disabled = false;
                        return;
                    }
                    var sessionId = res.data.session_id;
                    setTimeout(function () {
                        state.textContent = 'Checked in.';
                        setActivityDone(true, 'Daily bonus unlocked. You can claim now.', { session_id: sessionId });
                    }, 1100);
                }).catch(function () {
                    state.textContent = 'Network error.';
                    btn.disabled = false;
                });
        });
    }

    function renderActivity() {
        if (slug === 'shrine_visit') {
            var host = el('activity-container');
            if (host) {
                host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">Daily Shrine Visit uses a dedicated page. <a href="/earn/shrine">Open shrine visit</a>.</p>';
            }
            setActivityDone(false, 'Open the shrine visit page.');
            return;
        }
        if (slug === 'kanji_quiz') {
            var host = el('activity-container');
            if (host) {
                host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">Kanji Quiz uses a dedicated page. <a href="/earn/kanji">Open kanji quiz</a>.</p>';
            }
            setActivityDone(false, 'Open the kanji quiz page.');
            return;
        }
        if (slug === 'retro_trivia') {
            var host = el('activity-container');
            if (host) {
                host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">Retro Game Trivia uses a dedicated page. <a href="/earn/retro">Open retro trivia</a>.</p>';
            }
            setActivityDone(false, 'Open the retro trivia page.');
            return;
        }
        if (slug === 'yokai_match') {
            var host = el('activity-container');
            if (host) {
                host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">Yokai Match uses a dedicated page. <a href="/earn/yokai-match">Open yokai match</a>.</p>';
            }
            setActivityDone(false, 'Open the yokai match page.');
            return;
        }
        if (slug === 'yokai_quiz') {
            var host = el('activity-container');
            if (host) {
                host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">Yokai Quiz uses a dedicated page. <a href="/earn/yokai-quiz">Open yokai quiz</a>.</p>';
            }
            setActivityDone(false, 'Open the yokai quiz page.');
            return;
        }
        if (slug === 'shrine_puzzle') {
            var host = el('activity-container');
            if (host) {
                host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">Shrine Puzzle uses a dedicated page. <a href="/earn/shrine-puzzle">Open shrine puzzle</a>.</p>';
            }
            setActivityDone(false, 'Open the shrine puzzle page.');
            return;
        }
        if (slug === 'map_explore') {
            renderMapExplore();
            return;
        }
        if (slug === 'coffee_quiz') {
            renderQuiz(coffeeQuestions, 4, 'Coffee quiz passed.');
            return;
        }
        if (slug === 'daily_bonus') {
            renderDailyBonus();
            return;
        }

        var host = el('activity-container');
        if (host) {
            host.innerHTML = '<p class="muted" style="margin:0;font-size:13px;">No local challenge configured for this activity yet.</p>';
        }
        setActivityDone(false, 'This activity is not yet available here.');
    }

    function claim() {
        var result = el('claim-result');
        var wallet = (el('claim-wallet') && el('claim-wallet').value || '').trim();
        if (!wallet) {
            if (result) result.textContent = 'Enter wallet first.';
            return;
        }
        if (!activityDone || !proofPayload) {
            if (result) result.textContent = 'Finish the activity first.';
            return;
        }
        if (hasTurnstile && !turnstileToken) {
            if (result) result.textContent = 'Complete Turnstile verification first.';
            return;
        }

        var idem = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + '-' + String(Math.random()).slice(2);
        if (result) result.textContent = 'Verifying activity...';

        fetch(API + '/faucet/activity-complete', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                wallet_address: wallet,
                activity_slug: slug,
                turnstile_token: turnstileToken,
                proof: proofPayload
            })
        }).then(function (r) {
            return r.json().then(function (data) {
                return { status: r.status, data: data };
            });
        }).then(function (res) {
            var data = res.data || {};
            if (res.status < 200 || res.status >= 400 || data.error) {
                if (result) result.textContent = 'Activity verification failed: ' + (data.error || res.status);
                return;
            }
            if (!data.completion_token) {
                if (result) result.textContent = 'No completion token returned.';
                return;
            }
            if (result) result.textContent = 'Submitting claim...';
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
            return r.json().then(function (data) {
                return { status: r.status, data: data };
            });
        }).then(function (res) {
            if (!res) return;
            var data = res.data || {};
            if (data.error) {
                if (result) result.textContent = 'Claim failed: ' + data.error;
                return;
            }
            if (data.pending) {
                if (result) result.textContent = 'Claim accepted and pending payout. Amount: ' + (data.amount || '—') + ' KOTO.';
                fetchClaimAvailability();
                return;
            }
            if (data.success) {
                var tx = data.txid ? (' txid=' + data.txid) : '';
                if (result) result.textContent = 'Claim paid. Amount: ' + (data.amount || '—') + ' KOTO.' + tx;
                fetchClaimAvailability();
                return;
            }
            if (result) result.textContent = 'Unexpected response.';
        }).catch(function () {
            if (result) result.textContent = 'Network error while submitting.';
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
            wallet.addEventListener('input', function () {
                updateClaimButton();
                fetchClaimAvailability();
            });
            wallet.addEventListener('change', function () {
                try { localStorage.setItem(walletKey, wallet.value.trim()); } catch (e) {}
                recordLastVisit();
                updateClaimButton();
                fetchClaimAvailability();
            });
        }
        var claimBtn = el('claim-btn');
        if (claimBtn) {
            claimBtn.addEventListener('click', claim);
        }
        renderActivity();
        recordLastVisit();
        fetchClaimAvailability();
        updateClaimButton();
    });
})();
</script>
@endpush
