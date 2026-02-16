<template>
  <div class="item-profile-purchase-history" @click="onContainerClick">
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

const basePath = '/purchase_rate_history';

function buildUrl(page = '') {
  const query = page ? `?page=${page}` : '';
  return `${basePath}/${props.itemCode}${query}`;
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
    html.value = '<p class="text-center text-muted p-3">Failed to load purchase history.</p>';
  } finally {
    loading.value = false;
  }
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="purchase_rate_history"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const href = link.getAttribute('href') || link.href;
  if (href && href.indexOf('purchase_rate_history') !== -1) {
    load(href);
  }
}

function onRefresh() {
  load();
}

onMounted(() => {
  load();
  window.addEventListener('item-profile-purchase-history-refresh', onRefresh);
});

onUnmounted(() => {
  window.removeEventListener('item-profile-purchase-history-refresh', onRefresh);
});
</script>
