<template>
  <div class="item-profile-stock-ledger" @click="onContainerClick">
    <div v-if="loading" class="container d-flex justify-content-center align-items-center p-5">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div v-else v-html="html"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  itemCode: { type: String, required: true },
});

const html = ref('');
const loading = ref(true);

const basePath = '/get_stock_ledger';

function getFilterParams() {
  const container = document.querySelector('#history') || document;
  const user = container.querySelector('#erp-warehouse-user-filter');
  const wh = container.querySelector('#erp-warehouse-filter');
  const dates = container.querySelector('#erp_dates');
  return {
    wh_user: user ? user.value : '',
    erp_wh: wh ? wh.value : '',
    erp_d: dates ? dates.value : '',
  };
}

function buildUrl(page = '') {
  const params = getFilterParams();
  const query = new URLSearchParams();
  if (page) query.set('page', page);
  if (params.wh_user) query.set('wh_user', params.wh_user);
  if (params.erp_wh) query.set('erp_wh', params.erp_wh);
  if (params.erp_d) query.set('erp_d', params.erp_d);
  const qs = query.toString();
  return `${basePath}/${props.itemCode}${qs ? '?' + qs : ''}`;
}

async function load(urlOrPage = null) {
  loading.value = true;
  try {
    const url = typeof urlOrPage === 'string' && urlOrPage.indexOf(basePath) !== -1
      ? urlOrPage
      : buildUrl(urlOrPage || '');
    const { data } = await axios.get(url, { responseType: 'text' });
    html.value = data;
  } catch (_) {
    html.value = '<p class="text-center text-muted p-3">Failed to load stock ledger.</p>';
  } finally {
    loading.value = false;
  }
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="get_stock_ledger"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const href = link.getAttribute('href') || link.href;
  if (href && href.indexOf('get_stock_ledger') !== -1) {
    load(href);
  }
}

function onRefresh() {
  load();
}

onMounted(() => {
  load();
  window.addEventListener('item-profile-stock-ledger-refresh', onRefresh);
});

onUnmounted(() => {
  window.removeEventListener('item-profile-stock-ledger-refresh', onRefresh);
});
</script>
