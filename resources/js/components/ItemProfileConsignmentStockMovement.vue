<template>
  <div class="item-profile-consignment-stock-movement" @click="onContainerClick">
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

const basePath = '/consignment_stock_movement';

function getFilterParams() {
  const container = document.querySelector('#consignment-stock-movement') || document;
  const storeEl = container.querySelector('.csm-filter');
  const store = storeEl ? (storeEl.tagName === 'SELECT' ? storeEl.value : storeEl.value) : '';
  const dateRange = container.querySelector('#consignment-date-range');
  const user = container.querySelector('#consignment-user-select');
  return {
    branch_warehouse: store || '',
    date_range: dateRange ? dateRange.value : '',
    user: user ? user.value : '',
  };
}

function buildUrl(page = '') {
  const params = getFilterParams();
  const query = new URLSearchParams();
  if (page) query.set('page', page);
  if (params.branch_warehouse) query.set('branch_warehouse', params.branch_warehouse);
  if (params.date_range) query.set('date_range', params.date_range);
  if (params.user) query.set('user', params.user);
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
    html.value = '<p class="text-center text-muted p-3">Failed to load consignment stock movement.</p>';
  } finally {
    loading.value = false;
  }
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="consignment_stock_movement"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const href = link.getAttribute('href') || link.href;
  if (href && href.indexOf('consignment_stock_movement') !== -1 && href.indexOf('get_users') === -1) {
    load(href);
  }
}

function onRefresh() {
  load();
}

onMounted(() => {
  load();
  window.addEventListener('item-profile-consignment-stock-movement-refresh', onRefresh);
});

onUnmounted(() => {
  window.removeEventListener('item-profile-consignment-stock-movement-refresh', onRefresh);
});
</script>
