<template>
  <div class="dashboard-athena-logs">
    <div class="p-2">
      <div v-if="tableLoading" class="text-center p-3">
        <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
      </div>
      <div v-else v-html="tableHtml"></div>
    </div>
    <ul class="pagination pagination-month justify-content-center m-2">
      <li
        v-for="month in months"
        :key="month.key"
        class="page-item month"
        :class="{ active: month.key === activeMonthKey }"
        :data-month="month.value"
      >
        <a class="page-link" href="#" @click.prevent="selectMonth(month)">
          <p class="page-month mb-0" style="font-size: 0.9rem;">{{ month.label }}</p>
          <p class="page-year mb-0" style="font-size: 0.8rem;">{{ month.year }}</p>
        </a>
      </li>
    </ul>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const tableHtml = ref('');
const tableLoading = ref(true);
const activeMonthKey = ref('');

function buildMonths() {
  const result = [];
  const now = new Date();
  for (let i = 11; i >= 0; i--) {
    const d = new Date(now.getFullYear(), now.getMonth() - i, 1);
    const year = d.getFullYear();
    const month = d.getMonth() + 1;
    const value = `${year}-${String(month).padStart(2, '0')}-01`;
    const label = d.toLocaleString('en-US', { month: 'short' });
    result.push({ key: value, value, label, year });
  }
  return result;
}

const months = computed(() => buildMonths());

async function loadTable(monthValue) {
  if (!monthValue) return;
  tableLoading.value = true;
  try {
    const { data } = await axios.get('/get_athena_logs', {
      params: { month: monthValue },
      responseType: 'text',
    });
    tableHtml.value = data;
  } catch (err) {
    const message = err.response?.data?.message || 'Failed to load.';
    tableHtml.value = `<p class="text-center text-danger p-2">${message}</p>`;
  } finally {
    tableLoading.value = false;
  }
}

function selectMonth(month) {
  activeMonthKey.value = month.key;
  loadTable(month.value);
}

onMounted(() => {
  const current = months.value[months.value.length - 1];
  if (current) {
    activeMonthKey.value = current.key;
    loadTable(current.value);
  }
});
</script>
