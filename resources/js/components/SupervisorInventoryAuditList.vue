<template>
  <div class="row">
    <div class="col-md-8 offset-md-1">
      <div class="card card-info card-outline">
        <div class="card-body p-2">
          <div class="d-flex flex-row">
            <div class="p-1 col-3">
              <small class="d-block">Recent Period:</small>
              <span class="d-block font-weight-bold text-center">{{ displayedData.recent_period }}</span>
            </div>
            <div class="p-1 col-3" style="border-left: 10px solid #2E86C1;">
              <small class="d-block" style="font-size: 8pt;">Stores Submitted</small>
              <h5 class="d-block font-weight-bold m-0">{{ displayedData.stores_submitted }}</h5>
            </div>
            <div class="p-1 col-3" style="border-left: 10px solid #E67E22;">
              <small class="d-block" style="font-size: 8pt;">Stores Pending</small>
              <h5 class="d-block font-weight-bold m-0">{{ displayedData.stores_pending }}</h5>
            </div>
            <div class="p-1 col-3" style="border-left: 10px solid #27AE60;">
              <small class="d-block">Total Sales</small>
              <h4 class="d-block font-weight-bold m-0">{{ displayedData.total_sales }}</h4>
            </div>
          </div>
          <form id="inventory-audit-history-form" class="mt-2">
            <div class="d-flex flex-row align-items-center mt-2">
              <div class="p-1 col-5">
                <select v-model="filters.submitted.store" name="store" class="form-control form-control-sm" @change="loadSubmitted(1)">
                  <option value="">Select Store</option>
                  <option v-for="s in storeOptions" :key="s.id" :value="s.id">{{ s.text }}</option>
                </select>
              </div>
              <div class="p-1 col-3 col-lg-3">
                <select v-model="filters.submitted.year" name="year" class="form-control form-control-sm" @change="loadSubmitted(1)">
                  <option v-for="y in selectYear" :key="y" :value="y">{{ y }}</option>
                </select>
              </div>
              <div class="p-1 col-3 col-lg-3">
                <select v-model="filters.submitted.promodiser" name="promodiser" class="form-control form-control-sm" @change="loadSubmitted(1)">
                  <option value="">Select a Promodiser</option>
                  <option v-for="p in promodisers" :key="p" :value="String(p)">{{ p }}</option>
                </select>
              </div>
              <div class="p-1 col-1">
                <button type="button" class="btn btn-secondary btn-sm" @click="refreshSubmitted"><i class="fas fa-undo"></i></button>
              </div>
            </div>
          </form>
          <div v-if="submittedLoading" class="d-flex justify-content-center align-items-center p-5" style="height: 852px;"><div class="spinner-border"></div></div>
          <div v-else id="submitted-inventory-audit-el" class="p-1" style="height: 852px;" v-html="submittedHtml" @click="onSubmittedClick"></div>
        </div>
      </div>
    </div>
    <div class="col-md-2">
      <div class="card card-warning card-outline">
        <div class="card-body p-2">
          <h6 class="text-center font-weight-bolder text-uppercase">Pending for Submission</h6>
          <form id="pending-inventory-audit-filter-form" class="mt-1 mb-1">
            <div class="row p-1 mt-1 mb-1">
              <div class="col-10">
                <select v-model="filters.pending.store" name="store" class="form-control form-control-sm" @change="loadPending(1)">
                  <option value="">Select Store</option>
                  <option v-for="s in storeOptions" :key="s.id" :value="s.id">{{ s.text }}</option>
                </select>
              </div>
              <div class="col-2 p-0">
                <button type="button" class="btn btn-secondary btn-sm m-0" @click="refreshPending"><i class="fas fa-undo"></i></button>
              </div>
            </div>
          </form>
          <div v-if="pendingLoading" class="d-flex justify-content-center p-3"><div class="spinner-border spinner-border-sm"></div></div>
          <div v-else id="beginning-inventory-list-el" style="height: 850px; overflow-y: auto" v-html="pendingHtml"></div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import axios from 'axios';

const displayedData = ref({ recent_period: '', stores_submitted: '', stores_pending: '', total_sales: '' });
const selectYear = ref([]);
const promodisers = ref([]);

const storeOptions = ref([]);
const submittedHtml = ref('');
const pendingHtml = ref('');
const submittedLoading = ref(false);
const pendingLoading = ref(false);

const filters = reactive({
  submitted: { store: '', year: new Date().getFullYear().toString(), promodiser: '' },
  pending: { store: '' },
});

async function fetchStores() {
  try {
    const { data } = await axios.get('/consignment_stores', { params: { q: '' } });
    storeOptions.value = Array.isArray(data) ? data : [];
  } catch {
    storeOptions.value = [];
  }
}

async function loadSubmitted(page = 1) {
  submittedLoading.value = true;
  try {
    const params = { page, store: filters.submitted.store, year: filters.submitted.year, promodiser: filters.submitted.promodiser };
    const { data } = await axios.get('/submitted_inventory_audit', { params, responseType: 'text' });
    submittedHtml.value = data;
  } catch {
    submittedHtml.value = '<div class="alert alert-danger m-2">Failed to load.</div>';
  } finally {
    submittedLoading.value = false;
  }
}

async function loadPending(page = 1) {
  pendingLoading.value = true;
  try {
    const params = { page, store: filters.pending.store };
    const { data } = await axios.get('/pending_submission_inventory_audit', { params, responseType: 'text' });
    pendingHtml.value = data;
  } catch {
    pendingHtml.value = '<div class="alert alert-danger m-2">Failed to load.</div>';
  } finally {
    pendingLoading.value = false;
  }
}

function refreshSubmitted() {
  filters.submitted.store = '';
  filters.submitted.year = new Date().getFullYear().toString();
  filters.submitted.promodiser = '';
  loadSubmitted(1);
}

function refreshPending() {
  filters.pending.store = '';
  loadPending(1);
}

function onSubmittedClick(event) {
  const link = event.target.closest('#inventory-audit-history-pagination a');
  if (link && link.href) {
    event.preventDefault();
    const url = new URL(link.href);
    const page = url.searchParams.get('page') || 1;
    loadSubmitted(page);
  }
}

onMounted(async () => {
  const mountEl = document.getElementById('supervisor-inventory-audit-list');
  if (mountEl) {
    try {
      const dataAttr = mountEl.getAttribute('data-displayed-data');
      displayedData.value = dataAttr ? JSON.parse(dataAttr) : displayedData.value;
    } catch (_) {}
    try {
      const yearAttr = mountEl.getAttribute('data-select-year');
      selectYear.value = yearAttr ? JSON.parse(yearAttr) : [];
    } catch (_) {}
    try {
      const promAttr = mountEl.getAttribute('data-promodisers');
      promodisers.value = promAttr ? JSON.parse(promAttr) : [];
    } catch (_) {}
  }
  const currentYear = new Date().getFullYear().toString();
  if (selectYear.value.length && !filters.submitted.year) {
    filters.submitted.year = selectYear.value.includes(currentYear) ? currentYear : (selectYear.value[0] || currentYear);
  }
  await fetchStores();
  await Promise.all([loadSubmitted(1), loadPending(1)]);
});
</script>
