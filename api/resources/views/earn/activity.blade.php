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
            Endpoint: <code>POST {{ $apiBase }}/faucet/claim</code>
        </p>
    </div>
@endsection

@push('head')
@if (!empty($turnstileSiteKey))
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
<script>
(function () {
    var cfg = document.getElementById('earn-activity-config');
    var API = cfg ? (cfg.getAttribute('data-api-base') || '') : '';
    var slug = cfg ? (cfg.getAttribute('data-slug') || '') : '';
    var hasTurnstile = cfg ? (cfg.getAttribute('data-has-turnstile') === '1') : false;
    var walletKey = 'isekai_earn_wallet';
    var activityDone = false;
    var turnstileToken = '';

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

    function el(id) { return document.getElementById(id); }

    function setActivityDone(done, message) {
        activityDone = !!done;
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
            var left = 8;
            timerEl.textContent = '... ' + left + 's';
            var t = setInterval(function () {
                left -= 1;
                if (left <= 0) {
                    clearInterval(t);
                    timerEl.textContent = 'Complete.';
                    setActivityDone(true, 'Shrine visit complete. You can claim now.');
                    return;
                }
                timerEl.textContent = '... ' + left + 's';
            }, 1000);
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
                setActivityDone(true, doneMessage + ' Score: ' + score + '/' + questions.length + '.');
            } else {
                setActivityDone(false, 'Need ' + passScore + '/' + questions.length + ' to claim. You scored ' + score + '.');
            }
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
        var payload = {
            wallet_address: wallet,
            activity_slug: slug,
            turnstile_token: turnstileToken,
            source_site: 'isekai-pool'
        };

        var idem = (window.crypto && crypto.randomUUID) ? crypto.randomUUID() : String(Date.now()) + '-' + String(Math.random()).slice(2);
        if (result) result.textContent = 'Submitting claim...';

        fetch(API + '/faucet/claim', {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'Idempotency-Key': idem
            },
            body: JSON.stringify(payload)
        }).then(function (r) {
            return r.json().then(function (data) {
                return { status: r.status, data: data };
            });
        }).then(function (res) {
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
            if (result) result.textContent = 'Network error while submitting claim.';
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
