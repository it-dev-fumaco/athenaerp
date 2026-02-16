<template>
  <div>
    <h5 class="font-responsive font-weight-bold text-center m-1 text-uppercase d-block" id="branch-name">{{ branch }}</h5>
    <div class="row mt-3" style="font-size: 13px;">
      <div class="col-6 offset-1 text-right">
        <span class="d-inline-block mt-2 mb-2 font-weight-bold">Sales Report for the year: </span>
      </div>
      <div class="col-4">
        <select v-model="selectedYear" class="form-control" id="sales-report-year" @change="loadReport">
          <option v-for="y in years" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </div>
    <div v-if="loading" class="d-flex justify-content-center p-4"><div class="spinner-border"></div></div>
    <div v-else id="sales-report-table" class="mt-2" v-html="tableHtml"></div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const branch = ref('');
const years = ref([]);
const selectedYear = ref(new Date().getFullYear().toString());
const tableHtml = ref('');
const loading = ref(true);

async function loadReport() {
  if (!branch.value) return;
  loading.value = true;
  try {
    const { data } = await axios.get(`/sales_report_list/${encodeURIComponent(branch.value)}`, {
      params: { year: selectedYear.value },
      responseType: 'text',
    });
    tableHtml.value = data;
  } catch {
    tableHtml.value = '<div class="alert alert-danger m-2">Error in getting records.</div>';
  } finally {
    loading.value = false;
  }
}

onMounted(() => {
  const mountEl = document.getElementById('sales-report-list');
  if (mountEl) {
    try {
      branch.value = mountEl.getAttribute('data-branch') || '';
    } catch (_) {}
    try {
      const yearsAttr = mountEl.getAttribute('data-years');
      years.value = yearsAttr ? JSON.parse(yearsAttr) : [];
    } catch (_) {}
    try {
      const currentAttr = mountEl.getAttribute('data-current-year');
      if (currentAttr && years.value.includes(currentAttr)) {
        selectedYear.value = currentAttr;
      }
    } catch (_) {}
  }
  loadReport();
});
</script>
