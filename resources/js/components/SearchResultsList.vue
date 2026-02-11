<template>
  <div id="search-results-list">
    <div v-if="loading" class="container-fluid p-5 text-center">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <template v-else-if="apiData">
      <div class="col-12 col-xl-12">
        <div class="container-fluid m-0">
          <template v-for="(row, index) in apiData.data" :key="row.name">
            <div class="mb-1"></div>
            <div class="border border-outline-secondary">
              <div class="row m-0">
                <div class="col-1 p-1">
                  <a :href="row.image" :data-item-code="row.name" class="view-images">
                    <img :src="row.image" class="img w-100" alt="">
                  </a>
                  <div class="text-center mt-2 mb-1">
                    <a :href="'/get_item_details/' + row.name" class="btn btn-primary btn-xs btn-block">
                      <i class="fa fa-search"></i> <span class="d-inline d-md-none" style="font-size: 10pt">View Item Details</span>
                    </a>
                  </div>
                </div>
                <div class="col-6 p-1">
                  <div class="col-md-12 m-0 text-justify">
                    <span class="font-italic item-class">{{ row.item_classification }} - {{ row.item_group }}</span>
                    <span v-if="bundledItems.includes(row.name)" class="badge badge-info font-italic ml-1" style="font-size: 8pt;">Product Bundle</span>
                    <br/>
                    <span class="text-justify item-name" style="font-size: 10pt !important;"><b>{{ row.name }}</b> - <span v-html="row.description"></span></span>
                    <template v-if="row.package_dimension">
                      <dl class="mt-3 mb-0">
                        <dt style="font-size: 9pt;" class="text-muted">Package Dimension</dt>
                        <dd style="font-size: 8pt;" class="text-muted text-justify pt-1" v-html="row.package_dimension"></dd>
                      </dl>
                    </template>
                    <template v-else><br></template>
                    <span v-if="row.part_nos" class="text-justify item-name"><b>Part No(s)</b> {{ row.part_nos }}</span>
                    <p v-if="apiData.show_price && row.default_price > 0" class="mt-3 mb-2">
                      <span class="d-block font-weight-bold" style="font-size: 15pt;">₱ {{ formatPrice(row.default_price) }}</span>
                      <span class="d-block" style="font-size: 9pt;">Standard Selling Price</span>
                    </p>
                  </div>
                </div>
                <div class="col-5 p-1">
                  <table v-if="row.item_inventory && row.item_inventory.length" class="table table-sm table-bordered warehouse-table table-hover">
                    <thead>
                      <tr>
                        <th class="text-center wh-cell">Warehouse</th>
                        <th class="text-center qtr-cell text-muted">Reserved Qty</th>
                        <th class="text-center qtr-cell">Available Qty</th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-for="inv in row.item_inventory" :key="inv.warehouse">
                        <td class="text-center">
                          {{ inv.warehouse }}
                          <small v-if="inv.location" class="text-muted font-italic"> - {{ inv.location }}</small>
                        </td>
                        <td class="text-center"><small class="text-muted">{{ inv.reserved_qty * 1 }} {{ inv.stock_uom }}</small></td>
                        <td class="text-center">
                          <span class="badge" :class="invBadgeClass(inv)" style="font-size: 14px;">{{ inv.available_qty * 1 }} <small>{{ inv.stock_uom }}</small></span>
                        </td>
                      </tr>
                    </tbody>
                  </table>
                  <div v-else-if="!bundledItems.includes(row.name)" class="h-75 d-flex align-items-center">
                    <p class="pt-2 mx-auto">No Available Stock on All Warehouses</p>
                  </div>
                </div>
              </div>
            </div>
          </template>
          <div v-if="!apiData.data.length" class="col-md-12 text-center" style="padding: 25px;">
            <h5>No result(s) found / Stocks not available</h5>
          </div>
          <div id="search-results-pagination" class="mt-3 ml-3 clearfix pagination" style="display: block;">
            <div class="container-fluid d-flex justify-content-end align-items-center">
              <nav>
                <ul class="pagination mb-0">
                  <li class="page-item" :class="{ disabled: apiData.meta.current_page <= 1 }">
                    <a class="page-link" href="#" @click.prevent="goToPage(apiData.meta.current_page - 1)">«</a>
                  </li>
                  <li v-for="p in pageNumbers" :key="p" class="page-item" :class="{ active: p === apiData.meta.current_page }">
                    <a class="page-link" href="#" @click.prevent="goToPage(p)">{{ p }}</a>
                  </li>
                  <li class="page-item" :class="{ disabled: apiData.meta.current_page >= apiData.meta.last_page }">
                    <a class="page-link" href="#" @click.prevent="goToPage(apiData.meta.current_page + 1)">»</a>
                  </li>
                </ul>
              </nav>
            </div>
          </div>
        </div>
      </div>
    </template>
    <div v-else v-html="initialHtml"></div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const initialHtml = ref(typeof window !== 'undefined' && window.__SEARCH_RESULTS_INITIAL_HTML__ ? window.__SEARCH_RESULTS_INITIAL_HTML__ : '');
