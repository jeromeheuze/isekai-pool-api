<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

defineOptions({ name: 'Benchmark' });

const api = window.kotominer;

const poolUrl = ref('stratum+tcp://koto.isekai-pool.com:3301');
const wallet = ref('');
const threads = ref(4);
const warmupSec = ref(12);
const measureSec = ref(35);

const running = ref(false);
const statusLine = ref('');
const currentExe = ref('');
const results = ref([]);
const lastError = ref('');

let unsubProgress;

function fmtHps(h) {
  if (h == null || Number.isNaN(h)) return '—';
  if (h >= 1e6) return `${(h / 1e6).toFixed(2)} MH/s`;
  if (h >= 1e3) return `${(h / 1e3).toFixed(2)} kH/s`;
  return `${Math.round(h)} H/s`;
}

const sortedResults = computed(() => {
  const rows = [...results.value];
  rows.sort((a, b) => (b.hps || 0) - (a.hps || 0));
  return rows;
});

const best = computed(() => sortedResults.value.find((r) => r.hps != null && r.hps > 0 && !r.error));

onMounted(async () => {
  const s = await api.getSettings();
  poolUrl.value = s.pool_url || poolUrl.value;
  wallet.value = s.wallet_address || '';
  threads.value = typeof s.threads === 'number' ? s.threads : 4;

  unsubProgress = api.onBenchmarkProgress((msg) => {
    if (msg.phase === 'exe-start') {
      currentExe.value = msg.basename || '';
      statusLine.value = `Running ${msg.basename}…`;
    }
    if (msg.phase === 'sample' && msg.basename) {
      statusLine.value = `${msg.basename}: ${fmtHps(msg.maxHps ?? msg.hps)} (peak so far)`;
    }
  });
});

onUnmounted(() => {
  unsubProgress?.();
});

async function runBenchmark() {
  lastError.value = '';
  results.value = [];
  statusLine.value = '';
  currentExe.value = '';
  running.value = true;
  try {
    const r = await api.minerBenchmarkRun({
      pool_url: poolUrl.value.trim(),
      wallet_address: wallet.value.trim(),
      threads: threads.value,
      warmupSec: warmupSec.value,
      measureSec: measureSec.value,
    });
    if (!r.ok) {
      lastError.value = r.error || 'Benchmark failed';
      return;
    }
    results.value = r.results || [];
    if (best.value) {
      statusLine.value = `Fastest on pool: ${best.value.basename} (${fmtHps(best.value.hps)})`;
    } else {
      statusLine.value = 'Benchmark finished — no valid hashrate samples.';
    }
  } catch (e) {
    lastError.value = String(e.message || e);
  } finally {
    running.value = false;
    currentExe.value = '';
  }
}

async function cancelBenchmark() {
  await api.minerBenchmarkCancel();
  statusLine.value = 'Cancelling…';
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <div>
      <h2 class="font-mono text-lg text-white">Pool benchmark</h2>
      <p class="mt-2 text-sm text-slate-400">
        Runs each bundled miner build against <strong class="text-slate-300">your pool and wallet</strong> for a short window,
        records the best “Total” hashrate after a warmup. Stop mining on the Mine tab first. This uses real stratum connections.
      </p>
    </div>

    <section class="rounded-xl border border-slate-800 bg-kotominer-card p-6 space-y-4">
      <label class="block font-mono text-xs uppercase tracking-wide text-slate-500">Pool (stratum)</label>
      <input
        v-model="poolUrl"
        type="text"
        :disabled="running"
        class="w-full rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white focus:border-kotominer-violet focus:outline-none disabled:opacity-50"
      />

      <label class="block font-mono text-xs uppercase tracking-wide text-slate-500">Wallet / worker (-u)</label>
      <input
        v-model="wallet"
        type="text"
        :disabled="running"
        placeholder="k1…address.worker"
        class="w-full rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white focus:border-kotominer-violet focus:outline-none disabled:opacity-50"
      />

      <label class="block font-mono text-xs uppercase tracking-wide text-slate-500">Threads (-t)</label>
      <input
        v-model.number="threads"
        type="number"
        min="1"
        max="256"
        :disabled="running"
        class="w-full max-w-xs rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white"
      />

      <div class="grid gap-4 sm:grid-cols-2">
        <div>
          <label class="block font-mono text-xs uppercase tracking-wide text-slate-500">Warmup (seconds)</label>
          <input
            v-model.number="warmupSec"
            type="number"
            min="5"
            max="120"
            :disabled="running"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white"
          />
          <p class="mt-1 text-[11px] text-slate-500">Ignore samples during connect / ramp-up.</p>
        </div>
        <div>
          <label class="block font-mono text-xs uppercase tracking-wide text-slate-500">Measure (seconds)</label>
          <input
            v-model.number="measureSec"
            type="number"
            min="15"
            max="180"
            :disabled="running"
            class="mt-1 w-full rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white"
          />
          <p class="mt-1 text-[11px] text-slate-500">Best “Total” line wins per executable.</p>
        </div>
      </div>

      <div class="flex flex-wrap gap-3 pt-2">
        <button
          type="button"
          class="rounded-xl bg-kotominer-violet px-6 py-2.5 font-mono text-sm font-semibold text-white shadow-lg shadow-kotominer-violet/20 hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40"
          :disabled="running || wallet.trim().length < 20 || poolUrl.trim().length < 8 || threads < 1"
          @click="runBenchmark"
        >
          {{ running ? 'Running…' : 'Run benchmark' }}
        </button>
        <button
          v-if="running"
          type="button"
          class="rounded-xl border border-amber-500/50 bg-amber-500/10 px-6 py-2.5 font-mono text-sm text-amber-200 hover:bg-amber-500/20"
          @click="cancelBenchmark"
        >
          Cancel
        </button>
      </div>

      <p v-if="statusLine" class="font-mono text-xs text-kotominer-gold">{{ statusLine }}</p>
      <p v-if="lastError" class="rounded-lg border border-red-500/30 bg-red-500/10 p-2 font-mono text-xs text-red-300">{{ lastError }}</p>
    </section>

    <section v-if="results.length > 0" class="rounded-xl border border-slate-800 bg-kotominer-bg/50 p-4">
      <h3 class="font-mono text-xs uppercase tracking-wide text-slate-500">Results (best Total hashrate per build)</h3>
      <table class="mt-3 w-full border-collapse font-mono text-sm">
        <thead>
          <tr class="border-b border-slate-800 text-left text-slate-500">
            <th class="py-2 pr-4">Build</th>
            <th class="py-2 pr-4">Best rate</th>
            <th class="py-2">Note</th>
          </tr>
        </thead>
        <tbody>
          <tr
            v-for="row in sortedResults"
            :key="row.basename"
            class="border-b border-slate-800/80 text-slate-300"
            :class="best?.basename === row.basename ? 'bg-kotominer-violet/10' : ''"
          >
            <td class="py-2 pr-4 text-kotominer-gold">{{ row.basename }}</td>
            <td class="py-2 pr-4">{{ fmtHps(row.hps) }}</td>
            <td class="py-2 text-xs text-slate-500">{{ row.error || '—' }}</td>
          </tr>
        </tbody>
      </table>
      <p v-if="best" class="mt-3 text-xs text-slate-500">
        Tip: Kotominer auto-picks the <em class="text-slate-400">first</em> build that runs on your CPU (help probe), not necessarily the fastest on-pool — use this table to compare.
      </p>
    </section>
  </div>
</template>
