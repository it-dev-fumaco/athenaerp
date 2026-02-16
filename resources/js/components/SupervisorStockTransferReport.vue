<template>
  <div>
    <ul class="nav nav-pills m-0" role="tablist">
      <li v-for="tab in tabs" :key="tab.purpose" class="nav-item mr-1 border rounded">
        <button
          type="button"
          class="nav-link font-responsive"
          :class="{ active: activeTab === tab.purpose }"
          @click="switchTab(tab.purpose)"
        >
          {{ tab.label }}
          <span v-if="tab.countEl" class="badge badge-warning ml-1">{{ counts[tab.purpose] ?? '0' }}</span>
        </button>
      </li>
    </ul>

    <div class="row p-2">
      <div class="col-12">
        <!-- Store Transfer filters -->
        <form v-show="activeTab === 'Store Transfer'" id="stock-transfer-filter" class="mb-2" @submit.prevent="applyFilters('Store Transfer')">
          <div class="row p-2">
            <div class="col-3">
              <input v-model="filters.storeTransfer.q" type="text" name="q" class="form-control" placeholder="Search" style="font-size: 12px;" />
            </div>
            <div class="col-3">
              <select v-model="filters.storeTransfer.source_warehouse" name="source_warehouse" class="form-control">
                <option value="">Source Warehouse</option>
                <option v-for="s in storeOptions" :key="s.id" :value="s.id">{{ s.text }}</option>
              </select>
            </div>
            <div class="col-3">
              <select v-model="filters.storeTransfer.target_warehouse" name="target_warehouse" class="form-control">
                <option value="">Target Warehouse</option>
                <option v-for="s in storeOptions" :key="s.id" :value="s.id">{{ s.text }}</option>
              </select>
            </div>
            <div class="col-2">
              <select v-model="filters.storeTransfer.status" name="status" class="form-control" style="font-size: 12px;">
                <option value="">Select Status</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-1">
              <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </form>

        <!-- Pull Out filters -->
        <form v-show="activeTab === 'Pull Out'" id="pull-out-filter" class="mb-2" @submit.prevent="applyFilters('Pull Out')">
          <div class="row p-2">
            <div class="col-3">
              <input v-model="filters.pullOut.q" type="text" name="q" class="form-control" placeholder="Search" style="font-size: 12px;" />
            </div>
            <div class="col-3">
              <select v-model="filters.pullOut.source_warehouse" name="source_warehouse" class="form-control">
                <option value="">Source Warehouse</option>
                <option v-for="s in storeOptions" :key="s.id" :value="s.id">{{ s.text }}</option>
              </select>
            </div>
            <div class="col-2">
              <select v-model="filters.pullOut.status" name="status" class="form-control" style="font-size: 12px;">
                <option value="">Select Status</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-1">
              <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </form>

        <!-- Item Return filters -->
        <form v-show="activeTab === 'Item Return'" id="item-return-filter" class="mb-2" @submit.prevent="applyFilters('Item Return')">
          <div class="row p-2">
            <div class="col-3">
              <input v-model="filters.itemReturn.q" type="text" name="q" class="form-control" placeholder="Search" style="font-size: 12px;" />
            </div>
            <div class="col-3">
              <select v-model="filters.itemReturn.target_warehouse" name="target_warehouse" class="form-control">
                <option value="">Target Warehouse</option>
                <option v-for="s in storeOptions" :key="s.id" :value="s.id">{{ s.text }}</option>
              </select>
            </div>
            <div class="col-2">
              <select v-model="filters.itemReturn.status" name="status" class="form-control" style="font-size: 12px;">
                <option value="">Select Status</option>
                <option value="Pending">Pending</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-1">
              <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </form>

        <!-- Damaged Items filters -->
        <form v-show="activeTab === 'Damaged Items'" id="damaged-items-filter" class="mb-2" @submit.prevent="applyFilters('Damaged Items')">
          <div class="row p-2">
            <div class="col-3">
              <input v-model="filters.damaged.search" type="text" name="search" class="form-control" placeholder="Search Item" style="font-size: 10pt;" />
            </div>
            <div class="col-3">
              <select v-model="filters.damaged.store" name="store" class="form-control">
                <option value="">Select Store</option>
                <option value="For Approval">For Approval</option>
                <option value="Approved">Approved</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-1">
              <button type="submit" class="btn btn-info btn-block"><i class="fas fa-search"></i></button>
            </div>
          </div>
        </form>
      </div>
    </div>

    <div id="report-content" class="p-1">
      <div v-if="loading" class="d-flex justify-content-center align-items-center p-5">
        <div class="spinner-border"></div>
      </div>
      <div
        v-else
        class="tab-content-html"
        v-html="tableHtml"
        @click="onContentClick"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const tabs = [
  { purpose: 'Store Transfer', label: 'Stock-to-Store Transfer', countEl: true },
  { purpose: 'Pull Out', label: 'Item Pull Out', countEl: true },
  { purpose: 'Item Return', label: 'Item Return', countEl: true },
  { purpose: 'Damaged Items', label: 'Damaged Item List', countEl: false },
];

