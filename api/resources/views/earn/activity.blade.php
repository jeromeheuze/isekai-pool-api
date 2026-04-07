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

    <div class="card">
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

    var kanjiQuestions = [
        { q: 'What does 水 mean?', options: ['fire', 'water', 'tree'], answer: 1 },
        { q: 'What does 日 mean?', options: ['sun/day', 'moon', 'mountain'], answer: 0 },
        { q: 'What does 山 mean?', options: ['river', 'mountain', 'gold'], answer: 1 },
        { q: 'What does 人 mean?', options: ['person', 'sword', 'rain'], answer: 0 },
        { q: 'What does 火 mean?', options: ['water', 'fire', 'earth'], answer: 1 }
    ];

    var retroQuestions = [
        { q: 'Nintendo launched the Famicom in which year?', options: ['1981', '1983', '1987'], answer: 1 },
        { q: 'Which company created the Mega Drive?', options: ['SEGA', 'SNK', 'NEC'], answer: 0 },
        { q: 'Which game popularized side-scrolling platformers?', options: ['Super Mario Bros.', 'Pac-Man', 'Tetris'], answer: 0 },
        { q: 'PC Engine was known as what in NA?', options: ['Master System', 'TurboGrafx-16', 'Neo Geo'], answer: 1 },
        { q: 'Which studio made Street Fighter II?', options: ['Konami', 'Taito', 'Capcom'], answer: 2 }
    ];
    var yokaiQuestions = [
        { q: 'Which yokai is known for a long neck at night?', options: ['Rokurokubi', 'Kappa', 'Tengu'], answer: 0 },
        { q: 'Kappa are commonly associated with what place?', options: ['Mountains', 'Rivers', 'Deserts'], answer: 1 },
        { q: 'Tengu are often depicted with what?', options: ['Long nose', 'Three eyes', 'Fish tail'], answer: 0 },
        { q: 'Zashiki-warashi are said to bring...', options: ['Bad weather', 'Good fortune', 'Earthquakes'], answer: 1 },
        { q: 'Nurarihyon is often portrayed as a...', options: ['Child spirit', 'Old man yokai', 'Fox yokai'], answer: 1 }
    ];
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
        btn.disabled = !(activityDone && wallet.length >= 20 && captchaOk);
    }

    function renderShrineActivity() {
        var host = el('activity-container');
        if (!host) return;
        host.innerHTML =
            '<p class="muted" style="margin:0 0 0.6rem;font-size:13px;">Focus for 8 seconds, then mark your shrine visit complete.</p>' +
            '<button id="start-shrine" class="btn btn-ghost">Start shrine moment</button>' +
            '<p id="shrine-timer" class="muted" style="margin:0.7rem 0 0;font-size:12px;">Not started.</p>';

        var startBtn = el('start-shrine');
        var timerEl = el('shrine-timer');
        if (!startBtn || !timerEl) return;
        startBtn.addEventListener('click', function () {
            startBtn.disabled = true;
            timerEl.textContent = 'Starting session...';
            fetch(API + '/faucet/activity-session', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ activity_slug: 'shrine_visit' })
            }).then(function (r) { return r.json().then(function (data) { return { ok: r.ok, data: data }; }); })
                .then(function (res) {
                    if (!res.ok || !res.data.session_id) {
                        timerEl.textContent = 'Could not start session. Refresh and try again.';
                        startBtn.disabled = false;
                        return;
                    }
                    var sessionId = res.data.session_id;
                    var left = 8;
                    timerEl.textContent = '... ' + left + 's';
                    var t = setInterval(function () {
                        left -= 1;
                        if (left <= 0) {
                            clearInterval(t);
                            timerEl.textContent = 'Complete.';
                            setActivityDone(true, 'Shrine visit complete. You can claim now.', { session_id: sessionId });
                            return;
                        }
                        timerEl.textContent = '... ' + left + 's';
                    }, 1000);
                }).catch(function () {
                    timerEl.textContent = 'Network error starting session.';
                    startBtn.disabled = false;
                });
        });
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

    function renderYokaiMatch() {
        var host = el('activity-container');
        if (!host) return;
        host.innerHTML =
            '<p class="muted" style="margin:0 0 0.6rem;font-size:13px;">Click one yokai from each pair. Match all 4 pairs.</p>' +
            '<div id="match-grid" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0.5rem;"></div>' +
            '<p id="match-state" class="muted" style="margin:0.7rem 0 0;font-size:12px;">0/4 matched.</p>';
        var pairs = [
            ['Kappa', 'river imp'],
            ['Tengu', 'mountain spirit'],
            ['Rokurokubi', 'long-neck yokai'],
            ['Nurarihyon', 'old visitor yokai']
        ];
        var cards = [];
        for (var i = 0; i < pairs.length; i++) {
            cards.push({ key: i, label: pairs[i][0] });
            cards.push({ key: i, label: pairs[i][1] });
        }
        cards.sort(function () { return Math.random() - 0.5; });
        var grid = el('match-grid');
        var state = el('match-state');
        if (!grid || !state) return;
        var first = null;
        var matched = 0;
        cards.forEach(function (c, idx) {
            var b = document.createElement('button');
            b.type = 'button';
            b.className = 'btn btn-ghost';
            b.style.textAlign = 'left';
            b.dataset.key = String(c.key);
            b.dataset.idx = String(idx);
            b.textContent = c.label;
            b.addEventListener('click', function () {
                if (b.disabled) return;
                if (first === null) {
                    first = b;
                    b.style.borderColor = '#7c6af7';
                    return;
                }
                if (first === b) return;
                if (first.dataset.key === b.dataset.key) {
                    first.disabled = true;
                    b.disabled = true;
                    first.style.opacity = '0.65';
                    b.style.opacity = '0.65';
                    matched += 1;
                    state.textContent = matched + '/4 matched.';
                    first = null;
                    if (matched === 4) {
                        setActivityDone(true, 'Yokai match complete. You can claim now.', {
                            matches: [[0, 0], [1, 1], [2, 2], [3, 3]]
                        });
                    }
                    return;
                }
                var prev = first;
                first = null;
                prev.style.borderColor = '';
                b.style.borderColor = '#f87171';
                setTimeout(function () {
                    b.style.borderColor = '';
                }, 350);
            });
            grid.appendChild(b);
        });
    }

    function renderShrinePuzzle() {
        var host = el('activity-container');
        if (!host) return;
        host.innerHTML =
            '<p class="muted" style="margin:0 0 0.6rem;font-size:13px;">Arrange shrine ritual steps in the correct order, then verify.</p>' +
            '<ol id="puzzle-list" style="margin:0;padding-left:1.1rem;"></ol>' +
            '<button id="verify-puzzle" class="btn btn-ghost" style="margin-top:0.7rem;">Verify order</button>' +
            '<p id="puzzle-state" class="muted" style="margin:0.7rem 0 0;font-size:12px;">Not solved.</p>';
        var steps = ['Bow', 'Cleanse hands', 'Offer prayer', 'Final bow'];
        var order = [0, 1, 2, 3].sort(function () { return Math.random() - 0.5; });
        var list = el('puzzle-list');
        var state = el('puzzle-state');
        var verify = el('verify-puzzle');
        if (!list || !state || !verify) return;
        order.forEach(function (idx) {
            var li = document.createElement('li');
            li.style.marginBottom = '0.45rem';
            var label = document.createElement('span');
            label.textContent = steps[idx];
            label.style.marginRight = '0.4rem';
            var up = document.createElement('button');
            up.type = 'button';
            up.className = 'btn btn-ghost';
            up.style.padding = '0.2rem 0.45rem';
            up.textContent = '↑';
            up.addEventListener('click', function () {
                var prev = li.previousElementSibling;
                if (prev) list.insertBefore(li, prev);
            });
            var down = document.createElement('button');
            down.type = 'button';
            down.className = 'btn btn-ghost';
            down.style.padding = '0.2rem 0.45rem';
            down.style.marginLeft = '0.3rem';
            down.textContent = '↓';
            down.addEventListener('click', function () {
                var next = li.nextElementSibling;
                if (next) list.insertBefore(next, li);
            });
            li.dataset.step = steps[idx];
            li.appendChild(label);
            li.appendChild(up);
            li.appendChild(down);
            list.appendChild(li);
        });
        verify.addEventListener('click', function () {
            var items = Array.prototype.map.call(list.querySelectorAll('li'), function (li) {
                return li.dataset.step;
            });
            var ok = items.join('|') === steps.join('|');
            if (ok) {
                state.textContent = 'Solved.';
                setActivityDone(true, 'Shrine puzzle solved. You can claim now.', {
                    order: ['Bow', 'Cleanse hands', 'Offer prayer', 'Final bow']
                });
            } else {
                state.textContent = 'Order is not correct yet.';
                setActivityDone(false, 'Reorder the steps and verify again.');
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
            renderShrineActivity();
            return;
        }
        if (slug === 'kanji_quiz') {
            renderQuiz(kanjiQuestions, 4, 'Kanji quiz passed.');
            return;
        }
        if (slug === 'retro_trivia') {
            renderQuiz(retroQuestions, 4, 'Retro trivia passed.');
            return;
        }
        if (slug === 'yokai_match') {
            renderYokaiMatch();
            return;
        }
        if (slug === 'yokai_quiz') {
            renderQuiz(yokaiQuestions, 4, 'Yokai quiz passed.');
            return;
        }
        if (slug === 'shrine_puzzle') {
            renderShrinePuzzle();
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
                return;
            }
            if (data.success) {
                var tx = data.txid ? (' txid=' + data.txid) : '';
                if (result) result.textContent = 'Claim paid. Amount: ' + (data.amount || '—') + ' KOTO.' + tx;
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
            wallet.addEventListener('input', updateClaimButton);
            wallet.addEventListener('change', function () {
                try { localStorage.setItem(walletKey, wallet.value.trim()); } catch (e) {}
                updateClaimButton();
            });
        }
        var claimBtn = el('claim-btn');
        if (claimBtn) {
            claimBtn.addEventListener('click', claim);
        }
        renderActivity();
        updateClaimButton();
    });
})();
</script>
@endpush
