@extends('earn.layout')

@section('title', $title)

@section('content')
    <div id="earn-activity-config"
         data-api-base="{{ $apiBase }}"
         data-slug="{{ $slug }}"
         data-has-turnstile="{{ !empty($turnstileSiteKey) ? '1' : '0' }}">
    </div>
    <p class="muted" style="margin-bottom:1rem;"><a href="/earn">← Earn hub</a></p>
    @if ($slug !== 'map_explore' && $slug !== 'coffee_quiz' && $slug !== 'daily_bonus')
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
        <p style="margin:0 0 0.75rem;"><strong style="color:#fff;">@if ($slug === 'map_explore')Pilgrimage path@elseif ($slug === 'coffee_quiz')Kissaten quiz@elseif ($slug === 'daily_bonus')Daily reward@else Activity challenge @endif</strong></p>
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
        @if ($slug !== 'map_explore' && $slug !== 'coffee_quiz' && $slug !== 'daily_bonus')
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

        <button type="button" id="claim-btn" class="btn{{ $slug === 'map_explore' ? ' btn-map-claim' : '' }}{{ $slug === 'coffee_quiz' ? ' btn-coffee-claim' : '' }}{{ $slug === 'daily_bonus' ? ' btn-daily-claim' : '' }}" disabled style="margin-top:0.8rem;position:relative;z-index:2;{{ ($slug === 'map_explore' || $slug === 'coffee_quiz' || $slug === 'daily_bonus') ? ' width:100%;' : '' }}">@if ($slug === 'map_explore')Claim 1 KOTO@elseif ($slug === 'coffee_quiz')Claim 0.5 KOTO@elseif ($slug === 'daily_bonus')<span style="display:inline-flex;align-items:center;justify-content:center;gap:0.4rem;width:100%;"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z"/></svg>Claim 1.5 KOTO</span>@else Claim reward @endif</button>
        <p id="claim-result" class="muted" style="margin:0.8rem 0 0;font-size:12px;">Not ready.</p>
        @if ($slug !== 'map_explore' && $slug !== 'coffee_quiz' && $slug !== 'daily_bonus')
            <p class="muted" style="margin:0.5rem 0 0;font-size:12px;">
                Flow: <code>POST {{ $apiBase }}/faucet/activity-complete</code> then <code>POST {{ $apiBase }}/faucet/claim</code>
            </p>
        @endif
    </div>
@endsection

@push('head')
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
            background: #2d2f38 !important;
            color: #9ca3af !important;
            opacity: 0.85;
            cursor: not-allowed;
        }
    </style>
