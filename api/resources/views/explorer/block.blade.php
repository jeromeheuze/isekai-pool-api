@extends('explorer.layout')

@php
    use App\Support\ExplorerFormat;
    $h = (int) ($block['height'] ?? 0);
    $hash = $block['hash'] ?? '';
    $time = (int) ($block['time'] ?? 0);
@endphp

@section('title', 'Block '.$h)

@section('content')
    <h1>Block <span style="color:var(--accent)">{{ number_format($h) }}</span></h1>
    <p class="muted">
        @if ($prevHeight !== null)
            <a href="{{ route('explorer.block', ['heightOrHash' => $prevHeight]) }}">← Previous</a>
        @else
            <span>← Previous</span>
        @endif
        &nbsp;·&nbsp;
        @if ($nextHeight !== null)
            <a href="{{ route('explorer.block', ['heightOrHash' => $nextHeight]) }}">Next →</a>
        @else
            <span>Next →</span>
        @endif
    </p>

    <dl class="grid" style="margin-top:1.5rem;">
        <dt>Hash</dt>
        <dd class="mono">{{ $hash }}</dd>
        <dt>Timestamp</dt>
        <dd>{{ $time ? ExplorerFormat::ago($time) : '—' }} <span class="muted">({{ $time ? gmdate('Y-m-d H:i:s\\Z', $time) : '' }})</span></dd>
        <dt>Mined by</dt>
        <dd>{{ $miner }}</dd>
        <dt>Size</dt>
        <dd>{{ ExplorerFormat::bytes((int) ($block['size'] ?? 0)) }}</dd>
        <dt>Weight</dt>
        <dd>{{ isset($block['weight']) ? number_format((int) $block['weight']) : '—' }}</dd>
        <dt>Difficulty</dt>
        <dd>{{ isset($block['difficulty']) ? number_format((float) $block['difficulty'], 4) : '—' }}</dd>
        <dt>Nonce</dt>
        <dd class="mono">{{ $block['nonce'] ?? '—' }}</dd>
    </dl>

    <h2>Transactions ({{ count($txRows) }})</h2>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Txid</th>
                    <th>Output total (KOTO)</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($txRows as $tr)
                    <tr>
                        <td class="mono"><a href="{{ route('explorer.tx', ['txid' => $tr['txid']]) }}">{{ ExplorerFormat::shortHash($tr['txid']) }}</a></td>
                        <td class="gold">{{ ExplorerFormat::koto($tr['value']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