const apiData = ref(null);
const loading = ref(false);
const currentFetchUrl = ref('');

const bundledItems = computed(() => (apiData.value && apiData.value.bundled_items) ? apiData.value.bundled_items : []);

const pageNumbers = computed(() => {
  if (!apiData.value?.meta) return [];
  const cur = apiData.value.meta.current_page;
  const last = apiData.value.meta.last_page;
  const delta = 2;
  const range = [];
  for (let i = 1; i <= last; i++) {
    if (i === 1 || i === last || (i >= cur - delta && i <= cur + delta)) range.push(i);
  }
  return range;
});

function stripTags(html) {
  if (!html) return '';
  const div = document.createElement('div');
  div.innerHTML = html;
  return div.textContent || div.innerText || '';
}

function formatPrice(num) {
  return Number(num).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function invBadgeClass(inv) {
  if (inv.available_qty === 0) return 'badge-secondary';
  if (inv.available_qty <= inv.warehouse_reorder_level) return 'badge-warning';
  return 'badge-success';
}

async function loadUrl(url) {
  if (!url) return;
  loading.value = true;
  currentFetchUrl.value = url;
  try {
    const { data } = await axios.get(url, { headers: { Accept: 'application/json' } });
    if (data && Array.isArray(data.data) && data.meta) {
      apiData.value = data;
    } else {
      initialHtml.value = typeof data === 'string' ? data : '';
      apiData.value = null;
    }
  } catch (_) {
    apiData.value = null;
  } finally {
    loading.value = false;
  }
}

function goToPage(page) {
  const p = Math.max(1, Math.min(page, apiData.value?.meta?.last_page || 1));
  const base = currentFetchUrl.value || window.location.href;
  const url = base.startsWith('http') ? new URL(base) : new URL(base, window.location.origin);
  url.searchParams.set('page', String(p));
  loadUrl(url.toString());
}

function handleFormSubmit(event) {
  const form = event.target;
  if (!form || form.id !== 'search-form') return;
  event.preventDefault();
  const action = form.getAttribute('action') || '/search_results';
  const formData = new FormData(form);
  const params = new URLSearchParams(formData);
  params.set('page', '1');
  const url = action + (action.includes('?') ? '&' : '?') + params.toString();
  loadUrl(url);
}

function handleDocumentClick(event) {
  const listEl = document.getElementById('search-results-list');
  if (!listEl || !listEl.contains(event.target)) return;
  const link = event.target.closest('a[href*="search_results"]');
  if (!link || !link.href) return;
  const href = link.getAttribute('href');
  if (!href || href.startsWith('#') || href.indexOf('search_results') === -1) return;
  event.preventDefault();
  loadUrl(href);
}

onMounted(() => {
  const form = document.getElementById('search-form');
  if (form) form.addEventListener('submit', handleFormSubmit);
  document.addEventListener('click', handleDocumentClick, true);
});

onUnmounted(() => {
  const form = document.getElementById('search-form');
  if (form) form.removeEventListener('submit', handleFormSubmit);
  document.removeEventListener('click', handleDocumentClick, true);
});
</script>
