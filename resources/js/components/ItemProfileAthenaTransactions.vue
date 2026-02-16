<template>
  <div class="item-profile-athena-transactions" @click="onContainerClick">
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

const basePath = '/get_athena_transactions';

function getFilterParams() {
  const container = document.querySelector('#athena-logs') || document;
  const src = container.querySelector('#ath-src-warehouse-filter');
  const trg = container.querySelector('#ath-to-warehouse-filter');
  const user = container.querySelector('#warehouse-user-filter');
  const dates = container.querySelector('#ath_dates');
  return {
    src_wh: src ? src.value : '',
    trg_wh: trg ? trg.value : '',
    wh_user: user ? user.value : '',
    ath_dates: dates ? dates.value : '',
  };
}

function buildUrl(page = '') {
  const params = getFilterParams();
  const query = new URLSearchParams();
  if (page) query.set('page', page);
  if (params.wh_user) query.set('wh_user', params.wh_user);
  if (params.src_wh) query.set('src_wh', params.src_wh);
  if (params.trg_wh) query.set('trg_wh', params.trg_wh);
  if (params.ath_dates) query.set('ath_dates', params.ath_dates);
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
    html.value = '<p class="text-center text-muted p-3">Failed to load Athena transactions.</p>';
  } finally {
    loading.value = false;
  }
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="get_athena_transactions"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const href = link.getAttribute('href') || link.href;
  if (href && href.indexOf('get_athena_transactions') !== -1) {
    load(href);
  }
}

function onRefresh() {
  load();
}

onMounted(() => {
  load();
  window.addEventListener('item-profile-athena-transactions-refresh', onRefresh);
});

onUnmounted(() => {
  window.removeEventListener('item-profile-athena-transactions-refresh', onRefresh);
});
</script>
