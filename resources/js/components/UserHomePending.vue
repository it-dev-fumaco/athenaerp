<template>
  <div class="pending-home">
    <header class="pending-home__header">
      <span class="pending-home__header-icon" aria-hidden="true">
        <i class="fas fa-cube"></i>
      </span>
      <div>
        <h1 class="pending-home__title">My Pending Stock Entries</h1>
        <p class="pending-home__subtitle">View and manage your pending Stock Entry Requests and Delivery Requests for Issuance.</p>
      </div>
    </header>

    <div class="pending-home__summary">
      <article class="pending-home__metric pending-home__metric--blue">
        <span class="pending-home__metric-icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
        <div class="pending-home__metric-copy">
          <h2>Pending Stock Entry Requests</h2>
          <p>Waiting for approval / issuance</p>
          <strong>{{ stockEntries.length }}</strong>
        </div>
        <button type="button" class="pending-home__details" @click="showTab('stock')">View Details →</button>
      </article>

      <article class="pending-home__metric pending-home__metric--green">
        <span class="pending-home__metric-icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
        <div class="pending-home__metric-copy">
          <h2>Pending Delivery Requests for Issuance</h2>
          <p>Waiting for delivery / issuance</p>
          <strong>{{ deliveries.length }}</strong>
        </div>
        <button type="button" class="pending-home__details" @click="showTab('delivery')">View Details →</button>
      </article>

      <aside v-if="!reminderHidden" class="pending-home__reminder">
        <span class="pending-home__reminder-icon" aria-hidden="true"><i class="fas fa-info-circle"></i></span>
        <div>
          <h2>Important Reminder</h2>
          <p>Stock Entries (STE) that have not been issued for 1 month will be automatically deleted / cancelled by the system.</p>
        </div>
        <button type="button" class="pending-home__reminder-close" aria-label="Dismiss reminder" @click="dismissReminder">
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </aside>
    </div>

    <div id="pending-home-table" class="pending-home__panel">
      <div class="pending-home__tabs" role="tablist">
        <button
          type="button"
          role="tab"
          :aria-selected="tab === 'stock'"
          :class="{ 'is-active': tab === 'stock' }"
          @click="showTab('stock')"
        >
          <i class="fas fa-file-alt" aria-hidden="true"></i>
          Stock Entry Requests ({{ stockEntries.length }})
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="tab === 'delivery'"
          :class="{ 'is-active': tab === 'delivery' }"
          @click="showTab('delivery')"
        >
          <i class="fas fa-truck" aria-hidden="true"></i>
          Delivery Requests for Issuance ({{ deliveries.length }})
        </button>
      </div>

      <div class="pending-home__filters">
        <label class="pending-home__search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input v-model="search" type="search" :placeholder="searchPlaceholder">
        </label>
        <select v-model="status" aria-label="Status">
          <option value="">All Status</option>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <select v-model="warehouse" aria-label="Warehouse">
          <option value="">All Warehouses</option>
          <option v-for="name in warehouseOptions" :key="name" :value="name">{{ name }}</option>
        </select>
        <label class="pending-home__dates">
          <span>Date Range</span>
          <input v-model="dateFrom" type="date" aria-label="From date">
          <input v-model="dateTo" type="date" aria-label="To date">
        </label>
      </div>

      <p v-if="loading" class="pending-home__state">Loading pending requests…</p>
      <p v-else-if="error" class="pending-home__state pending-home__state--error">{{ error }}</p>
      <p v-else-if="rows.length === 0" class="pending-home__state">{{ emptyMessage }}</p>
      <div v-else class="pending-home__table-wrap">
        <table class="pending-home__table">
          <thead>
            <tr>
              <th>{{ tab === 'delivery' ? 'DR No.' : 'STE No.' }}</th>
              <th>Date Requested</th>
              <th>Requested By</th>
              <th>Warehouse</th>
              <th>Items / Qty</th>
              <th>Status</th>
              <th>Days Pending</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in rows" :key="row.number">
              <td>
                <div class="pending-home__doc">
                  <span class="pending-home__doc-icon" aria-hidden="true">
                    <i :class="row.type_label === 'Delivery Request' ? 'fas fa-truck' : 'fas fa-file-alt'"></i>
                  </span>
                  <span>
                    <strong>{{ row.number }}</strong>
                    <small>{{ row.type_label }}</small>
                  </span>
                </div>
              </td>
              <td>
                <div class="pending-home__date">
                  <strong>{{ row.date_label }}</strong>
                  <small>{{ row.time_label }}</small>
                </div>
              </td>
              <td>
                <div class="pending-home__person">
                  <strong>{{ row.requester_name }}</strong>
                  <small v-if="row.department">{{ row.department }}</small>
                </div>
              </td>
              <td>{{ row.warehouse || '—' }}</td>
              <td>
                <div class="pending-home__qty">
                  <strong>{{ itemLabel(row.item_count) }}</strong>
                  <small v-if="row.qty_label">{{ row.qty_label }}</small>
                </div>
              </td>
              <td>
                <span class="pending-home__badge" :class="'pending-home__badge--' + row.status">{{ row.status_label }}</span>
              </td>
              <td>
                <span class="pending-home__days" :class="dayClass(row.days_pending)">{{ dayLabel(row.days_pending) }}</span>
              </td>
              <td>
                <a class="pending-home__view" :href="row.view_url">View</a>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref } from 'vue';
