<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';

const api = window.kotominer;

const wallet = ref('');
const poolUrl = ref('stratum+tcp://koto.isekai-pool.com:3301');
const threads = ref(4);
const recommended = ref(4);
const mining = ref(false);
const hashrate = ref(0);
const shares = ref({ accepted: 0, rejected: 0 });
const logs = ref([]);
const minerError = ref('');
const paths = ref({
  platformDir: '',
  resourcesDir: '',
  resolved: null,
  present: false,
  selectedBasename: null,
  cachedBasename: null,
  windowsCandidates: [],
});
const restoreMsg = ref('');

let unsubStats;
let unsubLog;
let unsubErr;

function fmtHash(h) {
  if (h >= 1e6) return `${(h / 1e6).toFixed(2)} MH/s`;
  if (h >= 1e3) return `${(h / 1e3).toFixed(2)} kH/s`;
  return `${Math.round(h)} H/s`;
}

const canStart = computed(() => {
  return (
    !mining.value &&
    paths.value.present &&
    wallet.value.trim().length >= 20 &&
    poolUrl.value.trim().length > 8 &&
    threads.value >= 1
  );
});

onMounted(async () => {
  const s = await api.getSettings();
  wallet.value = s.wallet_address || '';
  poolUrl.value = s.pool_url || poolUrl.value;
  threads.value = s.threads ?? 4;

  const cpu = await api.getCpuInfo();
  recommended.value = cpu.recommended_threads;
  if (!s.threads) {
    threads.value = cpu.recommended_threads;
  }

  paths.value = await api.getMinerPaths();

  unsubStats = api.onMinerStats((s) => {
    hashrate.value = s.hashrate || 0;
    shares.value = { ...s.shares };
  });
  unsubLog = api.onMinerLog((line) => {
    logs.value = [line, ...logs.value].slice(0, 40);
  });
  unsubErr = api.onMinerError((msg) => {
    minerError.value = msg;
    mining.value = false;
  });
});

onUnmounted(() => {
  unsubStats?.();
  unsubLog?.();
  unsubErr?.();
});

async function persist() {
  await api.setSettings({
    wallet_address: wallet.value.trim(),
    pool_url: poolUrl.value.trim(),
    threads: threads.value,
  });
}

async function startMining() {
  minerError.value = '';
  await persist();
  paths.value = await api.getMinerPaths();
  const res = await api.minerStart({
    pool_url: poolUrl.value.trim(),
    wallet_address: wallet.value.trim(),
    threads: threads.value,
    solo: false,
  });
  if (!res.ok) {
    minerError.value = res.error || 'Failed to start';
    return;
  }
  mining.value = true;
}

async function stopMining() {
  await api.minerStop();
  mining.value = false;
}

async function refreshPaths() {
  paths.value = await api.getMinerPaths();
}

async function restoreMiner() {
  restoreMsg.value = 'Checking…';
  const r = await api.restoreMiner();
  restoreMsg.value = r.ok ? `Restored to ${r.path}` : r.error || 'Restore failed';
  await refreshPaths();
}
</script>

