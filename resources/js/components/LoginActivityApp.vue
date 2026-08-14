<template>
  <div class="login-activity-app space-y-6 p-6">
    <div>
      <h1 class="text-xl font-semibold text-slate-800">User login activity</h1>
      <p class="mt-1 text-sm text-slate-600">
        Monitor successful and failed sign-in attempts (password, Microsoft SSO, and legacy LDAP where applicable).
      </p>
    </div>

    <form class="login-activity-filters" @submit.prevent="applyFilters">
      <div class="login-activity-filters__row">
        <div class="login-activity-filters__user-col">
          <div class="login-activity-filters__field login-activity-filters__field--user">
            <label class="login-activity-filters__label" for="laf-user">User / email / id</label>
            <input
              id="laf-user"
              v-model="filters.user"
              type="search"
              class="login-activity-filters__control"
              placeholder="Partial match on user"
              autocomplete="off"
              @keydown.enter.prevent="applyFilters"
            >
          </div>
          <div class="login-activity-filters__actions">
            <button
              type="button"
              class="login-activity-filters__btn login-activity-filters__btn--reset"
              @click="resetFilters"
            >
              Reset
            </button>
            <button
              type="button"
              class="login-activity-filters__btn login-activity-filters__btn--apply"
              @click="applyFilters"
            >
              Apply filters
            </button>
          </div>
        </div>
        <div class="login-activity-filters__field login-activity-filters__field--status">
          <label class="login-activity-filters__label" for="laf-status">Status</label>
          <select
            id="laf-status"
            v-model="filters.status"
            class="login-activity-filters__control"
          >
            <option value="">All</option>
            <option value="success">Success</option>
            <option value="failed">Failed</option>
          </select>
        </div>
        <div class="login-activity-filters__field login-activity-filters__field--date">
          <label class="login-activity-filters__label" for="laf-from">From</label>
          <input
            id="laf-from"
            v-model="filters.date_from"
            type="date"
            class="login-activity-filters__control"
          >
        </div>
        <div class="login-activity-filters__field login-activity-filters__field--date">
          <label class="login-activity-filters__label" for="laf-to">To</label>
          <input
            id="laf-to"
            v-model="filters.date_to"
            type="date"
            class="login-activity-filters__control"
          >
        </div>
      </div>
    </form>

    <div v-if="loading" class="flex justify-center py-10">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700" aria-hidden="true" />
    </div>
    <div v-else-if="error" class="pt-2 text-sm font-medium text-red-700">
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

<style scoped>
.login-activity-filters {
  box-sizing: border-box;
  width: 100%;
  margin: 0;
  padding: 16px;
  border: 1px solid #e5e7eb;
  border-radius: 8px;
  background: #f8fafc;
}

.login-activity-filters__row {
  display: flex;
  flex-direction: row;
  flex-wrap: nowrap;
  align-items: flex-start;
  gap: 12px;
  width: 100%;
}

.login-activity-filters__user-col {
  display: flex;
  flex-direction: column;
  flex: 0 1 280px;
  width: 280px;
  max-width: 280px;
  gap: 8px;
}

.login-activity-filters__field {
  display: flex;
  flex-direction: column;
  min-width: 0;
}

.login-activity-filters__field--user {
  width: 100%;
  max-width: none;
}

.login-activity-filters__field--status {
  flex: 0 0 140px;
  width: 140px;
}

.login-activity-filters__field--date {
  flex: 0 0 150px;
  width: 150px;
}

.login-activity-filters__label {
  display: block;
  margin: 0 0 6px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.06em;
  line-height: 1.2;
  text-transform: uppercase;
  color: #334155;
}

.login-activity-filters__control {
  display: block;
  box-sizing: border-box;
  width: 100%;
  height: 40px;
  margin: 0;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  background: #fff;
  padding: 8px 12px;
  font-size: 14px;
  line-height: 24px;
  color: #0f172a;
  box-shadow: none;
  appearance: none;
  -webkit-appearance: none;
}

select.login-activity-filters__control {
  background-image: url("data:image/svg+xml;charset=UTF-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath fill='%2364748b' d='M1.4.6 6 5.2 10.6.6 12 2 6 8 0 2z'/%3E%3C/svg%3E");
  background-repeat: no-repeat;
  background-position: right 12px center;
  background-size: 10px 7px;
  padding-right: 32px;
}

.login-activity-filters__control::placeholder {
  color: #9ca3af;
}

.login-activity-filters__control:focus {
  outline: none;
  border-color: #0f172a;
  box-shadow: 0 0 0 1px #0f172a;
}

.login-activity-filters__actions {
  display: flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: flex-start;
  gap: 8px;
  width: 100%;
}

.login-activity-filters__btn {
  display: inline-flex;
  flex: 0 0 auto;
  align-items: center;
  justify-content: center;
  box-sizing: border-box;
  width: auto !important;
  min-width: 0;
  max-width: none;
  height: 40px;
  margin: 0;
  border-radius: 6px;
  padding: 0 16px;
  font-size: 14px;
  line-height: 1;
  white-space: nowrap;
  cursor: pointer;
}

.login-activity-filters__btn:focus {
  outline: none;
}

.login-activity-filters__btn:focus-visible {
  box-shadow: 0 0 0 2px #fff, 0 0 0 4px #94a3b8;
}

.login-activity-filters__btn--reset {
  border: 1px solid #d1d5db;
  background: #fff;
  color: #0f172a;
  font-weight: 500;
}

.login-activity-filters__btn--reset:hover {
  background: #f8fafc;
}

.login-activity-filters__btn--apply {
  flex: 0 0 auto;
  width: auto !important;
  border: 1px solid #0f172a;
  background: #0f172a;
  color: #fff;
  font-weight: 600;
}

.login-activity-filters__btn--apply:hover {
  background: #020617;
}

@media (max-width: 900px) {
  .login-activity-filters__row {
    flex-wrap: wrap;
  }

  .login-activity-filters__user-col,
  .login-activity-filters__field--status,
  .login-activity-filters__field--date {
    flex: 1 1 calc(50% - 12px);
    max-width: none;
    width: auto;
  }
}
</style>


