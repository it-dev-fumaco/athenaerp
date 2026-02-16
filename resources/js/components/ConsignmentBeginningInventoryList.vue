<template>
  <div>
    <div id="accordion">
      <button
        type="button"
        class="btn btn-link border-bottom btn-block text-left"
        style="font-size: 10pt;"
        data-toggle="collapse"
        :data-target="'#' + collapseId"
        aria-expanded="true"
        :aria-controls="collapseId"
        @click.prevent="filtersExpanded = !filtersExpanded"
      >
        <i class="fa fa-filter"></i> Filters
      </button>
      <div
        :id="collapseId"
        class="collapse show"
        aria-labelledby="headingOne"
        data-parent="#accordion"
      >
        <div class="card-body p-0">
          <div class="row p-2">
            <div class="col-12 col-lg-4 col-xl-4">
              <input
                v-model="search"
                type="text"
                class="form-control filters-font"
                placeholder="Search"
                @keyup.enter="load(1)"
              />
            </div>
            <div class="col-12 col-lg-2 col-xl-2 mt-2 mt-lg-0">
              <select v-model="store" class="form-control filters-font">
                <option value="" disabled>Select a store</option>
                <option v-for="s in stores" :key="s" :value="s">{{ s }}</option>
              </select>
            </div>
            <div class="col-12 col-lg-4 col-xl-2 mt-2 mt-lg-0">
              <input
                v-model="dateRange"
                type="text"
                class="form-control filters-font"
                placeholder="e.g. 2024-01-01 to 2024-01-31"
              />
            </div>
            <div class="col-12 col-lg-2 col-xl-1 mt-2 mt-lg-0">
              <button type="button" class="btn btn-primary filters-font w-100" @click="load(1)">
                <i class="fas fa-search"></i> Search
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div v-if="loading" class="d-flex justify-content-center align-items-center p-5">
      <div class="spinner-border"></div>
    </div>
    <div
      v-else
      class="beginning-inv-table-wrapper"
      v-html="tableHtml"
      @click="onContentClick"
    ></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const mountEl = document.getElementById('consignment-beginning-inventory-list');
const stores = ref(mountEl?.dataset?.stores ? JSON.parse(mountEl.dataset.stores) : []);
const earliestDate = ref(mountEl?.dataset?.earliestDate ?? '');

const collapseId = 'collapseBeginningInv';
const search = ref('');
const store = ref('');
const dateRange = ref('');
const tableHtml = ref('');
const loading = ref(true);

const baseUrl = '/beginning_inv_list';

async function load(page = 1) {
  loading.value = true;
  try {
    const { data } = await axios.get(baseUrl, {
      params: {
        search: search.value || undefined,
        store: store.value || undefined,
        date: dateRange.value || undefined,
        page,
      },
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

function onContentClick(event) {
  const link = event.target.closest('a[href*="/beginning_inv_list"]');
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
