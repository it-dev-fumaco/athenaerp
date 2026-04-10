<template>
  <div class="phase-out-tagged-table">
    <div v-if="loading" class="phase-out-table-loading">
      <div class="phase-out-spinner" aria-hidden="true" />
    </div>
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
      {{ error }}
    </div>

    <!-- Items page layout: toolbar + styled table -->
    <template v-else-if="itemsPageLayout">
      <div
        ref="toolbarRoot"
        class="phase-out-items-toolbar"
        @click.stop
      >
        <input
          v-model="searchQuery"
          type="search"
          class="phase-out-items-search"
          placeholder="Search by item code or name..."
          autocomplete="off"
          aria-label="Search by item code or name"
          @input="scheduleSearchLoad"
        >
        <div class="phase-out-filter-wrap">
          <button
            type="button"
            class="phase-out-filter-btn"
            :aria-expanded="warehouseMenuOpen"
            @click.stop="toggleWarehouseMenu"
          >
            Warehouse ▾
          </button>
          <ul
            v-show="warehouseMenuOpen"
            class="phase-out-filter-menu"
            role="listbox"
          >
            <li>
              <button type="button" class="phase-out-filter-menu-item" @click="selectWarehouse('')">
                All warehouses
              </button>
            </li>
            <li v-for="w in warehouseOptionsList" :key="w">
              <button type="button" class="phase-out-filter-menu-item" @click="selectWarehouse(w)">
                {{ w }}
              </button>
            </li>
          </ul>
        </div>
        <div class="phase-out-filter-wrap">
          <button
            type="button"
            class="phase-out-filter-btn"
            :aria-expanded="brandMenuOpen"
            @click.stop="toggleBrandMenu"
          >
            Brand ▾
          </button>
          <ul
            v-show="brandMenuOpen"
            class="phase-out-filter-menu"
            role="listbox"
          >
            <li>
              <button type="button" class="phase-out-filter-menu-item" @click="selectBrand('')">
                All brands
              </button>
            </li>
            <li v-for="b in brandOptionsList" :key="b">
              <button type="button" class="phase-out-filter-menu-item" @click="selectBrand(b)">
                {{ b }}
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="phase-out-items-table-shell">
        <div class="phase-out-items-table-scroll">
          <table class="phase-out-items-table">
            <thead>
              <tr>
                <th>Item code</th>
                <th>Name</th>
                <th>Brand</th>
                <th>Entry date</th>
                <th class="phase-out-th-num">Stock</th>
                <th>Warehouse</th>
                <th v-if="linkToProfile" class="phase-out-th-profile" />
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in rows" :key="row.name" class="phase-out-data-row">
                <td class="phase-out-td-code">{{ row.name }}</td>
                <td class="phase-out-td-name" :title="row.item_name">{{ sentenceCaseName(row.item_name) }}</td>
                <td>
                  <span v-if="row.brand" class="phase-out-brand-pill">{{ row.brand }}</span>
                  <span v-else class="phase-out-brand-dash">—</span>
                </td>
                <td class="phase-out-td-entry">{{ formatDate(row.creation) }}</td>
                <td class="phase-out-td-stock">
                  <div class="phase-out-stock-qty">{{ formatQty(row.total_actual_qty) }}</div>
                  <div class="phase-out-stock-unit">Piece(s)</div>
                </td>
                <td class="phase-out-td-wh" :title="row.primary_warehouse || ''">
                  {{ row.primary_warehouse || '—' }}
                </td>
                <td v-if="linkToProfile" class="phase-out-td-profile">
                  <a
                    :href="profileUrl(row.name)"
                    class="phase-out-profile-link"
                    target="_blank"
                    rel="noopener noreferrer"
                  >Profile</a>
                </td>
              </tr>
              <tr v-if="rows.length === 0">
                <td :colspan="linkToProfile ? 7 : 6" class="phase-out-empty-cell phase-out-muted">
                  No items tagged as For Phase Out yet.
                </td>
              </tr>
            </tbody>
          </table>
        </div>
        <div
          v-if="meta && meta.last_page > 1"
          class="phase-out-pagination phase-out-muted"
        >
          <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} items)</span>
          <div class="phase-out-pagination-actions">
            <button
              type="button"
              class="phase-out-page-btn"
              :disabled="meta.current_page <= 1"
              @click="goPage(meta.current_page - 1)"
            >
              Previous
            </button>
            <button
              type="button"
              class="phase-out-page-btn"
              :disabled="meta.current_page >= meta.last_page"
              @click="goPage(meta.current_page + 1)"
            >
              Next
            </button>
          </div>
        </div>
      </div>
    </template>

    <!-- Default layout (e.g. dashboard) -->
    <div v-else class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
          <tr>
            <th class="px-4 py-3">Item code</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Brand</th>
            <th class="px-4 py-3">Entry date</th>
            <th class="px-4 py-3 text-right">Stock</th>
            <th class="px-4 py-3">Warehouse</th>
            <th class="px-4 py-3">Last movement</th>
            <th v-if="linkToProfile" class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.name" class="hover:bg-slate-50">
            <td class="whitespace-nowrap px-4 py-2 font-mono text-slate-900">{{ row.name }}</td>
            <td class="max-w-xs truncate px-4 py-2 text-slate-700" :title="row.item_name">{{ row.item_name }}</td>
            <td class="px-4 py-2 text-slate-600">{{ row.brand || '—' }}</td>
            <td class="whitespace-nowrap px-4 py-2 text-slate-600">{{ formatDate(row.creation) }}</td>
            <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums text-slate-900">
              {{ formatQty(row.total_actual_qty) }} {{ row.stock_uom || '' }}
            </td>
            <td class="max-w-[10rem] truncate px-4 py-2 text-slate-600" :title="row.primary_warehouse || ''">
              {{ row.primary_warehouse || '—' }}
            </td>
            <td class="whitespace-nowrap px-4 py-2 text-slate-600">{{ formatDate(row.last_movement_date) }}</td>
            <td v-if="linkToProfile" class="whitespace-nowrap px-4 py-2">
              <a
                :href="profileUrl(row.name)"
                class="text-sky-700 hover:text-sky-900 hover:underline"
                target="_blank"
                rel="noopener noreferrer"
              >Profile</a>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="linkToProfile ? 8 : 7" class="px-4 py-8 text-center text-slate-500">
              No items tagged as For Phase Out yet.
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="meta && meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
        <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} items)</span>
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-3 py-1 hover:bg-slate-50 disabled:opacity-40"
            :disabled="meta.current_page <= 1"
            @click="goPage(meta.current_page - 1)"
          >
            Previous
          </button>
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-3 py-1 hover:bg-slate-50 disabled:opacity-40"
            :disabled="meta.current_page >= meta.last_page"
            @click="goPage(meta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  perPage: { type: Number, default: 15 },
  linkToProfile: { type: Boolean, default: true },
  itemsPageLayout: { type: Boolean, default: false },
  warehouseOptions: { type: Array, default: () => [] },
  brandOptions: { type: Array, default: () => [] },
});