import axios from 'axios';

const REMINDER_KEY = 'user-home-pending-reminder-dismissed';

const loading = ref(true);
const error = ref('');
const stockEntries = ref([]);
const deliveries = ref([]);
const tab = ref('stock');
const search = ref('');
const status = ref('');
const warehouse = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const reminderHidden = ref(false);

const sourceRows = computed(() => (tab.value === 'delivery' ? deliveries.value : stockEntries.value));

const statusOptions = computed(() => {
  if (tab.value === 'delivery') {
    return [{ value: 'pending_delivery', label: 'Pending Delivery' }];
  }
  return [
    { value: 'pending_approval', label: 'Pending Approval' },
    { value: 'pending_issuance', label: 'Pending Issuance' },
  ];
});

const warehouseOptions = computed(() => {
  const names = new Set();
  sourceRows.value.forEach((row) => {
    (row.warehouses || []).forEach((name) => {
      if (name) names.add(name);
    });
  });
  return [...names].sort((a, b) => a.localeCompare(b));
});

const searchPlaceholder = computed(() => (
  tab.value === 'delivery'
    ? 'Search by DR No., Item, or Remarks...'
    : 'Search by STE No., Item, or Remarks...'
));

const rows = computed(() => {
  const query = search.value.trim().toLowerCase();
  return sourceRows.value.filter((row) => {
    if (query && !String(row.search_text || '').includes(query)) return false;
    if (status.value && row.status !== status.value) return false;
    if (warehouse.value && !(row.warehouses || []).includes(warehouse.value)) return false;
    if (dateFrom.value && row.created_on < dateFrom.value) return false;
    if (dateTo.value && row.created_on > dateTo.value) return false;
    return true;
  });
});

const emptyMessage = computed(() => {
  const noun = tab.value === 'delivery' ? 'delivery requests' : 'stock entry requests';
  if (sourceRows.value.length === 0) {
    return `You have no pending ${noun}.`;
  }
  return `No pending ${noun} match these filters.`;
});

function itemLabel(count) {
  const total = Number(count) || 0;
  return `${total} ${total === 1 ? 'item' : 'items'}`;
}

function dayLabel(days) {
  const total = Number(days) || 0;
  return `${total} ${total === 1 ? 'day' : 'days'}`;
}

function dayClass(days) {
  const total = Number(days) || 0;
  if (total >= 30) return 'pending-home__days--late';
  if (total >= 7) return 'pending-home__days--warn';
  return '';
}

