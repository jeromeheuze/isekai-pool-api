<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { RouterLink, RouterView } from 'vue-router';

const nav = [
  { to: '/', label: 'Mine', icon: '⛩' },
  { to: '/benchmark', label: 'Benchmark', icon: '📊' },
  { to: '/pools', label: 'Pools', icon: '🏊' },
  { to: '/about', label: 'About', icon: '📖' },
  { to: '/guide', label: 'Guide', icon: '🗺' },
  { to: '/faucet', label: 'Faucet', icon: '💧' },
  { to: '/games', label: 'Games', icon: '🎮' },
  { to: '/settings', label: 'Settings', icon: '⚙' },
];

const updateReady = ref(false);
let unsubDownloaded;

onMounted(() => {
  unsubDownloaded = window.kotominer.onUpdateDownloaded(() => {
    updateReady.value = true;
  });
});

onUnmounted(() => {
  unsubDownloaded?.();
});

async function restartToUpdate() {
  await window.kotominer.quitAndInstall();
}
</script>

<template>
  <div class="flex h-full min-h-0">
    <aside
      class="flex w-44 shrink-0 flex-col border-r border-slate-800/80 bg-kotominer-card px-3 py-4"
    >
      <div class="mb-6 px-1 font-mono text-xs text-slate-500">by isekai-pool.com</div>
      <nav class="flex flex-col gap-0.5">
        <RouterLink
          v-for="item in nav"
          :key="item.to"
          :to="item.to"
          class="rounded-lg px-3 py-2 font-mono text-sm text-slate-400 transition hover:bg-kotominer-elevated hover:text-white"
          active-class="!bg-kotominer-violet/20 !text-kotominer-violet"
        >
          <span class="mr-2">{{ item.icon }}</span>{{ item.label }}
        </RouterLink>
      </nav>
    </aside>
    <main class="min-h-0 min-w-0 flex-1 overflow-auto p-6">
      <header class="mb-6 flex flex-wrap items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
        <h1 class="font-mono text-xl font-bold text-white">Kotominer <span class="text-slate-500">v0.1</span></h1>
        <div
          v-if="updateReady"
          class="flex items-center gap-2 rounded-lg border border-kotominer-violet/40 bg-kotominer-violet/10 px-3 py-2 font-mono text-xs text-kotominer-violet"
        >
          <span>Update downloaded — restart to install.</span>
          <button type="button" class="rounded bg-kotominer-violet px-2 py-1 text-white hover:brightness-110" @click="restartToUpdate">
            Restart
          </button>
        </div>
      </header>
      <router-view v-slot="{ Component }">
        <keep-alive :include="['Mining', 'Benchmark']">
          <component :is="Component" />
        </keep-alive>
      </router-view>
    </main>
  </div>
</template>
