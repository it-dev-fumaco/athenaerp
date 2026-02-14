<template>
  <div>
    <ul class="nav nav-pills mt-1 d-flex flex-row justify-content-center" role="tablist" style="font-size: 8pt;">
      <li v-for="tab in tabs" :key="tab.purpose" class="nav-item border">
        <button
          type="button"
          class="nav-link font-weight-bold"
          :class="{ active: activePurpose === tab.purpose }"
          @click="switchTab(tab.purpose)"
        >
          {{ tab.label }}
        </button>
      </li>
    </ul>

    <div class="container mt-2" style="padding: 8px 0 0 0;">
      <div v-if="loading" class="d-flex justify-content-center align-items-center p-5">
        <div class="spinner-border"></div>
      </div>
      <div
        v-else
        class="tab-content-html"
        v-html="tableHtml"
        @click="onContentClick"
      ></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const tabs = [
  { purpose: 'Store Transfer', label: 'Store-to-Store Transfer' },
  { purpose: 'Pull Out', label: 'Item Pull Out' },
  { purpose: 'Item Return', label: 'Item Return' },
];

const activePurpose = ref('Store Transfer');
const tableHtml = ref('');
const loading = ref(true);

const baseUrl = '/stock_transfer/list';

async function load(purpose, page = 1) {
  loading.value = true;
  try {
    const { data } = await axios.get(baseUrl, {
      params: { purpose, page },
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

function switchTab(purpose) {
  activePurpose.value = purpose;
  load(purpose, 1);
}

function onContentClick(event) {
  const link = event.target.closest('a[href*="/stock_transfer/list"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const url = new URL(link.href);
  const page = url.searchParams.get('page') || 1;
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

onMounted(() => load(activePurpose.value, 1));
</script>
