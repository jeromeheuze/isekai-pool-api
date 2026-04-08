<script setup>
import { RouterLink, RouterView } from 'vue-router';

const nav = [
  { to: '/', label: 'Mine', icon: '⛩' },
  { to: '/pools', label: 'Pools', icon: '🏊' },
  { to: '/about', label: 'About', icon: '📖' },
  { to: '/guide', label: 'Guide', icon: '🗺' },
  { to: '/faucet', label: 'Faucet', icon: '💧' },
  { to: '/games', label: 'Games', icon: '🎮' },
];
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
      <header class="mb-6 flex items-center justify-between gap-4 border-b border-slate-800/80 pb-4">
        <h1 class="font-mono text-xl font-bold text-white">Kotominer <span class="text-slate-500">v0.1</span></h1>
      </header>
      <RouterView />
    </main>
  </div>
</template>
