<template>
  <div class="dashboard-html-content" @click="onContainerClick">
    <div v-if="loading" class="text-center p-3">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div v-else v-html="html"></div>
  </div>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue';
import axios from 'axios';

const html = ref('');
const loading = ref(true);
const currentPage = ref(1);
const baseUrl = '/get_low_stock_level_items';

async function load(page = 1) {
  loading.value = true;
  currentPage.value = page;
  try {
    const { data } = await axios.get(baseUrl, { params: { page }, responseType: 'text' });
    html.value = data;
  } catch (_) {
    html.value = '<p class="text-center text-muted p-2">Failed to load.</p>';
  } finally {
    loading.value = false;
  }
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="get_low_stock_level_items"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const url = new URL(link.href, window.location.origin);
  const page = url.searchParams.get('page') || 1;
  load(Number(page));
}

function onRefresh() {
  load(currentPage.value);
}

onMounted(() => {
  load(1);
  document.addEventListener('low-level-stocks-refresh', onRefresh);
});

onBeforeUnmount(() => {
  document.removeEventListener('low-level-stocks-refresh', onRefresh);
});
</script>