const emit = defineEmits(['stats-update']);

const loading = ref(true);
const error = ref('');
const rows = ref([]);
const meta = ref(null);
const page = ref(1);

const searchQuery = ref('');
const warehouseFilter = ref('');
const brandFilter = ref('');
const warehouseMenuOpen = ref(false);
const brandMenuOpen = ref(false);
const toolbarRoot = ref(null);

/** Merge summary lists with distinct values from the current result set (summary can be empty). */
const warehouseOptionsList = computed(() => {
  const merged = new Set();
  (props.warehouseOptions || []).forEach((w) => {
    if (w != null && String(w).trim() !== '') {
      merged.add(String(w).trim());
    }
  });
  rows.value.forEach((r) => {
    const pw = r.primary_warehouse;
    if (pw != null && String(pw).trim() !== '') {
      merged.add(String(pw).trim());
    }
  });
  return Array.from(merged).sort((a, b) => a.localeCompare(b));
});

const brandOptionsList = computed(() => {
  const merged = new Set();
  (props.brandOptions || []).forEach((b) => {
    if (b != null && String(b).trim() !== '') {
      merged.add(String(b).trim());
    }
  });
  rows.value.forEach((r) => {
    const br = r.brand;
    if (br != null && String(br).trim() !== '') {
      merged.add(String(br).trim());
    }
  });
  return Array.from(merged).sort((a, b) => a.localeCompare(b));
});

let searchDebounceId = null;

function closeMenus() {
  warehouseMenuOpen.value = false;
  brandMenuOpen.value = false;
}

function toggleWarehouseMenu() {
  brandMenuOpen.value = false;
  warehouseMenuOpen.value = !warehouseMenuOpen.value;
}

function toggleBrandMenu() {
  warehouseMenuOpen.value = false;
  brandMenuOpen.value = !brandMenuOpen.value;
}

