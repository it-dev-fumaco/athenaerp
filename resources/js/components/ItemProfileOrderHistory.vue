<template>
  <div class="oh-wrap">
    <div v-if="loading && !loadedOnce" class="container d-flex justify-content-center align-items-center p-5">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>

    <div v-else-if="error" class="text-center text-muted p-4">
      Failed to load order history.
    </div>

    <template v-else-if="loadedOnce">
      <div class="oh-header">
        <div class="oh-title-row">
          <h2 class="oh-title">Order History</h2>
          <span class="oh-badge">Sales Orders</span>
        </div>
        <p class="oh-subtitle">List of unique customers who ordered this item based on Sales Order records.</p>
      </div>

      <div class="oh-kpi-grid">
        <div class="oh-kpi-card">
          <div class="oh-kpi-icon"><i class="fas fa-users"></i></div>
          <div class="oh-kpi-body">
            <div class="oh-kpi-label">Unique Customers</div>
            <div class="oh-kpi-value">{{ formatInt(summary.unique_customers) }}</div>
          </div>
        </div>
        <div class="oh-kpi-card">
          <div class="oh-kpi-icon"><i class="fas fa-file-invoice"></i></div>
          <div class="oh-kpi-body">
            <div class="oh-kpi-label">Total Sales (Vat Exclusive)</div>
            <div class="oh-kpi-value">{{ formatMoney(summary.total_sales) }}</div>
          </div>
        </div>
        <div class="oh-kpi-card">
          <div class="oh-kpi-icon"><i class="fas fa-box"></i></div>
          <div class="oh-kpi-body">
            <div class="oh-kpi-label">Total Quantity Ordered</div>
            <div class="oh-kpi-value">{{ formatQty(summary.total_qty) }}</div>
          </div>
        </div>
        <div class="oh-kpi-card">
          <div class="oh-kpi-icon"><i class="fas fa-tags"></i></div>
          <div class="oh-kpi-body">
            <div class="oh-kpi-label">Average Order Value (Vat Exclusive)</div>
            <div class="oh-kpi-value">{{ formatMoney(summary.average_order_value) }}</div>
          </div>
        </div>
        <div class="oh-kpi-card">
          <div class="oh-kpi-icon"><i class="fas fa-calendar-alt"></i></div>
          <div class="oh-kpi-body">
            <div class="oh-kpi-label">Last Order Date</div>
            <div class="oh-kpi-value">{{ formatDate(summary.last_order_date) }}</div>
          </div>
        </div>
        <div class="oh-kpi-card">
          <div class="oh-kpi-icon"><i class="fas fa-trophy"></i></div>
          <div class="oh-kpi-body">
            <div class="oh-kpi-label">Top Customer</div>
            <div class="oh-kpi-value oh-kpi-value-sm">{{ topCustomerName }}</div>
            <div v-if="summary.top_customer" class="oh-kpi-sub">
              {{ formatMoney(summary.top_customer.total_sales) }} ({{ formatPct(summary.top_customer.percentage) }})
            </div>
          </div>
        </div>
      </div>

      <div class="oh-toolbar">
        <div class="oh-filters">
          <div class="oh-field">
            <label class="oh-field-label" for="oh-date-range">Date Range</label>
            <div class="oh-select-wrap">
              <select id="oh-date-range" v-model="datePreset" class="oh-select" @change="onDatePresetChange">
                <option value="all">All Time</option>
                <option value="this_month">This Month</option>
                <option value="last_month">Last Month</option>
                <option value="last_6m">Last 6 Months</option>
                <option value="ytd">Year to Date</option>
                <option value="custom">Custom</option>
              </select>
              <i class="far fa-calendar-alt oh-select-icon"></i>
            </div>
          </div>
          <div v-if="datePreset === 'custom'" class="oh-field oh-field-dates">
            <label class="oh-field-label" for="oh-date-from">From</label>
            <input id="oh-date-from" v-model="customFrom" type="date" class="oh-select" @change="applyFilters">
            <label class="oh-field-label" for="oh-date-to">To</label>
            <input id="oh-date-to" v-model="customTo" type="date" class="oh-select" @change="applyFilters">
          </div>
          <div class="oh-field">
            <label class="oh-field-label" for="oh-customer">Customer</label>
            <select id="oh-customer" v-model="customer" class="oh-select" @change="applyFilters">
              <option value="">All Customers</option>
              <option v-for="opt in customers" :key="opt.id" :value="opt.id">{{ opt.name }}</option>
            </select>
          </div>
        </div>
      </div>

      <div class="oh-table-wrap" :class="{ 'oh-loading': loading }">
        <table class="oh-table">
          <thead>
            <tr>
              <th class="oh-col-num">#</th>
              <th>
                <button type="button" class="oh-sort" @click="toggleSort('customer')">
                  Customer
                  <i v-if="sort === 'customer'" class="fas" :class="sortIcon"></i>
                </button>
              </th>
              <th>
                <button type="button" class="oh-sort" @click="toggleSort('total_sales')">
                  Total Sales (Vat Exclusive)
                  <i class="fas" :class="sort === 'total_sales' ? sortIcon : 'fa-sort'"></i>
                </button>
              </th>
              <th>
                <button type="button" class="oh-sort" @click="toggleSort('total_qty')">
                  Total Quantity Ordered{{ stockUomLabel }}
                  <i v-if="sort === 'total_qty'" class="fas" :class="sortIcon"></i>
                </button>
              </th>
              <th>
                <button type="button" class="oh-sort" @click="toggleSort('avg_selling_price')">
                  Average Selling Price (Vat Exclusive)
                  <i v-if="sort === 'avg_selling_price'" class="fas" :class="sortIcon"></i>
                </button>
              </th>
              <th>
                <button type="button" class="oh-sort" @click="toggleSort('last_order_date')">
                  Last Order Date
                  <i v-if="sort === 'last_order_date'" class="fas" :class="sortIcon"></i>
                </button>
              </th>
              <th>Last Order #</th>
              <th>
                <button type="button" class="oh-sort" @click="toggleSort('number_of_orders')">
                  No. of Orders
                  <i v-if="sort === 'number_of_orders'" class="fas" :class="sortIcon"></i>
                </button>
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="rows.length === 0">
              <td colspan="8" class="oh-empty">No sales order records found.</td>
            </tr>
            <tr v-for="(row, index) in rows" :key="row.customer">
              <td class="oh-col-num">{{ rowNumber(index) }}</td>
              <td>
                <a v-if="customerUrl(row)" :href="customerUrl(row)" target="_blank" rel="noopener" class="oh-link">
                  {{ row.customer_name }}
                </a>
                <span v-else>{{ row.customer_name }}</span>
              </td>
              <td>{{ formatMoney(row.total_sales) }}</td>
              <td>{{ formatNumber(row.total_qty) }}</td>
              <td>{{ formatMoney(row.avg_selling_price) }}</td>
              <td>{{ formatDate(row.last_order_date) }}</td>
              <td>
                <a v-if="orderUrl(row)" :href="orderUrl(row)" target="_blank" rel="noopener" class="oh-link">
                  {{ row.last_order_no }}
                </a>
                <span v-else>{{ row.last_order_no || '—' }}</span>
              </td>
              <td>{{ formatInt(row.number_of_orders) }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="meta.total > 0" class="oh-footer">
        <div class="oh-entries">
          Showing {{ meta.from || 0 }} to {{ meta.to || 0 }} of {{ meta.total }} entries
        </div>
        <div class="oh-pager">
          <label class="oh-per-page">
            rows per page
            <select v-model.number="perPage" class="oh-select oh-select-sm" @change="onPerPageChange">
              <option :value="10">10</option>
              <option :value="25">25</option>
              <option :value="50">50</option>
            </select>
          </label>
          <nav class="oh-pages">
            <button
              v-for="p in pageNumbers"
              :key="'p'+p"
              type="button"
              class="oh-page-btn"
              :class="{ 'oh-page-active': p === meta.current_page }"
              @click="goToPage(p)"
            >{{ p }}</button>
            <button
              type="button"
              class="oh-page-btn"
              :disabled="meta.current_page >= meta.last_page"
              @click="goToPage(meta.current_page + 1)"
            >&gt;</button>
          </nav>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, onUnmounted, ref } from 'vue';
