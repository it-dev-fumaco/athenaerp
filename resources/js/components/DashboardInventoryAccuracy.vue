<template>
  <div class="box">
    <form autocomplete="off">
      <div class="text-center">Monthly Inventory Accuracy:
        <select
          v-model="selectedMonth"
          class="filter-inv-accuracy"
          style="width: 15%;"
          @change="loadData"
        >
          <option value="">-</option>
          <option v-for="i in 12" :key="i" :value="i">{{ monthLabel(i) }}</option>
        </select>
        <select
          v-model="selectedYear"
          class="filter-inv-accuracy"
          style="width: 15%;"
          @change="loadData"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </form>
    <table class="table table-bordered mt-2" id="monthly-inv-chart">
      <col style="width: 30%;">
      <col style="width: 30%;">
      <col style="width: 20%;">
      <col style="width: 20%;">
      <thead>
        <tr>
          <th class="text-center pr-0 pl-0 align-middle">Classification</th>
          <th class="text-center pr-0 pl-0 align-middle">Warehouse</th>
          <th class="text-center pr-0 pl-0 align-middle">Accuracy</th>
          <th class="text-center pr-0 pl-0 align-middle">Target</th>
        </tr>
      </thead>
      <tbody class="item-classification">
        <template v-if="loading">
          <tr>
            <td colspan="4" class="text-center">
              <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
              Loading...
            </td>
          </tr>
        </template>
        <template v-else-if="rows.length === 0">
          <tr>
            <td colspan="4" class="text-center">No Records Found.</td>
          </tr>
        </template>
        <tr v-for="(row, index) in rows" :key="index">
          <td class="inv-accuracy-tbl-item-class">{{ row.item_classification }}</td>
          <td class="inv-accuracy-tbl-item-class">{{ row.warehouse }}</td>
          <td class="text-center inv-accuracy-tbl-item-class">
            <i :class="row.percentage >= row.target ? 'fa fa-thumbs-up' : 'fa fa-thumbs-down'" :style="{ color: row.percentage >= row.target ? 'green' : 'red' }"></i>
            {{ row.percentageFixed }}%
          </td>
          <td class="text-center inv-accuracy-tbl-item-class">{{ row.targetFixed }}%</td>
        </tr>
      </tbody>
    </table>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const MONTHS = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const el = document.getElementById('dashboard-inv-accuracy');
const initialMonth = el?.dataset?.initialMonth ? parseInt(el.dataset.initialMonth, 10) : new Date().getMonth() + 1;
const initialYear = el?.dataset?.initialYear ? parseInt(el.dataset.initialYear, 10) : new Date().getFullYear();

const selectedMonth = ref(initialMonth);
const selectedYear = ref(initialYear);
const chartData = ref([]);
const loading = ref(false);

const yearOptions = computed(() => {
  const currentYear = new Date().getFullYear();
  const years = [];
  for (let y = 2018; y <= currentYear; y++) {
    years.push(y);
  }
  return years.reverse();
});

const rows = computed(() => {
  const month = selectedMonth.value ? parseInt(selectedMonth.value, 10) : null;
  if (!month || !chartData.value.length) return [];
  const monthData = chartData.value.find((d) => Number(d.month_no) === month);
  if (!monthData || !monthData.audit_per_month || !monthData.audit_per_month.length) return [];
  return monthData.audit_per_month.map((v) => {
    const target = parseFloat(v.percentage_sku);
    const percentage = parseFloat(v.average_accuracy_rate);
    return {
      item_classification: v.item_classification,
      warehouse: v.warehouse,
      percentage,
      target,
      percentageFixed: percentage.toFixed(2),
      targetFixed: target.toFixed(2),
    };
  });
});

function monthLabel(i) {
  return MONTHS[i] || i;
}

async function loadData() {
  const year = selectedYear.value;
  if (!year) return;
  loading.value = true;
  try {
    const { data } = await axios.get(`/inv_accuracy/${year}`);
    chartData.value = data;
  } catch (_) {
    chartData.value = [];
  } finally {
    loading.value = false;
  }
}

onMounted(loadData);
</script>