function selectWarehouse(w) {
  warehouseFilter.value = w;
  warehouseMenuOpen.value = false;
  load(1);
}

function selectBrand(b) {
  brandFilter.value = b;
  brandMenuOpen.value = false;
  load(1);
}

function onDocClick(e) {
  if (!props.itemsPageLayout) {
    return;
  }
  const el = toolbarRoot.value;
  if (el && !el.contains(e.target)) {
    closeMenus();
  }
}

function scheduleSearchLoad() {
  if (!props.itemsPageLayout) {
    return;
  }
  clearTimeout(searchDebounceId);
  searchDebounceId = setTimeout(() => {
    load(1);
  }, 300);
}

function profileUrl(itemCode) {
  return `/get_item_details/${encodeURIComponent(itemCode)}`;
}

function formatDate(v) {
  if (!v) return '—';
  const d = typeof v === 'string' ? v.slice(0, 10) : v;
  return d || '—';
}

function formatQty(n) {
  if (n === null || n === undefined) return '—';
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

function sentenceCaseName(s) {
  if (s == null || String(s).trim() === '') {
    return '—';
  }
  const t = String(s).trim().toLowerCase();
  return t.charAt(0).toUpperCase() + t.slice(1);
}

async function load(p = 1) {
  loading.value = true;
  error.value = '';
  page.value = p;
  try {
    const params = { per_page: props.perPage, page: p };
    if (props.itemsPageLayout) {
      const s = searchQuery.value.trim();
      if (s) {
        params.search = s;
      }
      if (warehouseFilter.value) {
        params.warehouse = warehouseFilter.value;
      }
      if (brandFilter.value) {
        params.brand = brandFilter.value;
      }
    }
    const { data } = await axios.get('/phase-out/tagged-items', { params });
    rows.value = data.data || [];
    meta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
      per_page: data.per_page,
    };

    if (props.itemsPageLayout) {
      const agg = data.aggregates;
      if (agg && typeof agg === 'object') {
        emit('stats-update', {
          totalItems: Number(data.total) || 0,
          totalStock: Number(agg.total_stock_sum) || 0,
          warehouses: Number(agg.unique_warehouse_count) || 0,
          brands: Number(agg.unique_brand_count) || 0,
        });
      } else {
        const r = rows.value;
        const totalStock = r.reduce((sum, row) => sum + (parseFloat(row.total_actual_qty) || 0), 0);
        const whSet = new Set(r.map((row) => row.primary_warehouse).filter(Boolean));
        const brSet = new Set(
          r.map((row) => row.brand).filter((b) => b != null && String(b).trim() !== '')
        );
        emit('stats-update', {
          totalItems: Number(data.total) || r.length,
          totalStock,
          warehouses: whSet.size,
          brands: brSet.size,
        });
      }
    }
  } catch (e) {
    error.value = 'Could not load tagged items.';
    rows.value = [];
    meta.value = null;
    if (props.itemsPageLayout) {
      emit('stats-update', {
        totalItems: 0,
        totalStock: 0,
        warehouses: 0,
        brands: 0,
      });
    }
  } finally {
    loading.value = false;
  }
}

function goPage(p) {
  load(p);
}

onMounted(() => {
  load(1);
  document.addEventListener('click', onDocClick);
});

onUnmounted(() => {
  document.removeEventListener('click', onDocClick);
  clearTimeout(searchDebounceId);
});

watch(
  () => props.perPage,
  () => {
    load(1);
  }
);

defineExpose({ load });
</script>

<style scoped>
.phase-out-tagged-table {
  width: 100%;
  max-width: 100%;
  box-sizing: border-box;
}

.phase-out-table-loading {
  display: flex;
  justify-content: center;
  padding: 2rem 0;
}

.phase-out-spinner {
  width: 2rem;
  height: 2rem;
  border: 2px solid #cbd5e1;
  border-top-color: #334155;
  border-radius: 9999px;
  animation: phase-out-spin 0.7s linear infinite;
}

@keyframes phase-out-spin {
  to {
    transform: rotate(360deg);
  }
}

.phase-out-muted {
  color: #737373;
}

.phase-out-items-toolbar {
  display: flex;
  flex-direction: row;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
  width: 100%;
  margin-bottom: 16px;
  box-sizing: border-box;
  position: relative;
  z-index: 50;
}

.phase-out-filter-wrap {
  position: relative;
  flex-shrink: 0;
  z-index: 1;
}

