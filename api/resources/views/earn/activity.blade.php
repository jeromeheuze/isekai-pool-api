@extends('earn.layout')

@section('title', $title)

@section('content')
    <p class="muted" style="margin-bottom:1rem;"><a href="/earn">← Earn hub</a></p>
    <h1>{{ $title }}</h1>
    <p class="muted" style="max-width:40rem;">{{ $intro }}</p>
    <div class="card" style="margin-top:1.25rem;">
        <p style="margin:0;"><strong style="color:#fff;">Activity slug</strong> <code class="muted">{{ $slug }}</code></p>
        <p class="muted" style="margin:0.75rem 0 0;font-size:13px;">
            Claim endpoint: <code>POST {{ $apiBase }}/faucet/claim</code> with <code>activity_slug</code>, wallet, and Turnstile token.
        </p>
    </div>
@endsection
