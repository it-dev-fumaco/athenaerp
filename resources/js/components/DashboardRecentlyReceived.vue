<template>
  <div class="dashboard-html-content overflow-auto">
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

async function load() {
  loading.value = true;
  try {
    const { data } = await axios.get('/recently_received_items', { responseType: 'text' });
    html.value = data;
  } catch (_) {
    html.value = '<p class="text-center text-muted p-2">Failed to load.</p>';
  } finally {
    loading.value = false;
  }
}

onMounted(load);
</script>
