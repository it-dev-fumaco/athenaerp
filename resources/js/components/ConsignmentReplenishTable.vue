<template>
  <div class="container-fluid">
    <div class="row">
      <div class="col-12 p-2">
        <div class="row">
          <div class="col-8 p-2">
            <input
              v-model="search"
              type="text"
              placeholder="Search..."
              class="form-control form-control-sm"
              @keyup.enter="load()"
            />
          </div>
          <div class="col-4 p-2">
            <button type="button" class="btn btn-sm btn-primary w-100" @click="load()">
              <i class="fa fa-search"></i> Search
            </button>
          </div>
        </div>

        <div v-show="showAdvancedFilters" class="row additional-filters">
          <div class="col-8 p-2">
            <select v-model="branch" class="form-control form-control-sm select-filter" @change="load()">
              <option value="" disabled>Select a Branch</option>
              <option value="">Select all</option>
              <option v-for="store in stores" :key="store" :value="store">{{ store }}</option>
            </select>
          </div>
          <div class="col-4 p-2">
            <select v-model="status" class="form-control form-control-sm select-filter" @change="load()">
              <option value="" disabled>Status</option>
              <option value="">Select all</option>
              <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="col-12 p-2">
            <button
              type="button"
              class="btn btn-link p-0 text-primary text-underline"
              style="font-size: 9pt"
              @click="showAdvancedFilters = !showAdvancedFilters"
            >
              {{ showAdvancedFilters ? 'Hide filters' : 'Advanced Filters...' }}
            </button>
          </div>
        </div>
      </div>
      <div
        id="replenish-tbl"
        class="col-12 dashboard-html-content"
        @click="onTableClick"
      >
        <div v-if="loading" class="d-flex justify-content-center align-items-center p-5">
          <div class="spinner-border"></div>
        </div>
        <div v-else v-html="tableHtml"></div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const el = document.getElementById('consignment-replenish') || document.getElementById('consignment-orders-supervisor');
const stores = ref(el?.dataset?.stores ? JSON.parse(el.dataset.stores) : []);
const statuses = ref(el?.dataset?.statuses ? JSON.parse(el.dataset.statuses) : ['Draft', 'For Approval', 'Approved', 'Delivered', 'Cancelled']);

const search = ref('');
const branch = ref('');
const status = ref('');
const showAdvancedFilters = ref(false);
const tableHtml = ref('');
const loading = ref(true);

const baseUrl = '/consignment/replenish';

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await axios.get(baseUrl, {
      params: { page, branch: branch.value || undefined, status: status.value || undefined, search: search.value || undefined },
      responseType: 'text',
    });
    tableHtml.value = data;
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to load.';
    tableHtml.value = `<div class="alert alert-danger m-2">${message}</div>`;
  } finally {
    loading.value = false;
  }
}

function onTableClick(event) {
  const link = event.target.closest('a[href*="/consignment/replenish"]');
  if (!link || !link.href) return;
  event.preventDefault();
  loading.value = true;
  axios.get(link.href, { responseType: 'text' })
    .then(({ data }) => {
      tableHtml.value = data;
    })
    .catch((err) => {
      const message = err.response?.data?.message || 'Failed to load.';
      tableHtml.value = `<div class="alert alert-danger m-2">${message}</div>`;
    })
    .finally(() => {
      loading.value = false;
    });
}

onMounted(() => load(1));
</script>
