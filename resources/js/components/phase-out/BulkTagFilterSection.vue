<template>
  <div class="space-y-4">
    <!-- Info banner -->
    <div class="flex gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm leading-relaxed text-slate-900 shadow-sm">
      <span class="mt-0.5 shrink-0 text-[#1976D2]" aria-hidden="true">
        <svg class="phase-out-ico-md h-5 w-5" width="20" height="20" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
          <path
            fill-rule="evenodd"
            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
            clip-rule="evenodd"
          />
        </svg>
      </span>
      <p>
        <template v-if="candidatesMeta">
          We identified <strong>{{ candidatesMeta.total }}</strong> items that may be suitable for phase-out based on your criteria below.
        </template>
        <template v-else-if="candidatesLoading">Finding candidates…</template>
        <template v-else>Adjust filters and click <strong>Apply filters</strong> to load candidates.</template>
      </p>
    </div>

    <!-- Filter rows -->
    <div class="rounded-xl border border-slate-200 bg-white shadow-sm">
      <!-- Brand -->
      <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 sm:gap-4 sm:px-5 sm:py-3.5">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-[#1976D2] bg-[#1976D2] text-xs font-bold text-white" aria-hidden="true">✓</span>
        <span class="w-28 shrink-0 text-sm font-bold text-slate-800 sm:w-32">Brand</span>
        <input
          :value="filters.brand"
          type="text"
          class="bulk-tag-field min-h-10 min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm text-slate-900 shadow-sm focus:border-[#1976D2] focus:outline-none focus:ring-2 focus:ring-[#1976D2]/25"
          placeholder="Any brand"
          autocomplete="organization"
          @input="patch({ brand: $event.target.value })"
        >
      </div>

      <!-- Entry date + days idle -->
      <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 sm:gap-4 sm:px-5 sm:py-3.5">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-[#1976D2] bg-[#1976D2] text-xs font-bold text-white" aria-hidden="true">✓</span>
        <span class="w-28 shrink-0 text-sm font-bold text-slate-800 sm:w-32">Entry date</span>
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-3 sm:flex-nowrap">
          <input
            :value="filters.created_before"
            type="date"
            class="bulk-tag-field min-h-10 min-w-[10rem] flex-1 rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-[#1976D2] focus:outline-none focus:ring-2 focus:ring-[#1976D2]/25"
            @input="patch({ created_before: $event.target.value })"
          >
          <input
            :value="filters.no_movement_days ?? ''"
            type="number"
            min="1"
            placeholder="Days idle"
            class="bulk-tag-field min-h-10 w-full min-w-[7rem] rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm sm:max-w-[9rem] focus:border-[#1976D2] focus:outline-none focus:ring-2 focus:ring-[#1976D2]/25"
            @input="patch({ no_movement_days: $event.target.value ? Number($event.target.value) : null })"
          >
        </div>
      </div>

      <!-- No stock movement -->
      <div class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-4 py-3 sm:gap-4 sm:px-5 sm:py-3.5">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-[#1976D2] bg-[#1976D2] text-xs font-bold text-white" aria-hidden="true">✓</span>
        <span class="w-28 shrink-0 text-sm font-bold leading-tight text-slate-800 sm:w-36">No stock movement</span>
        <div class="flex min-w-0 flex-1 flex-wrap items-center gap-2">
          <span class="text-sm text-slate-600">for</span>
          <input
            :value="filters.months"
            type="number"
            min="1"
            max="120"
            class="bulk-tag-field min-h-10 w-24 rounded-lg border border-slate-300 bg-white px-3 text-sm shadow-sm focus:border-[#1976D2] focus:outline-none focus:ring-2 focus:ring-[#1976D2]/25"
            @input="patch({ months: Number($event.target.value) || 12 })"
          >
          <span class="text-sm text-slate-600">months (if days empty)</span>
        </div>
      </div>

      <!-- Excess stock -->
      <div class="flex flex-wrap items-start gap-3 px-4 py-3 sm:items-center sm:gap-4 sm:px-5 sm:py-3.5">
        <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-[#1976D2] bg-[#1976D2] text-xs font-bold text-white" aria-hidden="true">✓</span>
        <span class="w-28 shrink-0 pt-0.5 text-sm font-bold leading-snug text-slate-800 sm:w-32 sm:pt-0">Excess stock</span>
        <label class="flex min-w-0 flex-1 cursor-pointer items-start gap-3 text-sm text-slate-800 sm:items-center">
          <input
            :checked="filters.excess_stock_only"
            type="checkbox"
            class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300 text-[#1976D2] focus:ring-[#1976D2] sm:mt-0"
            @change="patch({ excess_stock_only: $event.target.checked })"
          >
          <span class="min-w-0 leading-snug">Stock level &gt; 2× reorder level (any warehouse)</span>
        </label>
      </div>

      <div class="flex justify-end border-t border-slate-100 bg-slate-50/80 px-4 py-3 sm:px-5">
        <button
          type="button"
          class="min-h-10 rounded-lg border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:opacity-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400"
          :disabled="candidatesLoading"
          @click="$emit('apply-filters')"
        >
          Apply filters
        </button>
      </div>
    </div>

    <div v-if="candidatesLoadError" class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900" role="alert">
      {{ candidatesLoadError }}
    </div>
  </div>
</template>

<script setup>
defineProps({
  filters: { type: Object, required: true },
  candidatesMeta: { type: Object, default: null },
  candidatesLoading: { type: Boolean, default: false },
  candidatesLoadError: { type: String, default: null },
});

const emit = defineEmits(['update:filters', 'apply-filters']);

function patch(partial) {
  emit('update:filters', partial);
}
</script>
