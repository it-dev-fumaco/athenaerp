<template>
  <div class="box inv-accuracy-widget">
    <form autocomplete="off" class="inv-accuracy-form">
      <div class="inv-accuracy-filters">
        <span class="inv-accuracy-filters__label">Monthly Inventory Accuracy:</span>
        <select
          v-model="selectedMonth"
          class="inv-accuracy-filters__select form-control form-control-sm"
          @change="loadData"
        >
          <option value="">-</option>
          <option v-for="i in 12" :key="i" :value="i">{{ monthLabel(i) }}</option>
        </select>
        <select
          v-model="selectedYear"
          class="inv-accuracy-filters__select form-control form-control-sm"
          @change="loadData"
        >
          <option v-for="y in yearOptions" :key="y" :value="y">{{ y }}</option>
        </select>
      </div>
    </form>
    <div class="responsive-table-wrap">
    <table class="table table-bordered table-sm mt-2" id="monthly-inv-chart">
      <thead>
        <tr>
          <th class="text-center pr-1 pl-1 align-middle">Classification</th>
          <th class="text-center pr-1 pl-1 align-middle">Warehouse</th>
          <th class="text-center pr-1 pl-1 align-middle">Accuracy</th>
          <th class="text-center pr-1 pl-1 align-middle">Target</th>
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
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  initialMonth: { type: [Number, String], default: null },
  initialYear: { type: [Number, String], default: null },
});

const MONTHS = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

const el = typeof document !== 'undefined' ? document.getElementById('dashboard-inv-accuracy') : null;
const fallbackMonth = new Date().getMonth() + 1;
const fallbackYear = new Date().getFullYear();
const initialMonth = props.initialMonth != null ? Number(props.initialMonth) : (el?.dataset?.initialMonth ? parseInt(el.dataset.initialMonth, 10) : fallbackMonth);
const initialYear = props.initialYear != null ? Number(props.initialYear) : (el?.dataset?.initialYear ? parseInt(el.dataset.initialYear, 10) : fallbackYear);

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

<style scoped>
.inv-accuracy-form {
  margin-bottom: 0.5rem;
}
.inv-accuracy-filters {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 12px;
}
.inv-accuracy-filters__label {
  font-size: 0.9rem;
  color: #374151;
  white-space: nowrap;
}
.inv-accuracy-filters__select {
  min-width: 0;
  width: auto;
  max-width: 100%;
}
@media (max-width: 360px) {
  .inv-accuracy-filters {
    flex-direction: column;
    align-items: stretch;
  }
  .inv-accuracy-filters__select {
    width: 100%;
  }
}
</style>
