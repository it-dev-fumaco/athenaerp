<template>
  <div class="login-activity-app space-y-6 p-6">
    <div>
      <h1 class="text-xl font-semibold text-slate-800">User login activity</h1>
      <p class="mt-1 text-sm text-slate-600">
        Monitor successful and failed sign-in attempts (password, Microsoft SSO, and legacy LDAP where applicable).
      </p>
    </div>

    <div class="flex flex-col gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm lg:flex-row lg:flex-wrap lg:items-end">
      <div class="min-w-0 flex-1">
        <label class="block text-xs font-medium text-slate-500" for="laf-user">User / email / id</label>
        <input
          id="laf-user"
          v-model="filters.user"
          type="search"
          class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
          placeholder="Partial match on username or user id"
          autocomplete="off"
          @keyup.enter="applyFilters"
        >
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500" for="laf-status">Status</label>
        <select
          id="laf-status"
          v-model="filters.status"
          class="mt-1 w-full min-w-[10rem] rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-40"
        >
          <option value="">All</option>
          <option value="success">Success</option>
          <option value="failed">Failed</option>
        </select>
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500" for="laf-from">From</label>
        <input
          id="laf-from"
          v-model="filters.date_from"
          type="date"
          class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-44"
        >
      </div>
      <div>
        <label class="block text-xs font-medium text-slate-500" for="laf-to">To</label>
        <input
          id="laf-to"
          v-model="filters.date_to"
          type="date"
          class="mt-1 w-full rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-900 shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500 lg:w-44"
        >
      </div>
      <div class="flex gap-2">
        <button
          type="button"
          class="rounded-lg bg-slate-800 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-500 focus-visible:ring-offset-2"
          @click="applyFilters"
        >
          Apply
        </button>
        <button
          type="button"
          class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 shadow-sm hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 focus-visible:ring-offset-2"
          @click="resetFilters"
        >
          Reset
        </button>
      </div>
    </div>

    <div v-if="loading" class="flex justify-center py-10">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700" aria-hidden="true" />
    </div>
    <div v-else-if="error" class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
      {{ error }}
    </div>
    <div v-else class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
      <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
          <thead class="bg-slate-50">
            <tr>
              <th class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">Time</th>
              <th class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">Username</th>
              <th class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">User id</th>
              <th class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">Name</th>
              <th class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">IP</th>
              <th class="min-w-[8rem] px-4 py-3 font-semibold text-slate-700">User agent</th>
              <th class="whitespace-nowrap px-4 py-3 font-semibold text-slate-700">Status</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="row in rows" :key="row.id" class="hover:bg-slate-50/80">
              <td class="whitespace-nowrap px-4 py-3 tabular-nums text-slate-800">{{ formatDt(row.login_at) }}</td>
              <td class="max-w-[14rem] truncate px-4 py-3 text-slate-800" :title="row.username">{{ row.username }}</td>
              <td class="max-w-[10rem] truncate px-4 py-3 font-mono text-xs text-slate-600" :title="row.user_id || ''">
                {{ row.user_id || '—' }}
              </td>
              <td class="max-w-[12rem] truncate px-4 py-3 text-slate-700" :title="displayName(row)">
                {{ displayName(row) }}
              </td>
              <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ row.ip_address || '—' }}</td>
              <td class="max-w-xs truncate px-4 py-3 text-xs text-slate-600" :title="row.user_agent || ''">
                {{ row.user_agent || '—' }}
              </td>
              <td class="px-4 py-3">
                <span
                  class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold"
                  :class="row.status === 'success' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                >{{ row.status }}</span>
              </td>
            </tr>
            <tr v-if="rows.length === 0">
              <td colspan="7" class="px-4 py-8 text-center text-sm text-slate-500">No records found.</td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="pagination.last_page > 1" class="flex flex-col items-stretch justify-between gap-3 border-t border-slate-100 px-4 py-3 sm:flex-row sm:items-center">
        <p class="text-xs text-slate-500">
          Page {{ pagination.current_page }} of {{ pagination.last_page }}
          <span v-if="pagination.total != null" class="tabular-nums">({{ pagination.total }} total)</span>
        </p>
        <div class="flex flex-wrap gap-2">
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 disabled:opacity-40"
            :disabled="pagination.current_page <= 1"
            @click="goPage(pagination.current_page - 1)"
          >
            Previous
          </button>
          <button
            type="button"
            class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-700 disabled:opacity-40"
            :disabled="pagination.current_page >= pagination.last_page"
            @click="goPage(pagination.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import axios from 'axios';
import { onMounted, reactive, ref } from 'vue';

const rows = ref([]);
const loading = ref(true);
const error = ref('');

const pagination = reactive({
  current_page: 1,
  last_page: 1,
  total: 0,
});

const filters = reactive({
  user: '',
  status: '',
  date_from: '',
  date_to: '',
  per_page: 20,
});

function displayName(row) {
  const u = row.user;
  if (u && u.full_name) {
    return u.full_name;
  }
  if (u && u.wh_user) {
    return u.wh_user;
  }
  return '—';
}

function formatDt(iso) {
  if (!iso) {
    return '—';
  }
  try {
    const d = new Date(iso);
    if (Number.isNaN(d.getTime())) {
      return iso;
    }
    return d.toLocaleString(undefined, {
      year: 'numeric',
      month: 'short',
      day: '2-digit',
      hour: '2-digit',
      minute: '2-digit',
      second: '2-digit',
    });
  } catch {
    return iso;
  }
}

function queryParams(page) {
  const p = {
    page: page || pagination.current_page,
    per_page: filters.per_page,
  };
  if (filters.user.trim()) {
    p.user = filters.user.trim();
  }
  if (filters.status) {
    p.status = filters.status;
  }
  if (filters.date_from) {
    p.date_from = filters.date_from;
  }
  if (filters.date_to) {
    p.date_to = filters.date_to;
  }
  return p;
}

async function loadPage(page) {
  loading.value = true;
  error.value = '';
  try {
    const { data } = await axios.get('/admin/login-activity/logs', {
      params: queryParams(page),
    });
    rows.value = data.data || [];
    pagination.current_page = data.current_page || 1;
    pagination.last_page = data.last_page || 1;
    pagination.total = data.total != null ? data.total : 0;
  } catch (e) {
    rows.value = [];
    const msg = e.response?.data?.message || e.message || 'Failed to load login activity.';
    error.value = typeof msg === 'string' ? msg : 'Failed to load login activity.';
  } finally {
    loading.value = false;
  }
}

function applyFilters() {
  pagination.current_page = 1;
  loadPage(1);
}

function resetFilters() {
  filters.user = '';
  filters.status = '';
  filters.date_from = '';
  filters.date_to = '';
  filters.per_page = 20;
  pagination.current_page = 1;
  loadPage(1);
}

function goPage(page) {
  if (page < 1 || page > pagination.last_page) {
    return;
  }
  pagination.current_page = page;
  loadPage(page);
}

onMounted(() => {
  loadPage(1);
});
</script>