import axios from 'axios';

const props = defineProps({
  itemCode: { type: String, required: true },
});

const loading = ref(false);
const loadedOnce = ref(false);
const error = ref(false);
const rows = ref([]);
const customers = ref([]);
const stockUom = ref('');
const erpWebBaseUrl = ref('');
const summary = ref({
  unique_customers: 0,
  total_sales: 0,
  total_qty: 0,
  average_order_value: 0,
  last_order_date: null,
  top_customer: null,
});
const meta = ref({
  current_page: 1,
  last_page: 1,
  per_page: 10,
  total: 0,
  from: 0,
  to: 0,
});

const datePreset = ref('all');
const customFrom = ref('');
const customTo = ref('');
const customer = ref('');
const sort = ref('total_sales');
const direction = ref('desc');
const perPage = ref(10);
const page = ref(1);

let loadSeq = 0;

const sortIcon = computed(() => (direction.value === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down'));
const stockUomLabel = computed(() => (stockUom.value ? ` (${stockUom.value})` : ''));
const topCustomerName = computed(() => summary.value.top_customer?.customer_name || '—');

const pageNumbers = computed(() => {
  const last = meta.value.last_page || 1;
  const current = meta.value.current_page || 1;
  const windowSize = 5;
  let start = Math.max(1, current - Math.floor(windowSize / 2));
  let end = Math.min(last, start + windowSize - 1);
  start = Math.max(1, end - windowSize + 1);
  const pages = [];
  for (let i = start; i <= end; i += 1) pages.push(i);
  return pages;
});

function pad(n) {
  return String(n).padStart(2, '0');
}

function toYmd(date) {
  return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

function dateRangeFromPreset() {
  if (datePreset.value === 'all') {
    return { date_from: '', date_to: '' };
  }
  if (datePreset.value === 'custom') {
    return { date_from: customFrom.value || '', date_to: customTo.value || '' };
  }

  const now = new Date();
  const today = toYmd(now);

  if (datePreset.value === 'this_month') {
    return { date_from: toYmd(new Date(now.getFullYear(), now.getMonth(), 1)), date_to: today };
  }
  if (datePreset.value === 'last_month') {
    const start = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const end = new Date(now.getFullYear(), now.getMonth(), 0);
    return { date_from: toYmd(start), date_to: toYmd(end) };
  }
  if (datePreset.value === 'last_6m') {
    const start = new Date(now.getFullYear(), now.getMonth() - 5, 1);
    return { date_from: toYmd(start), date_to: today };
  }
  if (datePreset.value === 'ytd') {
    return { date_from: toYmd(new Date(now.getFullYear(), 0, 1)), date_to: today };
  }

  return { date_from: '', date_to: '' };
}

function queryParams() {
  const range = dateRangeFromPreset();
  const params = {
    page: page.value,
    per_page: perPage.value,
    sort: sort.value,
    direction: direction.value,
  };
  if (range.date_from) params.date_from = range.date_from;
  if (range.date_to) params.date_to = range.date_to;
  if (customer.value) params.customer = customer.value;
  return params;
}

async function load() {
  const seq = ++loadSeq;
  loading.value = true;
  error.value = false;
  try {
    const { data } = await axios.get(`/item_order_history/${encodeURIComponent(props.itemCode)}`, {
      headers: { Accept: 'application/json' },
      params: queryParams(),
    });
    if (seq !== loadSeq) return;
    summary.value = data.summary || summary.value;
    rows.value = data.rows || [];
    meta.value = data.meta || meta.value;
    customers.value = data.customers || [];
    stockUom.value = data.stock_uom || '';
    erpWebBaseUrl.value = data.erp_web_base_url || '';
    loadedOnce.value = true;
  } catch (_) {
    if (seq !== loadSeq) return;
    error.value = true;
    loadedOnce.value = true;
  } finally {
    if (seq === loadSeq) loading.value = false;
  }
}

function applyFilters() {
  page.value = 1;
  load();
}

function onDatePresetChange() {
  if (datePreset.value !== 'custom') {
    applyFilters();
  }
}

function onPerPageChange() {
  page.value = 1;
  load();
}

function goToPage(target) {
  const next = Math.max(1, Math.min(meta.value.last_page || 1, parseInt(target, 10) || 1));
  if (next === page.value) return;
  page.value = next;
  load();
}

function toggleSort(column) {
  if (sort.value === column) {
    direction.value = direction.value === 'desc' ? 'asc' : 'desc';
  } else {
    sort.value = column;
    direction.value = column === 'customer' ? 'asc' : 'desc';
  }
  page.value = 1;
  load();
}

function rowNumber(index) {
  const from = meta.value.from || 1;
  return from + index;
}

function formatMoney(value) {
  const n = Number(value || 0);
  return `₱ ${n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
}

function formatNumber(value) {
  const n = Number(value || 0);
  return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatInt(value) {
  return Number(value || 0).toLocaleString('en-US');
}

function formatQty(value) {
  const qty = formatNumber(value);
  return stockUom.value ? `${qty} ${stockUom.value}` : qty;
}

function formatPct(value) {
  const n = Number(value || 0);
  return `${n.toFixed(2)}%`;
}

function formatDate(value) {
  if (!value) return '—';
  const raw = String(value);
  if (/^\d{4}-\d{2}-\d{2}/.test(raw)) {
    const [y, m, d] = raw.slice(0, 10).split('-').map(Number);
    return new Date(y, m - 1, d).toLocaleDateString('en-US', {
      month: 'short',
      day: 'numeric',
      year: 'numeric',
    });
  }
  const parsed = new Date(raw);
  if (Number.isNaN(parsed.getTime())) return '—';
  return parsed.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function customerUrl(row) {
  if (!erpWebBaseUrl.value || !row.customer) return '';
  return `${erpWebBaseUrl.value}/app/customer/${encodeURIComponent(row.customer)}`;
}

function orderUrl(row) {
  if (!erpWebBaseUrl.value || !row.last_order_no) return '';
  return `${erpWebBaseUrl.value}/app/sales-order/${encodeURIComponent(row.last_order_no)}`;
}

function onRefresh() {
  load();
}

onMounted(() => {
  document.addEventListener('item-profile-order-history-refresh', onRefresh);
  const pane = document.getElementById('order-history');
  if (pane?.classList.contains('active')) {
    load();
  }
});

onUnmounted(() => {
  document.removeEventListener('item-profile-order-history-refresh', onRefresh);
});
</script>

<style scoped>
.oh-wrap {
  padding: 1.25rem 1.5rem 1.5rem;
  background: #fff;
}

.oh-header {
  margin-bottom: 1.25rem;
}

.oh-title-row {
  display: flex;
  align-items: center;
  gap: 0.65rem;
}

.oh-title {
  margin: 0;
  font-size: 1.5rem;
  font-weight: 700;
  color: #1f2937;
  line-height: 1.2;
}

.oh-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.15rem 0.65rem;
  border-radius: 999px;
  background: #2563eb;
  color: #fff;
  font-size: 0.7rem;
  font-weight: 600;
  letter-spacing: 0.01em;
}

.oh-subtitle {
  margin: 0.35rem 0 0;
  font-size: 0.875rem;
  color: #6b7280;
}

.oh-kpi-grid {
  display: grid;
  grid-template-columns: repeat(6, minmax(0, 1fr));
  gap: 0.75rem;
  margin-bottom: 1.25rem;
}

.oh-kpi-card {
  display: flex;
  gap: 0.7rem;
  align-items: flex-start;
  padding: 0.9rem 0.85rem;
  background: #fff;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
  min-width: 0;
}

.oh-kpi-icon {
  flex-shrink: 0;
  width: 32px;
  height: 32px;
  border-radius: 8px;
  background: #eff6ff;
  color: #2563eb;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.85rem;
}

.oh-kpi-body {
  min-width: 0;
}

.oh-kpi-label {
  font-size: 0.7rem;
  color: #6b7280;
  line-height: 1.3;
}

.oh-kpi-value {
  margin-top: 0.2rem;
  font-size: 1.05rem;
  font-weight: 700;
  color: #111827;
  line-height: 1.25;
  word-break: break-word;
}

.oh-kpi-value-sm {
  font-size: 0.92rem;
}

.oh-kpi-sub {
  margin-top: 0.15rem;
  font-size: 0.72rem;
  color: #6b7280;
}

.oh-toolbar {
  display: flex;
  align-items: flex-end;
  gap: 1rem;
  flex-wrap: wrap;
  margin-bottom: 0.85rem;
}

.oh-filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.75rem;
  align-items: flex-end;
}

.oh-field {
  display: flex;
  flex-direction: column;
  gap: 0.25rem;
}

.oh-field-dates {
  flex-direction: row;
  align-items: flex-end;
  gap: 0.5rem;
}

.oh-field-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: #6b7280;
  margin: 0;
}

.oh-select-wrap {
  position: relative;
}

.oh-select {
  appearance: none;
  min-width: 180px;
  height: 36px;
  padding: 0 2rem 0 0.75rem;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #fff;
  font-size: 0.85rem;
  color: #111827;
}

.oh-select-sm {
  min-width: 64px;
  height: 30px;
  padding-right: 1.5rem;
}

.oh-select-icon {
  position: absolute;
  right: 0.7rem;
  top: 50%;
  transform: translateY(-50%);
  color: #6b7280;
  pointer-events: none;
  font-size: 0.8rem;
}

.oh-table-wrap {
  overflow-x: auto;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
}

.oh-table-wrap.oh-loading {
  opacity: 0.65;
}

.oh-table {
  width: 100%;
  margin: 0;
  border-collapse: collapse;
  font-size: 0.82rem;
}

.oh-table th,
.oh-table td {
  padding: 0.7rem 0.75rem;
  border-bottom: 1px solid #e5e7eb;
  text-align: left;
  white-space: nowrap;
  color: #111827;
}

.oh-table thead th {
  background: #f9fafb;
  font-weight: 600;
  color: #374151;
  border-bottom: 1px solid #e5e7eb;
}

.oh-table tbody tr:last-child td {
  border-bottom: 0;
}

.oh-col-num {
  width: 3rem;
  color: #6b7280;
}

.oh-sort {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  padding: 0;
  border: 0;
  background: transparent;
  font: inherit;
  font-weight: 600;
  color: inherit;
  cursor: pointer;
}

.oh-sort .fa-sort {
  color: #9ca3af;
  font-size: 0.7rem;
}

.oh-sort .fa-arrow-up,
.oh-sort .fa-arrow-down {
  color: #2563eb;
  font-size: 0.7rem;
}

.oh-link {
  color: #2563eb;
  font-weight: 600;
}

.oh-link:hover {
  text-decoration: underline;
}

.oh-empty {
  text-align: center !important;
  color: #6b7280 !important;
  font-weight: 600;
  padding: 1.5rem 0.75rem !important;
}

.oh-footer {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 1rem;
  flex-wrap: wrap;
  margin-top: 0.85rem;
}

.oh-entries {
  font-size: 0.8rem;
  color: #6b7280;
}

.oh-pager {
  display: flex;
  align-items: center;
  gap: 0.75rem;
}

.oh-per-page {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.8rem;
  color: #6b7280;
  margin: 0;
}

.oh-pages {
  display: flex;
  gap: 0.25rem;
}

.oh-page-btn {
  min-width: 32px;
  height: 32px;
  padding: 0 0.45rem;
  border: 1px solid #e5e7eb;
  border-radius: 6px;
  background: #fff;
  color: #374151;
  font-size: 0.8rem;
}

.oh-page-btn:hover:not(:disabled):not(.oh-page-active) {
  background: #f9fafb;
}

.oh-page-active {
  background: #2563eb;
  border-color: #2563eb;
  color: #fff;
}

.oh-page-btn:disabled {
  opacity: 0.45;
  cursor: not-allowed;
}

@media (max-width: 1199px) {
  .oh-kpi-grid {
    grid-template-columns: repeat(3, minmax(0, 1fr));
  }
}

@media (max-width: 767px) {
  .oh-wrap {
    padding: 1rem;
  }

  .oh-kpi-grid {
    grid-template-columns: repeat(2, minmax(0, 1fr));
  }

  .oh-select {
    min-width: 140px;
  }
}
</style>
