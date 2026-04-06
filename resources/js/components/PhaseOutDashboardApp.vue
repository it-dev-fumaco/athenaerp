<template>
  <div class="phase-out-dashboard space-y-6 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Inventory Phase-Out Management</h1>
        <p class="mt-1 text-sm text-slate-600">
          Track items tagged For Phase Out and identify candidates for tagging.
        </p>
      </div>
      <button
        type="button"
        class="phase-out-bulk-tag-btn inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2"
        @click="bulkTagModalOpen = true"
      >
        Bulk tag as For Phase Out
      </button>
    </div>

    <div v-if="summaryLoading" class="flex justify-center py-6">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700" />
    </div>
    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">Phase-Out items</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ summary.tagged_count }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">Total stock (units)</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ formatQty(summary.total_units) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1">
          <p class="text-sm font-medium text-slate-500">By brand (value)</p>
          <p class="mt-2 text-sm text-slate-600">See charts below for distribution.</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-sm font-semibold text-slate-800">Items by brand</h2>
          <div class="mt-4 space-y-3">
            <div v-for="row in brandBars" :key="row.brand" class="flex items-center gap-3 text-sm">
              <span class="w-28 shrink-0 truncate text-slate-600" :title="row.brand">{{ row.brand }}</span>
              <div class="h-6 min-w-0 flex-1 overflow-hidden rounded bg-slate-100">
                <div
                  class="h-full rounded bg-sky-600 transition-all"
                  :style="{ width: row.pct + '%' }"
                />
              </div>
              <span class="w-10 shrink-0 text-right tabular-nums text-slate-700">{{ row.item_count }}</span>
            </div>
            <p v-if="brandBars.length === 0" class="text-sm text-slate-500">No data.</p>
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-sm font-semibold text-slate-800">Stock value by brand</h2>
          <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row">
            <div
              class="h-40 w-40 shrink-0 rounded-full border border-slate-200 shadow-inner"
              :style="donutStyle"
              role="img"
              :aria-label="'Stock value distribution'"
            />
            <ul class="min-w-0 flex-1 space-y-2 text-sm">
              <li v-for="(row, idx) in donutLegend" :key="row.brand" class="flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-sm" :style="{ background: row.color }" />
                <span class="truncate text-slate-700">{{ row.brand }}</span>
                <span class="ml-auto tabular-nums text-slate-600">{{ formatMoney(row.stock_value) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </template>

    <section>
      <h2 class="mb-3 text-sm font-semibold text-slate-800">Phase-Out items</h2>
      <PhaseOutTaggedTable ref="taggedTableRef" :per-page="8" />
    </section>

    <PhaseOutBulkTagModal v-model="bulkTagModalOpen" @success="onBulkTagSuccess" />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import PhaseOutTaggedTable from '@/components/PhaseOutTaggedTable.vue';
import PhaseOutBulkTagModal from '@/components/PhaseOutBulkTagModal.vue';

const summaryLoading = ref(true);
const summary = ref({
  tagged_count: 0,
  total_units: 0,
  total_stock_value: 0,
  by_brand: [],
});

const taggedTableRef = ref(null);
const bulkTagModalOpen = ref(false);

const DONUT_COLORS = ['#0ea5e9', '#8b5cf6', '#f59e0b', '#10b981', '#ec4899', '#64748b'];

function formatMoney(n) {
  if (n === null || n === undefined) return '—';
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(
    Number(n)
  );
}

function formatQty(n) {
  if (n === null || n === undefined) return '—';
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

const brandBars = computed(() => {
  const rows = summary.value.by_brand || [];
  const max = Math.max(1, ...rows.map((r) => r.item_count));
  return rows.map((r) => ({
    brand: r.brand,
    item_count: r.item_count,
    pct: (r.item_count / max) * 100,
  }));
});

const donutLegend = computed(() => {
  const rows = summary.value.by_brand || [];
  return rows.map((r, i) => ({
    brand: r.brand,
    stock_value: r.stock_value,
    color: DONUT_COLORS[i % DONUT_COLORS.length],
  }));
});

const donutStyle = computed(() => {
  const rows = summary.value.by_brand || [];
  const total = rows.reduce((s, r) => s + (Number(r.stock_value) || 0), 0);
  if (total <= 0) {
    return { background: '#e2e8f0' };
  }
  let acc = 0;
  const parts = [];
  rows.forEach((r, i) => {
    const v = Number(r.stock_value) || 0;
    const pct = (v / total) * 100;
    const start = acc;
    acc += pct;
    const color = DONUT_COLORS[i % DONUT_COLORS.length];
    parts.push(`${color} ${start}% ${acc}%`);
  });
  return { background: `conic-gradient(${parts.join(', ')})` };
});

async function loadSummary() {
  summaryLoading.value = true;
  try {
    const { data } = await axios.get('/phase-out/summary');
    summary.value = data;
  } catch {
    summary.value = { tagged_count: 0, total_units: 0, total_stock_value: 0, by_brand: [] };
  } finally {
    summaryLoading.value = false;
  }
}

async function onBulkTagSuccess() {
  await loadSummary();
  taggedTableRef.value?.load(1);
}

onMounted(() => {
  loadSummary();
});
</script>

<style scoped>
.phase-out-bulk-tag-btn {
  background: linear-gradient(135deg, #38bdf8 0%, #2563eb 45%, #1d4ed8 100%);
  box-shadow: 0 10px 25px -5px rgb(37 99 235 / 0.45);
}
.phase-out-bulk-tag-btn:hover {
  background: linear-gradient(135deg, #0ea5e9 0%, #1d4ed8 50%, #1e40af 100%);
  box-shadow: 0 14px 32px -6px rgb(37 99 235 / 0.55);
  transform: translateY(-1px);
}
.phase-out-bulk-tag-btn:active {
  transform: translateY(0);
}
</style>
