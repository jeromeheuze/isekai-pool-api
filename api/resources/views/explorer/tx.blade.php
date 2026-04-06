@extends('explorer.layout')

@php
    use App\Support\ExplorerFormat;
@endphp

@section('title', 'Transaction '.ExplorerFormat::shortHash($txid))

@section('content')
    <h1>Transaction</h1>
    <p class="mono muted" style="word-break:break-all;">{{ $txid }}</p>

    @if ($isShielded)
        <p style="margin-top:1.5rem;padding:1rem;border:1px solid var(--border);border-radius:8px;background:var(--card);">
            Shielded transaction — details private by design.
        </p>
    @endif

    <dl class="grid" style="margin-top:1.5rem;">
        <dt>Confirmations</dt>
        <dd>{{ $tx['confirmations'] ?? 0 }}</dd>
        <dt>Block height</dt>
        <dd>
            @if ($height !== null)
                <a href="{{ route('explorer.block', ['heightOrHash' => $height]) }}">{{ number_format((int) $height) }}</a>
            @else
                —
            @endif
        </dd>
        <dt>Time</dt>
        <dd>
            @php $bt = (int) ($tx['blocktime'] ?? $tx['time'] ?? 0); @endphp
            {{ $bt ? ExplorerFormat::ago($bt).' · '.gmdate('Y-m-d H:i:s\\Z', $bt) : '—' }}
        </dd>
        <dt>Total output</dt>
        <dd class="gold">{{ ExplorerFormat::koto($sumOut) }} KOTO</dd>
        @if ($sumIn > 0)
            <dt>Total input</dt>
            <dd class="gold">{{ ExplorerFormat::koto($sumIn) }} KOTO</dd>
        @endif
        @if ($fee !== null)
            <dt>Fee</dt>
            <dd class="gold">{{ ExplorerFormat::koto($fee) }} KOTO</dd>
        @endif
    </dl>

    @if (! $isShielded)
        <h2>Inputs</h2>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Previous tx</th>
                        <th>Index</th>
                        <th>Value</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tx['vin'] ?? [] as $vin)
                        @if (! is_array($vin))
                            @continue
                        @endif
                        @if (isset($vin['coinbase']))
                            <tr>
                                <td colspan="3" class="muted">Coinbase</td>
                            </tr>
                        @else
                            <tr>
                                <td class="mono">
                                    @if (!empty($vin['txid']))
                                        <a href="{{ route('explorer.tx', ['txid' => $vin['txid']]) }}">{{ ExplorerFormat::shortHash($vin['txid']) }}</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $vin['vout'] ?? '—' }}</td>
                                <td class="gold">{{ isset($vin['value']) ? ExplorerFormat::koto((float) $vin['value']) : '—' }}</td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>

        <h2>Outputs</h2>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Address</th>
                        <th>Value (KOTO)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($tx['vout'] ?? [] as $i => $out)
                        @if (! is_array($out))
                            @continue
                        @endif
                        @php
                            $spk = $out['scriptPubKey'] ?? [];
                            $addr = $spk['address'] ?? (is_array($spk['addresses'] ?? null) ? ($spk['addresses'][0] ?? null) : null);
                        @endphp
                        <tr>
                            <td>{{ $i }}</td>
                            <td class="mono">
                                @if ($addr)
                                    <a href="{{ route('explorer.address', ['address' => $addr]) }}">{{ $addr }}</a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="gold">{{ isset($out['value']) ? ExplorerFormat::koto((float) $out['value']) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
