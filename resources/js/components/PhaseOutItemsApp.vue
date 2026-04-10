<template>
  <div class="phase-out-items-page">
    <header class="phase-out-items-header">
      <h1 class="phase-out-items-title">
        Phase-out items
      </h1>
      <p class="phase-out-items-subtitle">
        All inventory items currently tagged as For Phase Out
      </p>
    </header>

    <div class="phase-out-stat-cards">
      <div class="phase-out-stat-block">
        <div class="phase-out-stat-label">
          Total Items
        </div>
        <div class="phase-out-stat-value">
          {{ formatStatInt(tableStats.totalItems) }}
        </div>
      </div>
      <div class="phase-out-stat-block">
        <div class="phase-out-stat-label">
          Total stock
        </div>
        <div class="phase-out-stat-value">
          {{ formatQty(tableStats.totalStock) }}
        </div>
      </div>
      <div class="phase-out-stat-block">
        <div class="phase-out-stat-label">
          Warehouses
        </div>
        <div class="phase-out-stat-value">
          {{ formatStatInt(tableStats.warehouses) }}
        </div>
      </div>
      <div class="phase-out-stat-block">
        <div class="phase-out-stat-label">
          Brands
        </div>
        <div class="phase-out-stat-value">
          {{ formatStatInt(tableStats.brands) }}
        </div>
      </div>
    </div>

    <PhaseOutTaggedTable
      :per-page="25"
      items-page-layout
      :warehouse-options="warehouseOptions"
      :brand-options="brandOptions"
      @stats-update="onTableStats"
    />
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';
import PhaseOutTaggedTable from '@/components/PhaseOutTaggedTable.vue';

/** Mirrors /phase-out/summary for filter dropdown options only (not stat cards). */
const summary = ref({
  by_brand: [],
  warehouses: [],
});

const tableStats = ref({
  totalItems: 0,
  totalStock: 0,
  warehouses: 0,
  brands: 0,
});

const warehouseOptions = computed(() => summary.value.warehouses || []);

const brandOptions = computed(() => (summary.value.by_brand || []).map((r) => r.brand));

function onTableStats(payload) {
  tableStats.value = {
    totalItems: payload.totalItems ?? 0,
    totalStock: payload.totalStock ?? 0,
    warehouses: payload.warehouses ?? 0,
    brands: payload.brands ?? 0,
  };
}

function formatQty(n) {
  if (n === null || n === undefined) {
    return '—';
  }
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

function formatStatInt(n) {
  if (n === null || n === undefined || Number.isNaN(Number(n))) {
    return '—';
  }
  return Number(n).toLocaleString(undefined, { maximumFractionDigits: 0 });
}

async function loadSummaryForOptions() {
  try {
    const { data } = await axios.get('/phase-out/summary');
    summary.value = {
      by_brand: data.by_brand ?? [],
      warehouses: data.warehouses ?? [],
    };
  } catch {
    summary.value = {
      by_brand: [],
      warehouses: [],
    };
  }
}

onMounted(() => {
  loadSummaryForOptions();
});
</script>

<style scoped>
.phase-out-items-page {
  box-sizing: border-box;
  width: 100%;
  max-width: 100%;
  padding: 1.5rem;
}

.phase-out-stat-cards {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: flex-start;
  gap: 24px;
  width: 100%;
  margin-bottom: 1.5rem;
}

.phase-out-items-header {
  margin-bottom: 1.5rem;
}

.phase-out-items-title {
  font-size: 26px;
  font-weight: 500;
  color: #171717;
  margin: 0;
  line-height: 1.25;
}

.phase-out-items-subtitle {
  margin: 0.35rem 0 0;
  font-size: 13px;
  color: #737373;
}

.phase-out-stat-block {
  flex: 1 1 0;
  min-width: 0;
  padding: 0;
  background: transparent;
  border: none;
  box-shadow: none;
}

@media (max-width: 640px) {
  .phase-out-stat-block {
    flex: 1 1 calc(50% - 12px);
    min-width: 8rem;
  }
}

.phase-out-stat-label {
  font-size: 13px;
  color: #737373;
  margin-bottom: 4px;
}

.phase-out-stat-value {
  font-size: 28px;
  font-weight: 500;
  color: #171717;
  font-variant-numeric: tabular-nums;
  line-height: 1.2;
}
</style>
