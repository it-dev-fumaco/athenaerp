<template>
  <div class="dashboard-html-content" @click="onContainerClick">
    <div v-if="loading" class="text-center p-3">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <div v-else v-html="html"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const html = ref('');
const loading = ref(true);
const baseUrl = '/get_reserved_items';

async function load(page = 1) {
  loading.value = true;
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
  const link = event.target.closest('a[href*="get_reserved_items"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const url = new URL(link.href, window.location.origin);
  const page = url.searchParams.get('page') || 1;
  load(Number(page));
}

onMounted(() => load(1));
</script>
