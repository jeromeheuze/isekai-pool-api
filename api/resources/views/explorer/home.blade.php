@extends('explorer.layout')

@section('title', 'KOTO Explorer')

@section('content')
    @php
        $d = $data;
    @endphp
    <h1>KOTO <span style="color:var(--accent)">Explorer</span></h1>
    <p class="muted">RPC-backed · <span class="mono">explorer.isekai-pool.com</span></p>

    <form class="search" method="get" action="{{ route('explorer.search') }}" role="search">
        <input type="text" name="q" placeholder="Block height, block hash, txid, or address…" autocomplete="off" value="{{ request('q') }}">
        <button type="submit">Search</button>
    </form>

    <div class="bar">
        <span><strong>Block height</strong> {{ number_format($d['tip']) }}</span>
        <span><strong>Network</strong> {{ \App\Support\ExplorerFormat::networkHs($d['network_hashps']) }}</span>
        <span><strong>Difficulty</strong> {{ number_format($d['difficulty'], 2) }}</span>
        <span><strong>Peers</strong> {{ $d['peers'] }}</span>
    </div>

    <h2>Latest blocks</h2>
    <div class="table-scroll">
        <table>
            <thead>
                <tr>
                    <th>Height</th>
                    <th>Hash</th>
                    <th>Time</th>
                    <th>Miner</th>
                    <th>Txs</th>
                    <th>Size</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($d['rows'] as $row)
                    <tr>
                        <td><a href="{{ route('explorer.block', ['heightOrHash' => $row['height']]) }}">{{ number_format($row['height']) }}</a></td>
                        <td class="mono"><a href="{{ route('explorer.block', ['heightOrHash' => $row['hash']]) }}" title="{{ $row['hash'] }}">{{ \App\Support\ExplorerFormat::shortHash($row['hash']) }}</a></td>
                        <td>{{ \App\Support\ExplorerFormat::ago($row['time']) }}</td>
                        <td>{{ $row['miner'] }}</td>
                        <td>{{ $row['txs'] }}</td>
                        <td>{{ \App\Support\ExplorerFormat::bytes($row['size']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="muted" style="margin-top:2rem;">Data from the public KOTO node. Shielded (z) transactions show limited detail by design.</p>
@endsection