function showTab(next) {
  if (tab.value !== next) {
    tab.value = next;
    status.value = '';
    warehouse.value = '';
  }
  nextTick(() => {
    document.getElementById('pending-home-table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

function dismissReminder() {
  reminderHidden.value = true;
  try {
    sessionStorage.setItem(REMINDER_KEY, '1');
  } catch (_) {}
}

onMounted(async () => {
  try {
    reminderHidden.value = sessionStorage.getItem(REMINDER_KEY) === '1';
  } catch (_) {}

  try {
    const { data } = await axios.get('/user-home/pending');
    stockEntries.value = Array.isArray(data.stock_entries) ? data.stock_entries : [];
    deliveries.value = Array.isArray(data.deliveries) ? data.deliveries : [];
  } catch (_) {
    error.value = 'Pending requests could not be loaded.';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.pending-home {
  box-sizing: border-box;
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 1.25rem 1.25rem 2.5rem;
  color: #0f172a;
}
.pending-home__header {
  display: flex;
  align-items: flex-start;
  gap: 0.85rem;
  margin-bottom: 1.1rem;
}
.pending-home__header-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.5rem;
  height: 2.5rem;
  flex-shrink: 0;
  border-radius: 0.7rem;
  background: #e8f0fe;
  color: #2563eb;
  font-size: 1.05rem;
}
.pending-home__title {
  margin: 0;
  font-size: 1.55rem;
  font-weight: 700;
  line-height: 1.2;
  color: #0f172a;
}
.pending-home__subtitle {
  margin: 0.25rem 0 0;
  font-size: 0.92rem;
  color: #64748b;
}
.pending-home__summary {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.85rem;
  margin-bottom: 1rem;
}
@media (min-width: 900px) {
  .pending-home__summary {
    grid-template-columns: 1fr 1fr;
  }
}
@media (min-width: 1200px) {
  .pending-home__summary {
    grid-template-columns: 1.15fr 1.15fr 0.9fr;
  }
}
.pending-home__metric {
  display: flex;
  align-items: center;
  gap: 0.85rem;
  min-width: 0;
  padding: 1rem 1.05rem;
  border: 1px solid transparent;
  border-radius: 0.9rem;
}
.pending-home__metric--blue {
  background: #eef4ff;
  border-color: #d7e4ff;
}
.pending-home__metric--green {
  background: #e9f8ef;
  border-color: #cfeedd;
}
.pending-home__metric-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 2.6rem;
  height: 2.6rem;
  flex-shrink: 0;
  border-radius: 0.75rem;
  background: #fff;
  font-size: 1.05rem;
}
.pending-home__metric--blue .pending-home__metric-icon { color: #2563eb; }
.pending-home__metric--green .pending-home__metric-icon { color: #16a34a; }
.pending-home__metric-copy {
  flex: 1;
  min-width: 0;
}
.pending-home__metric-copy h2 {
  margin: 0;
  font-size: 0.92rem;
  font-weight: 700;
  color: #0f172a;
}
.pending-home__metric-copy p {
  margin: 0.15rem 0 0;
  font-size: 0.78rem;
  color: #64748b;
}
.pending-home__metric-copy strong {
  display: block;
  margin-top: 0.2rem;
  font-size: 1.7rem;
  line-height: 1;
  color: #0f172a;
}
.pending-home__details {
  flex-shrink: 0;
  padding: 0.4rem 0.7rem;
  border: 1px solid transparent;
  border-radius: 0.55rem;
  background: #fff;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
}
.pending-home__metric--blue .pending-home__details { color: #2563eb; }
.pending-home__metric--green .pending-home__details { color: #15803d; }
.pending-home__reminder {
  display: flex;
  align-items: flex-start;
  gap: 0.65rem;
  padding: 0.9rem 0.85rem;
  border: 1px solid #d6e6fb;
  border-radius: 0.9rem;
  background: #eef6ff;
}
.pending-home__reminder h2 {
  margin: 0;
  font-size: 0.88rem;
  font-weight: 700;
  color: #1d4ed8;
}
.pending-home__reminder p {
  margin: 0.25rem 0 0;
  font-size: 0.78rem;
  line-height: 1.4;
  color: #334155;
}
.pending-home__reminder-icon {
  color: #2563eb;
  font-size: 1rem;
  line-height: 1.2;
}
.pending-home__reminder-close {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  padding: 0;
}
.pending-home__panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.95rem;
  box-shadow: 0 8px 24px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}
.pending-home__tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  padding: 0.75rem 0.9rem 0;
  border-bottom: 1px solid #e2e8f0;
}
.pending-home__tabs button {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: -1px;
  padding: 0.65rem 0.75rem;
  border: 0;
  border-bottom: 2px solid transparent;
  background: transparent;
  color: #64748b;
  font-size: 0.88rem;
  font-weight: 600;
  cursor: pointer;
}
.pending-home__tabs button.is-active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}
.pending-home__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.6rem;
  align-items: center;
  padding: 0.85rem 0.9rem;
}
.pending-home__search {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex: 1 1 220px;
  min-width: 200px;
  padding: 0.45rem 0.7rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.6rem;
  background: #fff;
  color: #94a3b8;
}
.pending-home__search input,
.pending-home__filters select,
.pending-home__dates input {
  border: 1px solid #e2e8f0;
  border-radius: 0.6rem;
  background: #fff;
  color: #0f172a;
  font-size: 0.85rem;
  padding: 0.45rem 0.65rem;
}
.pending-home__search input {
  flex: 1;
  min-width: 0;
  border: 0;
  padding: 0;
  outline: none;
}
.pending-home__dates {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 600;
}
.pending-home__state {
  margin: 0;
  padding: 1.5rem 1rem 1.75rem;
  text-align: center;
  color: #64748b;
}
.pending-home__state--error { color: #b91c1c; }
.pending-home__table-wrap { overflow-x: auto; }
.pending-home__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.84rem;
}
.pending-home__table th {
  padding: 0.7rem 0.85rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.75rem;
  font-weight: 700;
  text-align: left;
  white-space: nowrap;
}
.pending-home__table td {
  padding: 0.8rem 0.85rem;
  border-top: 1px solid #eef2f7;
  vertical-align: middle;
  color: #0f172a;
}
.pending-home__doc,
.pending-home__date,
.pending-home__person,
.pending-home__qty {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.pending-home__doc {
  flex-direction: row;
  align-items: center;
  gap: 0.55rem;
}
.pending-home__doc strong,
.pending-home__date strong,
.pending-home__person strong,
.pending-home__qty strong {
  font-weight: 700;
  color: #0f172a;
}
.pending-home__doc small,
.pending-home__date small,
.pending-home__person small,
.pending-home__qty small {
  color: #64748b;
  font-size: 0.75rem;
}
.pending-home__doc-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.8rem;
  height: 1.8rem;
  border-radius: 0.5rem;
  background: #eef4ff;
  color: #2563eb;
}
.pending-home__badge {
  display: inline-flex;
  align-items: center;
  padding: 0.2rem 0.55rem;
  border-radius: 999px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}
.pending-home__badge--pending_approval { background: #fff4e5; color: #c2410c; }
.pending-home__badge--pending_issuance { background: #e8f1ff; color: #1d4ed8; }
.pending-home__badge--pending_delivery { background: #f3e8ff; color: #7e22ce; }
.pending-home__days {
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
}
.pending-home__days--warn { color: #c2410c; }
.pending-home__days--late { color: #b91c1c; }
.pending-home__view {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 4.2rem;
  padding: 0.35rem 0.75rem;
  border: 1px solid #dbe3ee;
  border-radius: 0.5rem;
  background: #fff;
  color: #334155 !important;
  font-size: 0.8rem;
  font-weight: 700;
  text-decoration: none !important;
}
.pending-home__view:hover { background: #f8fafc; }
</style>
