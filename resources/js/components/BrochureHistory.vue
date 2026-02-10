<template>
  <div>
    <div class="card-header">
      <h3 class="card-title">Recently Uploads</h3>
    </div>
    <div class="card-body p-0">
      <input
        v-model="search"
        type="text"
        class="form-control m-2"
        placeholder="Search..."
        @input="onSearchInput"
      />
      <div class="container-fluid" v-html="historyHtml"></div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const search = ref('');
const historyHtml = ref('');
let searchTimeout = null;

async function loadHistory() {
  try {
    const { data } = await axios.get('/brochure', {
      params: { search: search.value },
      responseType: 'text',
    });
    historyHtml.value = data;
  } catch {
    historyHtml.value = '<ul class="products-list product-list-in-card pl-2 pr-2"><li class="item pl-2 pr-2"><span class="d-block text-center text-uppercase text-danger">Failed to load.</span></li></ul>';
  }
}

function onSearchInput() {
  if (searchTimeout) clearTimeout(searchTimeout);
  searchTimeout = setTimeout(loadHistory, 200);
}

onMounted(() => loadHistory());
</script>