@endif
@if ($slug === 'coffee_quiz')
    <style>
        .coffee-quiz { max-width: 40rem; }
        .coffee-quiz-header { margin-bottom: 1.15rem; }
        .coffee-quiz-header__title-row {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin: 0 0 0.45rem;
        }
        .coffee-quiz-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: #fff;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
        }
        .coffee-quiz-sub {
            margin: 0 0 0.85rem;
            font-size: 13px;
            color: #a8a29e;
            line-height: 1.45;
            max-width: 36rem;
        }
        .coffee-quiz-progress-row {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            flex-wrap: wrap;
        }
        .coffee-quiz-progress-track {
            flex: 1;
            min-width: 120px;
            height: 3px;
            border-radius: 2px;
            background: #1e2030;
            overflow: hidden;
        }
        .coffee-quiz-progress-fill {
            height: 100%;
            width: 20%;
            background: #c8956c;
            border-radius: 2px;
            transition: width 0.35s ease;
        }
        .coffee-quiz-pill {
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            padding: 0.25rem 0.6rem;
            border-radius: 999px;
            border: 1px solid rgba(200, 149, 108, 0.55);
            color: #d6b89a;
            background: rgba(200, 149, 108, 0.06);
        }
        .coffee-q-card {
            position: relative;
            overflow: hidden;
            background: #111318;
            border: 1px solid #1e2030;
            border-left: 3px solid #c8956c;
            border-radius: 8px;
            padding: 1.5rem;
        }
        .coffee-q-watermark {
            position: absolute;
            right: -0.25rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 80px;
            font-weight: 700;
            line-height: 1;
            color: #c8956c;
            opacity: 0.06;
            pointer-events: none;
            user-select: none;
            white-space: nowrap;
        }
        .coffee-q-text {
            position: relative;
            z-index: 1;
            margin: 0 0 1.1rem;
            font-size: 15px;
            font-weight: 600;
            color: #f3f4f6;
            line-height: 1.45;
            padding-right: 2rem;
        }
        .coffee-q-options {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        .coffee-sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }
        .coffee-opt {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.65rem 1rem;
            border-radius: 999px;
            border: 1px solid #1e2030;
            background: #0d0f14;
            cursor: pointer;
            transition: border-color 0.15s ease, background 0.15s ease;
        }
        .coffee-opt:hover {
            border-color: rgba(200, 149, 108, 0.5);
        }
        .coffee-opt--selected {
            border-color: #c8956c;
            background: #1a1208;
            color: #fff;
        }
        .coffee-opt__dot {
            display: none;
            flex-shrink: 0;
            align-items: center;
            justify-content: center;
        }
        .coffee-opt--selected .coffee-opt__dot {
            display: flex;
        }
        .coffee-opt__icon {
            display: block;
        }
        .coffee-opt__label {
            font-size: 13px;
            color: #d1d5db;
        }
        .coffee-opt--selected .coffee-opt__label {
            color: #fff;
        }
        .coffee-btn-next {
            margin-top: 1.25rem;
            width: 100%;
            padding: 0.55rem 1rem;
            border: none;
            border-radius: 8px;
            background: #c8956c;
            color: #0d0f14;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            transition: filter 0.15s ease, opacity 0.15s ease;
        }
        .coffee-btn-next:hover:not(:disabled) {
            filter: brightness(1.06);
        }
        .coffee-btn-next:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }
        .coffee-quiz-results { margin-top: 0.5rem; }
        .coffee-results-head {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 0.65rem;
            padding: 1rem 0 1.25rem;
        }
        .coffee-results-head__score {
            font-size: 1.75rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
        }
        .coffee-results-head__score--pass { color: #f0c040; }
        .coffee-results-head__score--fail { color: #c8956c; }
        .coffee-results-head__msg {
            margin: 0;
            font-size: 14px;
            font-weight: 600;
            max-width: 22rem;
            line-height: 1.45;
        }
        .coffee-results-head__msg--pass { color: #e7e5e4; }
        .coffee-results-head__msg--fail { color: #a8a29e; }
        .coffee-results-banner {
            width: 100%;
            margin-top: 0.25rem;
            padding: 1rem 1.15rem;
            border-radius: 8px;
            background: #1a1208;
            border: 1px solid rgba(200, 149, 108, 0.45);
            text-align: center;
            font-size: 13px;
            color: #d6b89a;
            animation: coffee-banner-in 0.65s ease-out;
        }
        @keyframes coffee-banner-in {
            from { opacity: 0; transform: translateY(-5px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .coffee-results-fail-box {
            margin-top: 0.75rem;
            padding: 0.9rem 1rem;
            border-radius: 8px;
            background: rgba(180, 83, 9, 0.08);
            border: 1px solid rgba(217, 119, 6, 0.28);
        }
        .coffee-results-fail-box p {
            margin: 0;
            font-size: 12px;
            color: #d6b89a;
            line-height: 1.5;
        }
        .coffee-results-fail-box p + p {
            margin-top: 0.45rem;
        }
        .btn-coffee-claim {
            width: 100%;
            background: #c8956c !important;
            color: #0d0f14 !important;
            font-family: 'JetBrains Mono', ui-monospace, monospace !important;
            font-weight: 600;
            border: none;
        }
        .btn-coffee-claim:hover:not(:disabled) {
            filter: brightness(1.06);
            color: #0d0f14 !important;
        }
        .btn-coffee-claim:disabled {
            background: #3d342c !important;
            color: #9ca3af !important;
            opacity: 0.85;
            cursor: not-allowed;
        }
    </style>
@endif
@if ($slug === 'daily_bonus')
    <style>
        .db-header {
            margin-bottom: 1.25rem;
        }
        .db-header__title-row {
            display: flex;
            align-items: center;
            gap: 0.65rem;
            margin: 0 0 0.45rem;
        }
        .db-header__title-row h2 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: #fff;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
        }
        .db-header__sub {
            margin: 0 0 0.5rem;
            font-size: 13px;
            color: #9ca3af;
            max-width: 36rem;
            line-height: 1.45;
        }
        .db-header__streak {
            display: flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 12px;
            color: #6b7280;
            margin: 0;
        }
        .db-hero {
            text-align: center;
            padding: 3rem 1rem;
            position: relative;
        }
        .db-hero__star-wrap {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
        }
        .db-star-hero {
            width: 64px;
            height: 64px;
            animation: db-star-pulse 2s ease-in-out infinite;
        }
        @keyframes db-star-pulse {
            0%, 100% { transform: scale(1); opacity: 0.85; }
            50% { transform: scale(1.05); opacity: 1; }
        }
        .db-star-hero.db-star-hero--done {
            animation: none;
            transform: scale(1);
            opacity: 1;
        }
        .db-burst {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }
        .db-spark {
            position: absolute;
            left: 50%;
            top: 50%;
            width: 6px;
            height: 6px;
            margin: -3px 0 0 -3px;
            background: #f0c040;
            border-radius: 1px;
            opacity: 0;
            transform: rotate(var(--db-r, 0deg)) translateY(0);
        }
        .db-hero__star-wrap--burst .db-spark {
            animation: db-spark-fly 0.65s ease-out forwards;
        }
        .db-spark:nth-child(1) { --db-r: 0deg; animation-delay: 0s; }
        .db-spark:nth-child(2) { --db-r: 60deg; animation-delay: 0.03s; }
        .db-spark:nth-child(3) { --db-r: 120deg; animation-delay: 0.06s; }
        .db-spark:nth-child(4) { --db-r: 180deg; animation-delay: 0.09s; }
        .db-spark:nth-child(5) { --db-r: 240deg; animation-delay: 0.12s; }
        .db-spark:nth-child(6) { --db-r: 300deg; animation-delay: 0.15s; }
        @keyframes db-spark-fly {
            0% {
                opacity: 1;
                transform: rotate(var(--db-r, 0deg)) translateY(0) scale(1);
            }
            100% {
                opacity: 0;
                transform: rotate(var(--db-r, 0deg)) translateY(-48px) scale(0.2);
            }
        }
        .db-hero__jp {
            margin: 0 0 1.25rem;
            font-size: 13px;
            color: #5eead4;
            opacity: 0.85;
        }
        .db-hero__jp--done {
            color: #2dd4bf;
            font-weight: 600;
        }
        .db-btn-checkin {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            width: 100%;
            max-width: 320px;
            margin: 0 auto;
            padding: 0.75rem 1.25rem;
            border: none;
            border-radius: 10px;
            background: #7c6af7;
            color: #fff;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: filter 0.15s ease;
        }
        .db-btn-checkin:hover:not(:disabled) {
            filter: brightness(1.08);
        }
        .db-btn-checkin:disabled {
            cursor: default;
        }
        .db-btn-checkin--done {
            background: #0d9488 !important;
            color: #ecfdf5 !important;
        }
        .db-week {
            margin-top: 1.75rem;
            padding-top: 1.25rem;
            border-top: 1px solid #1e2030;
        }
        .db-week__row {
            display: flex;
            gap: 8px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .db-week__cell {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            min-width: 36px;
        }
        .db-week__dot {
            width: 14px;
            height: 14px;
            border-radius: 50%;
            border: 2px solid #1e2030;
            background: transparent;
            box-sizing: border-box;
            position: relative;
        }
        .db-week__dot--past {
            border-color: #7c6af7;
            background: #7c6af7;
        }
        .db-week__dot--past::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 4px;
            height: 4px;
            margin: -2px 0 0 -2px;
            border-radius: 50%;
            background: #fff;
        }
        .db-week__dot--future {
            border-color: #1e2030;
            background: #0d0f14;
        }
        .db-week__dot--today {
            border-color: rgba(251, 191, 36, 0.85);
            background: transparent;
            animation: db-today-pulse 1.8s ease-in-out infinite;
        }
        .db-week__dot--today-checked {
            border-color: #f0c040;
            background: #f0c040;
            animation: none;
        }
        .db-week__dot--today-checked::after {
            display: none;
        }
        @keyframes db-today-pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(251, 191, 36, 0.35); }
            50% { box-shadow: 0 0 0 5px rgba(251, 191, 36, 0); }
        }
        .db-week__label {
            font-size: 10px;
            font-family: 'JetBrains Mono', ui-monospace, monospace;
            color: #6b7280;
        }
        .db-reward-card {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.65rem;
            margin-top: 1.25rem;
            padding: 0.85rem 1rem;
            border-radius: 8px;
            background: rgba(124, 106, 247, 0.1);
            border: 1px solid rgba(124, 106, 247, 0.35);
            font-size: 14px;
            font-weight: 600;
            color: #c4b5fd;
            animation: db-reward-in 0.5s ease-out;
        }
        .db-reward-card--visible {
            display: flex;
        }
        @keyframes db-reward-in {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .btn-daily-claim {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.45rem;
            width: 100%;
            background: #f0c040 !important;
            color: #0d0f14 !important;
            font-family: 'JetBrains Mono', ui-monospace, monospace !important;
            font-weight: 600;
            border: none;
        }
        .btn-daily-claim:hover:not(:disabled) {
            filter: brightness(1.06);
            color: #0d0f14 !important;
        }
        .btn-daily-claim:disabled {
            background: #3d3828 !important;
            color: #9ca3af !important;
            opacity: 0.85;
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

    function renderCoffeeQuiz() {
        var questions = coffeeQuestions;
        var passScore = 4;
        var doneMessage = 'Coffee quiz passed.';
        var WATERMARKS = ['ドリップ', 'エスプレッソ', 'アラビカ', 'カフェラテ', '保存'];

        var ICON_COFFEE_HDR = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 2v2" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v2" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 0 0 0 4h1a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1Z" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 2v2" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_COFFEE_PASS = '<svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M10 2v2" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M14 2v2" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M16 8a1 1 0 0 1 1 1v8a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V9a1 1 0 0 1 1-1h14a4 4 0 0 0 0 4h1a1 1 0 0 0 1-1V8a1 1 0 0 0-1-1Z" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path d="M6 2v2" stroke="#c8956c" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_CIRCLE_DOT = '<svg class="coffee-opt__icon" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="#c8956c" stroke-width="2"/><circle cx="12" cy="12" r="3" fill="#c8956c" stroke="none"/></svg>';
        var ICON_X = '<svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="10" stroke="#c8956c" stroke-width="2"/><path d="m15 9-6 6" stroke="#c8956c" stroke-width="2" stroke-linecap="round"/><path d="m9 9 6 6" stroke="#c8956c" stroke-width="2" stroke-linecap="round"/></svg>';

        var host = el('activity-container');
        if (!host) return;

        var html = '';
        html += '<div class="coffee-quiz">';
        html += '<div class="coffee-quiz-header">';
        html += '<div class="coffee-quiz-header__title-row">' + ICON_COFFEE_HDR + '<h2 class="coffee-quiz-title">Coffee Quiz</h2></div>';
        html += '<p class="coffee-quiz-sub">Five questions about coffee culture — score 4/5 or better</p>';
        html += '<div class="coffee-quiz-progress-row">';
        html += '<div class="coffee-quiz-progress-track"><div class="coffee-quiz-progress-fill" id="coffee-progress-fill"></div></div>';
        html += '<span class="coffee-quiz-pill" id="coffee-q-pill">Q 1 / 5</span>';
        html += '</div></div>';
        html += '<div id="coffee-quiz-stage"></div>';
        html += '<div id="coffee-quiz-results" class="coffee-quiz-results" hidden></div>';
        html += '</div>';

        host.innerHTML = html;

        var step = 0;
        var answers = [null, null, null, null, null];
        var stage = el('coffee-quiz-stage');
        var resultsEl = el('coffee-quiz-results');
        var fillEl = el('coffee-progress-fill');
        var pillEl = el('coffee-q-pill');

        function updateProgress() {
            var pct = ((step + 1) / 5) * 100;
            if (fillEl) fillEl.style.width = pct + '%';
            if (pillEl) pillEl.textContent = 'Q ' + (step + 1) + ' / 5';
        }

        function getSelected() {
            var r = document.querySelector('#coffee-quiz-stage input[name="coffee-cur"]:checked');
            return r ? parseInt(r.value, 10) : null;
        }

        function updateOptClasses() {
            if (!stage) return;
            var sel = getSelected();
            var labels = stage.querySelectorAll('.coffee-opt');
            for (var i = 0; i < labels.length; i++) {
                var lab = labels[i];
                var v = parseInt(lab.getAttribute('data-opt'), 10);
                lab.classList.toggle('coffee-opt--selected', sel !== null && sel === v);
            }
        }

        function renderStep() {
            if (!stage) return;
            var q = questions[step];
            var wm = WATERMARKS[step];
            var inner = '';
            inner += '<div class="coffee-q-card">';
            inner += '<span class="coffee-q-watermark" aria-hidden="true">' + wm + '</span>';
            inner += '<p class="coffee-q-text">' + q.q + '</p>';
            inner += '<div class="coffee-q-options" role="radiogroup" aria-label="Choose an answer">';
            for (var j = 0; j < q.options.length; j++) {
                inner += '<label class="coffee-opt" data-opt="' + j + '">';
                inner += '<input type="radio" name="coffee-cur" value="' + j + '" class="coffee-sr-only">';
                inner += '<span class="coffee-opt__dot" aria-hidden="true">' + ICON_CIRCLE_DOT + '</span>';
                inner += '<span class="coffee-opt__label">' + q.options[j] + '</span>';
                inner += '</label>';
            }
            inner += '</div>';
            inner += '<button type="button" class="coffee-btn-next" id="coffee-next-btn" disabled>Next →</button>';
            inner += '</div>';
            stage.innerHTML = inner;

            var nextBtn = el('coffee-next-btn');
            var inputs = stage.querySelectorAll('input[name="coffee-cur"]');
            for (var ii = 0; ii < inputs.length; ii++) {
                inputs[ii].addEventListener('change', function () {
                    updateOptClasses();
                    if (nextBtn) nextBtn.disabled = getSelected() === null;
                });
            }
            if (nextBtn) {
                nextBtn.addEventListener('click', function () {
                    var sel = getSelected();
                    if (sel === null) return;
                    answers[step] = sel;
                    if (step < 4) {
                        step += 1;
                        renderStep();
                    } else {
                        gradeCoffee();
                    }
                });
            }
            updateProgress();
        }

        function gradeCoffee() {
            var score = 0;
            for (var i = 0; i < questions.length; i++) {
                if (answers[i] === questions[i].answer) score += 1;
            }
            if (fillEl) fillEl.style.width = '100%';
            if (stage) stage.style.display = 'none';
            if (resultsEl) resultsEl.removeAttribute('hidden');

            var pass = score >= passScore;
            var ansProof = [];
            for (var ai = 0; ai < answers.length; ai++) {
                ansProof.push(answers[ai]);
            }

            if (pass) {
                resultsEl.className = 'coffee-quiz-results coffee-quiz-results--pass';
                resultsEl.innerHTML =
                    '<div class="coffee-results-head">' +
                    ICON_COFFEE_PASS +
                    '<div class="coffee-results-head__score coffee-results-head__score--pass">' + score + '/5</div>' +
                    '<p class="coffee-results-head__msg coffee-results-head__msg--pass">Barista approved — reward unlocked</p>' +
                    '</div>' +
                    '<div class="coffee-results-banner">Reward unlocked — complete Turnstile below to claim.</div>';
                setActivityDone(true, doneMessage + ' Score: ' + score + '/' + questions.length + '.', { answers: ansProof });
            } else {
                resultsEl.className = 'coffee-quiz-results coffee-quiz-results--fail';
                resultsEl.innerHTML =
                    '<div class="coffee-results-head">' +
                    ICON_X +
                    '<div class="coffee-results-head__score coffee-results-head__score--fail">' + score + '/5</div>' +
                    '<p class="coffee-results-head__msg coffee-results-head__msg--fail">Not quite — steep yourself in coffee lore and try tomorrow</p>' +
                    '</div>' +
                    '<div class="coffee-results-fail-box">' +
                    '<p id="coffee-fail-cooldown">Checking cooldown…</p>' +
                    '</div>';
                setActivityDone(false, 'Need ' + passScore + '/' + questions.length + ' to claim. You scored ' + score + '.');
                fetchCoffeeFailCooldown();
            }
        }

        function fetchCoffeeFailCooldown() {
            var coolP = el('coffee-fail-cooldown');
            if (!coolP) return;
            var w = (el('claim-wallet') && el('claim-wallet').value || '').trim();
            if (!w || w.length < 20 || !API) {
                coolP.textContent = 'Enter your wallet above to see the next eligible claim time.';
                fetchClaimAvailability();
                return;
            }
            fetch(API + '/faucet/status?wallet=' + encodeURIComponent(w), { headers: { 'Accept': 'application/json' } })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data.error || !data.activities) {
                        coolP.textContent = 'Cooldown applies — try again when the faucet allows.';
                        fetchClaimAvailability();
                        return;
                    }
                    var mine = null;
                    for (var i = 0; i < data.activities.length; i++) {
                        if (data.activities[i].slug === 'coffee_quiz') {
                            mine = data.activities[i];
                            break;
                        }
                    }
                    if (mine && mine.next_claim_at) {
                        coolP.textContent = 'Next eligible claim: ' + formatWhen(mine.next_claim_at) + ' (local time).';
                    } else {
                        coolP.textContent = 'Steep yourself in coffee lore — come back after the daily window resets.';
                    }
                    fetchClaimAvailability();
                })
                .catch(function () {
                    coolP.textContent = 'Try again after the cooldown.';
                    fetchClaimAvailability();
                });
        }

        renderStep();
        var actState = el('activity-state');
        if (actState) actState.textContent = 'Five questions — 4/5 or better unlocks claim.';
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
        var STREAK_KEY = 'isekai_daily_bonus_streak_v1';
        function pad2(n) {
            return n < 10 ? '0' + n : '' + n;
        }
        function ymd(d) {
            return d.getFullYear() + '-' + pad2(d.getMonth() + 1) + '-' + pad2(d.getDate());
        }
        function readStreak() {
            try {
                var raw = localStorage.getItem(STREAK_KEY);
                var o = raw ? JSON.parse(raw) : null;
                return o && typeof o === 'object' ? o : { lastYmd: '', count: 0 };
            } catch (e) {
                return { lastYmd: '', count: 0 };
            }
        }
        function bumpStreak() {
            var t = readStreak();
            var today = ymd(new Date());
            var y = new Date();
            y.setDate(y.getDate() - 1);
            var yesterday = ymd(y);
            var count = 1;
            if (t.lastYmd === today) {
                count = t.count || 1;
            } else if (t.lastYmd === yesterday) {
                count = (t.count || 0) + 1;
            }
            try {
                localStorage.setItem(STREAK_KEY, JSON.stringify({ lastYmd: today, count: count }));
            } catch (e) {}
            return count;
        }

        var ICON_STAR_HDR = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.03a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" stroke="#f0c040" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_FLAME = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z" stroke="#fb7185" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_STAR_OUT = '<svg class="db-star-hero" id="db-hero-star" xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.03a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" stroke="#f0c040" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_STAR_FILL = '<svg class="db-star-hero db-star-hero--done" id="db-hero-star" xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" aria-hidden="true"><path d="M11.525 2.295a.53.53 0 0 1 .95 0l2.31 4.679a2.123 2.123 0 0 0 1.595 1.16l5.166.756a.53.53 0 0 1 .294.904l-3.736 3.638a2.123 2.123 0 0 0-.611 1.878l.882 5.14a.53.53 0 0 1-.771.56l-4.618-2.428a2.122 2.122 0 0 0-1.973 0L6.396 21.03a.53.53 0 0 1-.77-.56l.881-5.139a2.122 2.122 0 0 0-.611-1.879L2.16 9.795a.53.53 0 0 1 .294-.906l5.165-.755a2.122 2.122 0 0 0 1.597-1.16z" fill="#f0c040" stroke="#f0c040" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_ZAP = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 14a1 1 0 0 1-.78-1.63l9.9-10.2a.5.5 0 0 1 .86.46l-1.92 6.02A1 1 0 0 0 13 10h7a1 1 0 0 1 .78 1.63l-9.9 10.2a.5.5 0 0 1-.86-.46l1.92-6.02A1 1 0 0 0 11 14z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_CHECK_BTN = '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M20 6 9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
        var ICON_GIFT = '<svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="8" width="18" height="4" rx="1" stroke="#a78bfa" stroke-width="2"/><path d="M12 8v13" stroke="#a78bfa" stroke-width="2"/><path d="M19 12v7a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-7" stroke="#a78bfa" stroke-width="2"/><path d="M7.5 8a2.5 2.5 0 0 1 0-5A4.8 4.8 0 0 1 12 8a4.8 4.8 0 0 1 4.5-5 2.5 2.5 0 0 1 0 5" stroke="#a78bfa" stroke-width="2"/></svg>';

        function weekRowHtml(checkedIn) {
            var labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
            var mondayOffset = (new Date().getDay() + 6) % 7;
            var html = '<div class="db-week" id="db-week-root"><div class="db-week__row">';
            for (var i = 0; i < 7; i++) {
                var cls = 'db-week__dot';
                if (i < mondayOffset) cls += ' db-week__dot--past';
                else if (i > mondayOffset) cls += ' db-week__dot--future';
                else cls += checkedIn ? ' db-week__dot--today-checked' : ' db-week__dot--today';
                html += '<div class="db-week__cell"><div class="' + cls + '"></div><span class="db-week__label">' + labels[i] + '</span></div>';
            }
            html += '</div></div>';
            return html;
        }

        var host = el('activity-container');
        if (!host) return;

        var streak = readStreak();
        var todayYmd = ymd(new Date());
        var yest = new Date();
        yest.setDate(yest.getDate() - 1);
        var yesterdayYmd = ymd(yest);
        var streakN = streak.count || 0;
        var showStreakHdr = streakN >= 1 && streak.lastYmd && (streak.lastYmd === todayYmd || streak.lastYmd === yesterdayYmd);

        var streakLine = '';
        if (showStreakHdr) {
            streakLine =
                '<p class="db-header__streak" id="db-streak-line">' +
                ICON_FLAME +
                '<span id="db-streak-text">' +
                streakN +
                ' day streak</span></p>';
        }

        var html = '';
        html += '<div class="db-daily-root">';
        html += '<div class="db-header">';
        html += '<div class="db-header__title-row">' + ICON_STAR_HDR + '<h2>Daily Bonus</h2></div>';
        html += '<p class="db-header__sub">One quick check-in per day — show up and earn</p>';
        html += streakLine;
        html += '</div>';

        html += '<div class="db-hero">';
        html += '<div class="db-hero__star-wrap" id="db-star-wrap">';
        html += '<div class="db-burst" id="db-burst" style="display:none;">';
        for (var sp = 0; sp < 6; sp++) {
            html += '<span class="db-spark"></span>';
        }
        html += '</div>';
        html += ICON_STAR_OUT;
        html += '</div>';
        html += '<p class="db-hero__jp" id="db-hero-jp">今日のボーナス</p>';
        html +=
            '<button type="button" class="db-btn-checkin" id="daily-check-in">' +
            ICON_ZAP +
            ' Check in</button>';
        html += '</div>';

        html += weekRowHtml(false);

        html +=
            '<div class="db-reward-card" id="db-reward-card">' +
            ICON_GIFT +
            '<span>1.5 KOTO reward ready to claim</span></div>';

        html += '<p id="daily-state" class="muted" style="margin:0.85rem 0 0;font-size:12px;text-align:center;"></p>';
        html += '</div>';

        host.innerHTML = html;

        var btn = el('daily-check-in');
        var state = el('daily-state');
        var starWrap = el('db-star-wrap');
        var burst = el('db-burst');
        var heroJp = el('db-hero-jp');
        var rewardCard = el('db-reward-card');
        if (!btn) return;

        function applyCheckedInUi(sessionId) {
            var newN = bumpStreak();
            var stEl = el('db-streak-line');
            var stText = el('db-streak-text');
            if (!stEl) {
                var hdr = host.querySelector('.db-header');
                if (hdr) {
                    hdr.insertAdjacentHTML(
                        'beforeend',
                        '<p class="db-header__streak" id="db-streak-line">' +
                            ICON_FLAME +
                            '<span id="db-streak-text">' +
                            newN +
                            ' day streak</span></p>'
                    );
                }
            } else if (stText) {
                stText.textContent = newN + ' day streak';
            }
            var starEl = el('db-hero-star');
            if (starEl) {
                starEl.outerHTML = ICON_STAR_FILL;
            }
            if (starWrap) starWrap.classList.add('db-hero__star-wrap--burst');
            if (burst) burst.style.display = '';
            if (heroJp) {
                heroJp.textContent = 'チェックイン完了';
                heroJp.classList.add('db-hero__jp--done');
            }
            btn.classList.add('db-btn-checkin--done');
            btn.disabled = true;
            btn.innerHTML = ICON_CHECK_BTN + ' Checked in';
            if (rewardCard) rewardCard.classList.add('db-reward-card--visible');
            var wk = el('db-week-root');
            if (wk) wk.outerHTML = weekRowHtml(true);
            setActivityDone(true, 'Daily bonus unlocked. You can claim now.', { session_id: sessionId });
        }

        btn.addEventListener('click', function () {
            btn.disabled = true;
            if (state) state.textContent = 'Starting check-in...';
            fetch(API + '/faucet/activity-session', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'Content-Type': 'application/json' },
                body: JSON.stringify({ activity_slug: 'daily_bonus' })
            })
                .then(function (r) {
                    return r.json().then(function (data) {
                        return { ok: r.ok, data: data };
                    });
                })
                .then(function (res) {
                    if (!res.ok || !res.data.session_id) {
                        if (state) state.textContent = 'Could not start check-in.';
                        btn.disabled = false;
                        btn.innerHTML = ICON_ZAP + ' Check in';
                        return;
                    }
                    var sessionId = res.data.session_id;
                    setTimeout(function () {
                        if (state) state.textContent = '';
                        applyCheckedInUi(sessionId);
                    }, 1100);
                })
                .catch(function () {
                    if (state) state.textContent = 'Network error.';
                    btn.disabled = false;
                    btn.innerHTML = ICON_ZAP + ' Check in';
                });
        });

        var actLine = el('activity-state');
        if (actLine) actLine.textContent = 'Tap check-in to unlock today’s bonus.';
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
            renderCoffeeQuiz();
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
@if (!empty($turnstileSiteKey))
{{-- Load after earn script so window.onEarnTurnstile exists before Turnstile initializes (avoids race with async head). --}}
<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endif
@endpush
