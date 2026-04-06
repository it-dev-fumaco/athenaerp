<template>
  <div class="phase-out-tagged-table">
    <div v-if="loading" class="flex justify-center py-8">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700" aria-hidden="true" />
    </div>
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
      {{ error }}
    </div>
    <div v-else class="overflow-x-auto rounded-lg border border-slate-200 bg-white shadow-sm">
      <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
        <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
          <tr>
            <th class="px-4 py-3">Item code</th>
            <th class="px-4 py-3">Name</th>
            <th class="px-4 py-3">Brand</th>
            <th class="px-4 py-3">Entry date</th>
            <th class="px-4 py-3 text-right">Stock</th>
            <th class="px-4 py-3">Warehouse</th>
            <th class="px-4 py-3">Last movement</th>
            <th v-if="linkToProfile" class="px-4 py-3" />
          </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
          <tr v-for="row in rows" :key="row.name" class="hover:bg-slate-50">
            <td class="whitespace-nowrap px-4 py-2 font-mono text-slate-900">{{ row.name }}</td>
            <td class="max-w-xs truncate px-4 py-2 text-slate-700" :title="row.item_name">{{ row.item_name }}</td>
            <td class="px-4 py-2 text-slate-600">{{ row.brand || '—' }}</td>
            <td class="whitespace-nowrap px-4 py-2 text-slate-600">{{ formatDate(row.creation) }}</td>
            <td class="whitespace-nowrap px-4 py-2 text-right tabular-nums text-slate-900">
              {{ formatQty(row.total_actual_qty) }} {{ row.stock_uom || '' }}
            </td>
            <td class="max-w-[10rem] truncate px-4 py-2 text-slate-600" :title="row.primary_warehouse || ''">
              {{ row.primary_warehouse || '—' }}
            </td>
            <td class="whitespace-nowrap px-4 py-2 text-slate-600">{{ formatDate(row.last_movement_date) }}</td>
            <td v-if="linkToProfile" class="whitespace-nowrap px-4 py-2">
              <a
                :href="profileUrl(row.name)"
                class="text-sky-700 hover:text-sky-900 hover:underline"
                target="_blank"
                rel="noopener noreferrer"
              >Profile</a>
            </td>
          </tr>
          <tr v-if="rows.length === 0">
            <td :colspan="linkToProfile ? 8 : 7" class="px-4 py-8 text-center text-slate-500">
              No items tagged as For Phase Out yet.
            </td>
          </tr>
        </tbody>
      </table>
      <div v-if="meta && meta.last_page > 1" class="flex flex-wrap items-center justify-between gap-2 border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
        <span>Page {{ meta.current_page }} of {{ meta.last_page }} ({{ meta.total }} items)</span>
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-3 py-1 hover:bg-slate-50 disabled:opacity-40"
            :disabled="meta.current_page <= 1"
            @click="goPage(meta.current_page - 1)"
          >
            Previous
          </button>
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-3 py-1 hover:bg-slate-50 disabled:opacity-40"
            :disabled="meta.current_page >= meta.last_page"
            @click="goPage(meta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  perPage: { type: Number, default: 15 },
  linkToProfile: { type: Boolean, default: true },
});

const loading = ref(true);
const error = ref('');
const rows = ref([]);
const meta = ref(null);
const page = ref(1);

function profileUrl(itemCode) {
  return `/get_item_details/${encodeURIComponent(itemCode)}`;
}

function formatDate(v) {
  if (!v) return '—';
  const d = typeof v === 'string' ? v.slice(0, 10) : v;
  return d || '—';
}

function formatQty(n) {
  if (n === null || n === undefined) return '—';
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

async function load(p = 1) {
  loading.value = true;
  error.value = '';
  page.value = p;
  try {
    const { data } = await axios.get('/phase-out/tagged-items', {
      params: { per_page: props.perPage, page: p },
    });
    rows.value = data.data || [];
    meta.value = {
      current_page: data.current_page,
      last_page: data.last_page,
      total: data.total,
      per_page: data.per_page,
    };
  } catch (e) {
    error.value = 'Could not load tagged items.';
    rows.value = [];
    meta.value = null;
  } finally {
    loading.value = false;
  }
}

function goPage(p) {
  load(p);
}

onMounted(() => {
  load(1);
});

watch(
  () => props.perPage,
  () => {
    load(1);
  }
);

defineExpose({ load });
</script>
