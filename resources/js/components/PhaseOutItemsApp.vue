<template>
  <div class="space-y-4 p-6">
    <div>
      <h1 class="text-xl font-semibold text-slate-800">Phase-Out Items</h1>
      <p class="mt-1 text-sm text-slate-600">
        All inventory items currently tagged as For Phase Out.
      </p>
    </div>
    <PhaseOutTaggedTable :per-page="25" />
  </div>
</template>

<script setup>
import PhaseOutTaggedTable from '@/components/PhaseOutTaggedTable.vue';
</script>
