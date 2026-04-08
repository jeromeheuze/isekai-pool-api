<script setup>
function openEarn() {
  window.kotominer.openExternal('https://koto.isekai-pool.com/earn');
}
</script>

<template>
  <div class="mx-auto max-w-lg space-y-4 text-center">
    <h2 class="font-mono text-lg text-white">Earn hub</h2>
    <p class="text-sm text-slate-400">
      The spec embeds the full earn hub in a BrowserView. This scaffold opens the hub in your default browser (same as
      “Open in Browser” in the spec).
    </p>
    <button
      type="button"
      class="rounded-xl bg-kotominer-violet px-6 py-3 font-mono text-sm font-semibold text-white hover:brightness-110"
      @click="openEarn"
    >
      Open koto.isekai-pool.com/earn
    </button>
    <p class="text-xs text-slate-600">Wallet from Kotominer can be pasted into the earn page when you arrive.</p>
  </div>
</template>