<template>
  <div class="mx-auto max-w-3xl space-y-6">
    <div
      v-if="!paths.present"
      class="rounded-lg border border-amber-500/40 bg-amber-500/10 p-4 text-sm text-amber-200/90"
    >
      <p class="font-medium text-amber-100">No working miner found</p>
      <p class="mt-1 text-xs text-amber-200/70">
        On <strong>Windows</strong>, unpack the full KotoDevelopers zip into
        <code class="font-mono text-amber-100">resources/{{ paths.platformDir }}/</code>
        (all <code class="font-mono">minerd-*.exe</code> + DLLs). Kotominer picks the best build your CPU can run (see
        <code class="font-mono">resources/README.md</code>). Optional: place a single
        <code class="font-mono">cpuminer-koto.exe</code> under your profile <code class="font-mono">bin</code> folder to override.
      </p>
      <button
        type="button"
        class="mt-3 rounded-lg bg-amber-500/20 px-3 py-1.5 font-mono text-xs text-amber-100 hover:bg-amber-500/30"
        @click="restoreMiner"
      >
        Restore miner (GitHub manifest)
      </button>
      <button
        type="button"
        class="ml-2 rounded-lg border border-slate-600 px-3 py-1.5 font-mono text-xs text-slate-300 hover:bg-slate-800"
        @click="refreshPaths"
      >
        Refresh paths
      </button>
      <p v-if="restoreMsg" class="mt-2 font-mono text-xs text-slate-400">{{ restoreMsg }}</p>
    </div>

    <p
      v-else
      class="rounded-lg border border-slate-800/80 bg-kotominer-elevated/40 px-3 py-2 font-mono text-xs text-slate-400"
    >
      Miner:
      <span class="text-kotominer-gold">{{ paths.selectedBasename || '—' }}</span>
      <span class="text-slate-600"> · </span>
      <span class="break-all text-slate-500">{{ paths.resourcesDir }}</span>
    </p>

    <section class="rounded-xl border border-slate-800 bg-kotominer-card p-6">
      <label class="block font-mono text-xs uppercase tracking-wide text-slate-500">KOTO wallet</label>
      <input
        v-model="wallet"
        type="text"
        autocomplete="off"
        placeholder="k1…"
        class="mt-1 w-full rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white placeholder:text-slate-600 focus:border-kotominer-violet focus:outline-none"
        @change="persist"
      />

      <label class="mt-4 block font-mono text-xs uppercase tracking-wide text-slate-500">Pool (stratum)</label>
      <input
        v-model="poolUrl"
        type="text"
        class="mt-1 w-full rounded-lg border border-slate-700 bg-kotominer-bg px-3 py-2 font-mono text-sm text-white focus:border-kotominer-violet focus:outline-none"
        @change="persist"
      />

      <label class="mt-4 block font-mono text-xs uppercase tracking-wide text-slate-500">
        CPU threads (recommended {{ recommended }})
      </label>
      <input
        v-model.number="threads"
        type="range"
        min="1"
        max="64"
        class="mt-2 w-full accent-kotominer-violet"
        @change="persist"
      />
      <div class="font-mono text-sm text-kotominer-gold">{{ threads }} threads</div>
    </section>

    <div class="flex flex-wrap items-center gap-3">
      <button
        v-if="!mining"
        type="button"
        class="rounded-xl bg-kotominer-violet px-8 py-3 font-mono text-sm font-semibold text-white shadow-lg shadow-kotominer-violet/20 hover:brightness-110 disabled:cursor-not-allowed disabled:opacity-40"
        :disabled="!canStart"
        @click="startMining"
      >
        ▶ Start mining
      </button>
      <button
        v-else
        type="button"
        class="rounded-xl border border-red-500/50 bg-red-500/10 px-8 py-3 font-mono text-sm font-semibold text-red-300 hover:bg-red-500/20"
        @click="stopMining"
      >
        Stop
      </button>
      <span class="font-mono text-kotominer-gold">{{ fmtHash(hashrate) }}</span>
      <span class="font-mono text-sm text-slate-500">
        shares {{ shares.accepted }} / {{ shares.rejected }}
      </span>
    </div>

    <p v-if="minerError" class="rounded-lg border border-red-500/30 bg-red-500/10 p-3 font-mono text-xs text-red-300 whitespace-pre-wrap">
      {{ minerError }}
    </p>

    <section class="rounded-xl border border-slate-800 bg-kotominer-bg/50 p-4">
      <h2 class="font-mono text-xs uppercase tracking-wide text-slate-500">Miner log</h2>
      <pre class="mt-2 max-h-48 overflow-auto font-mono text-[11px] leading-relaxed text-slate-400">{{ logs.join('\n') || '—' }}</pre>
    </section>
  </div>
</template>
