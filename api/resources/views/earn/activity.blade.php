@extends('earn.layout')

@section('title', $title)

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="{{ $slug }}"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>
    <p class="muted" style="margin-bottom:1rem;"><a href="/earn">← Earn hub</a></p>
    <h1>{{ $title }}</h1>
    <p class="muted" style="max-width:40rem;">{{ $intro }}</p>

    <div class="card" style="margin-top:1.25rem;">
        <p style="margin:0 0 0.6rem;"><strong style="color:#fff;">Wallet (saved in browser)</strong></p>
        <input id="claim-wallet" type="text" autocomplete="off" placeholder="k1…"
            style="width:100%;max-width:100%;padding:0.5rem 0.75rem;border-radius:6px;border:1px solid var(--border);background:#0d0f14;color:var(--text);font-family:inherit;font-size:13px;">
        <p class="muted" style="margin:0.6rem 0 0;font-size:12px;">This wallet is used only for faucet eligibility and claim submission.</p>
    </div>

    <div class="card">
        <p style="margin:0 0 0.75rem;"><strong style="color:#fff;">Activity challenge</strong></p>
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
        <p style="margin:0 0 0.75rem;"><strong style="color:#fff;">Activity slug</strong> <code class="muted">{{ $slug }}</code></p>

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

        <button id="claim-btn" class="btn" disabled style="margin-top:0.8rem;">Claim reward</button>
        <p id="claim-result" class="muted" style="margin:0.8rem 0 0;font-size:12px;">Not ready.</p>
        <p class="muted" style="margin:0.5rem 0 0;font-size:12px;">
            Flow: <code>POST {{ $apiBase }}/faucet/activity-complete</code> then <code>POST {{ $apiBase }}/faucet/claim</code>
        </p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
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
        host.innerHTML =
            '<p class="muted" style="margin:0 0 0.6rem;font-size:13px;">Click checkpoints in order: 1 → 2 → 3 → 4.</p>' +
            '<div id="map-points" style="display:flex;flex-wrap:wrap;gap:0.45rem;"></div>' +
            '<p id="map-state" class="muted" style="margin:0.7rem 0 0;font-size:12px;">Start at checkpoint 1.</p>';
        var points = el('map-points');
        var state = el('map-state');
        if (!points || !state) return;
        var next = 1;
        for (var i = 1; i <= 4; i++) {
            (function (n) {
                var b = document.createElement('button');
                b.type = 'button';
                b.className = 'btn btn-ghost';
                b.textContent = 'Checkpoint ' + n;
                b.addEventListener('click', function () {
                    if (n !== next) {
                        state.textContent = 'Wrong checkpoint. Follow order 1 → 2 → 3 → 4.';
                        return;
                    }
                    b.disabled = true;
                    b.style.opacity = '0.65';
                    next += 1;
                    if (next === 5) {
                        state.textContent = 'All checkpoints explored.';
                        setActivityDone(true, 'Map exploration complete. You can claim now.', { sequence: [1, 2, 3, 4] });
                    } else {
                        state.textContent = 'Great. Next: checkpoint ' + next + '.';
                    }
                });
                points.appendChild(b);
            })(i);
        }
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
