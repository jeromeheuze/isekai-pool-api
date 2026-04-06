@extends('explorer.layout')

@php
    use App\Support\ExplorerFormat;
@endphp

@section('title', 'Address '.$address)

@section('content')
    <h1>Address</h1>
    <p class="mono" style="word-break:break-all;">{{ $address }}</p>

    @if (! empty($payload['error']))
        <p class="alert" style="margin-top:1rem;">RPC: {{ $payload['error'] }}</p>
        <p class="muted">The node wallet may not track this address. Full history requires running KOTO Core locally.</p>
    @else
        <dl class="grid" style="margin-top:1.5rem;">
            <dt>Total received (0 conf.)</dt>
            <dd class="gold">{{ ExplorerFormat::koto((float) ($payload['recv0'] ?? 0)) }} KOTO</dd>
            <dt>Total received (≥1 conf.)</dt>
            <dd class="gold">{{ ExplorerFormat::koto((float) ($payload['recv1'] ?? 0)) }} KOTO</dd>
            <dt>Balance (wallet UTXOs)</dt>
            <dd class="gold">{{ ExplorerFormat::koto((float) ($payload['balance'] ?? 0)) }} KOTO</dd>
        </dl>

        <p class="muted" style="margin-top:1.5rem;">Approximate balance from <code class="mono">listunspent</code> against the node wallet. Full transaction history requires an indexer or KOTO Core.</p>

        <h2>Unspent outputs</h2>
        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Txid</th>
                        <th>Vout</th>
                        <th>Amount</th>
                        <th>Confirmations</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($payload['utxos'] ?? [] as $u)
                        @if (! is_array($u))
                            @continue
                        @endif
                        <tr>
                            <td class="mono"><a href="{{ route('explorer.tx', ['txid' => $u['txid'] ?? '']) }}">{{ isset($u['txid']) ? ExplorerFormat::shortHash($u['txid']) : '—' }}</a></td>
                            <td>{{ $u['vout'] ?? '—' }}</td>
                            <td class="gold">{{ isset($u['amount']) ? ExplorerFormat::koto((float) $u['amount']) : '—' }}</td>
                            <td>{{ $u['confirmations'] ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No UTXOs in the node wallet for this address.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif
@endsection