const activeTab = ref('Store Transfer');
const tableHtml = ref('');
const loading = ref(true);
const storeOptions = ref([]);
const counts = reactive({
  'Store Transfer': '0',
  'Pull Out': '0',
  'Item Return': '0',
});

const filters = reactive({
  storeTransfer: { q: '', source_warehouse: '', target_warehouse: '', status: '' },
  pullOut: { q: '', source_warehouse: '', status: '' },
  itemReturn: { q: '', target_warehouse: '', status: '' },
  damaged: { search: '', store: '' },
});

async function fetchStores() {
  try {
    const { data } = await axios.get('/consignment_stores', { params: { q: '' } });
    storeOptions.value = Array.isArray(data) ? data : [];
  } catch {
    storeOptions.value = [];
  }
}

async function fetchCounts() {
  const purposes = ['Store Transfer', 'Pull Out', 'Item Return'];
  for (const purpose of purposes) {
    try {
      const { data } = await axios.get(`/countStockTransfer/${encodeURIComponent(purpose)}`);
      counts[purpose] = typeof data === 'number' ? String(data) : (typeof data === 'string' ? data : String(data ?? '0'));
    } catch {
      counts[purpose] = '0';
    }
  }
}

function getFilterParams(purpose) {
  if (purpose === 'Damaged Items') {
    return { search: filters.damaged.search, store: filters.damaged.store };
  }
  if (purpose === 'Store Transfer') {
    return {
      q: filters.storeTransfer.q,
      source_warehouse: filters.storeTransfer.source_warehouse,
      target_warehouse: filters.storeTransfer.target_warehouse,
      status: filters.storeTransfer.status,
    };
  }
  if (purpose === 'Pull Out') {
    return {
      q: filters.pullOut.q,
      source_warehouse: filters.pullOut.source_warehouse,
      status: filters.pullOut.status,
    };
  }
  if (purpose === 'Item Return') {
    return {
      q: filters.itemReturn.q,
      target_warehouse: filters.itemReturn.target_warehouse,
      status: filters.itemReturn.status,
    };
  }
  return {};
}

async function loadReport(purpose, page = 1) {
  loading.value = true;
  try {
    const params = { page, ...getFilterParams(purpose) };
    if (purpose === 'Damaged Items') {
      const { data } = await axios.get('/damaged_items_list', { params, responseType: 'text' });
      tableHtml.value = data;
    } else {
      const query = new URLSearchParams({ purpose, page, ...params });
      const { data } = await axios.get(`/stocks_report/list?${query}`, { responseType: 'text' });
      tableHtml.value = data;
    }
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to load.';
    tableHtml.value = `<div class="alert alert-danger m-2">${message}</div>`;
  } finally {
    loading.value = false;
  }
}

function applyFilters(purpose) {
  loadReport(purpose, 1);
}

function switchTab(purpose) {
  activeTab.value = purpose;
  loadReport(purpose, 1);
  if (purpose !== 'Damaged Items') {
    fetchCounts();
  }
}

function onContentClick(event) {
  const paginationLink = event.target.closest('#consignment-stock-entry-pagination a, #damaged-items-pagination a');
  if (paginationLink && paginationLink.href) {
    event.preventDefault();
    const url = new URL(paginationLink.href);
    const page = url.searchParams.get('page') || 1;
    const purposeAttr = paginationLink.getAttribute('data-consignment-purpose');
    if (purposeAttr) {
      loadReport(purposeAttr, page);
    } else if (activeTab.value === 'Damaged Items') {
      loadReport('Damaged Items', page);
    }
    return;
  }
}

onMounted(async () => {
  await fetchStores();
  await fetchCounts();
  await loadReport(activeTab.value, 1);
});
</script>
