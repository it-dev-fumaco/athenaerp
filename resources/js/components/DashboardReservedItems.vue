<template>
  <div class="dashboard-html-content" @click="onContainerClick" @change="onContainerChange">
    <div v-if="loading" class="text-center p-3">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div v-else class="dashboard-html-body" v-html="html"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const html = ref('');
const loading = ref(true);
const warehouse = ref('');
const baseUrl = '/get_reserved_items';

async function load(page = 1) {
  loading.value = true;
  try {
    const params = { page };
    if (warehouse.value) {
      params.warehouse = warehouse.value;
    }
    const { data } = await axios.get(baseUrl, { params, responseType: 'text' });
    html.value = data;
  } catch (_) {
    html.value = '<p class="text-center text-muted p-2">Failed to load.</p>';
  } finally {
    loading.value = false;
  }
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="get_reserved_items"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const url = new URL(link.href, window.location.origin);
  const page = url.searchParams.get('page') || 1;
  const linkWarehouse = url.searchParams.get('warehouse') || '';
  warehouse.value = linkWarehouse;
  load(Number(page));
}

function onContainerChange(event) {
  const select = event.target.closest('#reserved-warehouse-filter');
  if (!select) return;
  warehouse.value = select.value || '';
  load(1);
}

onMounted(() => load(1));
</script>

<style scoped>
.dashboard-html-content {
  min-width: 0;
  display: flex;
  flex-direction: column;
  min-height: 0;
}
.dashboard-html-body {
  flex: 1 1 auto;
  min-height: 0;
  overflow: auto;
}
</style>