.phase-out-filter-menu {
  position: absolute;
  right: 0;
  top: 100%;
  z-index: 1000;
  margin: 4px 0 0;
  max-height: 240px;
  min-width: 12rem;
  overflow: auto;
  list-style: none;
  padding: 4px 0;
  border-radius: 8px;
  border: 0.5px solid #e5e5e5;
  background: #fff;
  box-shadow: 0 4px 12px rgb(0 0 0 / 0.08);
}

.phase-out-items-search {
  flex: 1 1 auto;
  min-width: 12rem;
  width: 0;
  height: 40px;
  padding: 0 14px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  font-size: 14px;
  outline: none;
  box-sizing: border-box;
  background: #fff;
}

.phase-out-items-search:focus {
  border-color: #ccc;
}

.phase-out-filter-btn {
  height: 40px;
  padding: 0 14px;
  border: 1px solid #e0e0e0;
  border-radius: 8px;
  background: #fff;
  font-size: 14px;
  color: #555;
  white-space: nowrap;
  cursor: pointer;
}

.phase-out-filter-btn:hover {
  background: #fafafa;
}

.phase-out-filter-menu-item {
  display: block;
  width: 100%;
  padding: 8px 12px;
  font-size: 13px;
  color: #374151;
  text-align: left;
  border: none;
  background: transparent;
  cursor: pointer;
}

.phase-out-filter-menu-item:hover {
  background: #f5f5f5;
}

.phase-out-items-table-scroll {
  overflow-x: auto;
  width: 100%;
  -webkit-overflow-scrolling: touch;
}

.phase-out-items-table {
  width: 100%;
  min-width: 100%;
  border-collapse: collapse;
  text-align: left;
  table-layout: auto;
}

.phase-out-items-table-shell {
  overflow: hidden;
  width: 100%;
  border-radius: 12px;
  border: 0.5px solid #e5e5e5;
  background: #fff;
  box-sizing: border-box;
  position: relative;
  z-index: 1;
}

.phase-out-empty-cell {
  padding: 2rem 1rem;
  text-align: center;
}

.phase-out-pagination {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 8px;
  padding: 12px 16px;
  border-top: 0.5px solid #e5e5e5;
  font-size: 13px;
}

.phase-out-pagination-actions {
  display: flex;
  flex-wrap: wrap;
  gap: 8px;
}

.phase-out-page-btn {
  border-radius: 6px;
  border: 0.5px solid #ddd;
  background: #fff;
  padding: 6px 12px;
  font-size: 13px;
  cursor: pointer;
}

.phase-out-page-btn:hover:not(:disabled) {
  background: #f9f9f9;
}

.phase-out-page-btn:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

.phase-out-items-table thead th {
  text-transform: uppercase;
  font-size: 12px;
  font-weight: 500;
  color: #999;
  background: transparent;
  padding: 10px 16px;
  border-bottom: 1px solid #e5e5e5;
}

.phase-out-th-num {
  text-align: right;
}

.phase-out-td-stock {
  text-align: right;
  vertical-align: top;
}

.phase-out-td-profile {
  white-space: nowrap;
}

.phase-out-th-profile {
  width: 1%;
}

.phase-out-items-table tbody td {
  padding: 16px;
  vertical-align: top;
  border-bottom: 0.5px solid #f0f0f0;
}

.phase-out-items-table tbody tr:last-child td {
  border-bottom: none;
}

.phase-out-td-code {
  font-size: 14px;
  font-weight: 400;
  color: #171717;
  white-space: nowrap;
}

.phase-out-td-name {
  font-size: 14px;
  font-weight: 400;
  line-height: 1.5;
  max-width: 260px;
  color: #171717;
}

.phase-out-brand-pill {
  display: inline-block;
  background: #f0f0f0;
  border-radius: 6px;
  padding: 3px 10px;
  font-size: 12px;
  color: #666;
}

.phase-out-brand-dash {
  font-size: 14px;
  color: #737373;
}

.phase-out-td-entry {
  font-size: 13px;
  color: #737373;
  white-space: nowrap;
}

.phase-out-stock-qty {
  font-weight: 500;
  color: #171717;
  font-variant-numeric: tabular-nums;
  font-size: 14px;
}

.phase-out-stock-unit {
  margin-top: 2px;
  font-size: 12px;
  color: #737373;
}

.phase-out-td-wh {
  max-width: 12rem;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 13px;
  color: #737373;
}

.phase-out-profile-link {
  font-size: 13px;
  color: #185fa5;
  text-decoration: none;
}

.phase-out-profile-link:hover {
  text-decoration: underline;
}
</style>
