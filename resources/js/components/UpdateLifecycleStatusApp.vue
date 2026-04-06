<template>
  <div class="update-lifecycle-status ulc-root flex min-h-full min-w-0 flex-1 flex-col bg-[#eef2f6]">
    <div
      class="mx-auto w-full max-w-[1600px] flex-1 space-y-4 p-4 sm:space-y-5 sm:p-6"
      :class="currentStep === 2 ? 'pb-28 sm:pb-32' : 'pb-8 sm:pb-10'"
    >
      <header class="flex flex-col gap-1">
        <div class="flex flex-wrap items-start gap-3 sm:gap-4">
          <div class="min-w-0 flex-1">
            <h1 class="text-xl font-semibold text-slate-800">
              Mass Update Lifecycle Status
            </h1>
            <p class="mt-1 text-sm text-slate-600">
              Select items to update their lifecycle status to &quot;For Phase Out&quot;.
            </p>
          </div>
        </div>
      </header>

      <!-- Stepper: pointed ribbon; active step highlights after Find Items -->
      <div
        class="ulc-stepper-wrap rounded-sm shadow-sm"
        aria-live="polite"
      >
        <div class="ulc-stepper">
          <div
            class="ulc-stepper__ribbon"
            :class="step1RibbonClass"
            :aria-current="currentStep === 1 ? 'step' : undefined"
          >
            <span class="ulc-stepper__label">Step 1: Filter Items</span>
          </div>
          <div
            class="ulc-stepper__ribbon"
            :class="step2RibbonClass"
            :aria-current="currentStep === 2 ? 'step' : undefined"
          >
            <span class="ulc-stepper__label">Step 2: Review &amp; Confirm</span>
          </div>
        </div>
      </div>

      <!-- Step 1: filters only -->
      <div v-if="currentStep === 1" class="ulc-workspace mx-auto w-full max-w-lg">
        <section class="w-full">
          <div class="ulc-card h-full rounded-lg border border-slate-200/90 bg-white p-5 shadow-[0_1px_3px_rgba(15,23,42,0.06)]">
            <h2 class="text-[0.8125rem] font-bold uppercase tracking-wide text-slate-800">Search Filters</h2>
            <div class="mt-4 space-y-4">
              <div>
                <label class="ulc-field-label">Item Classification</label>
                <select
                  v-model="filters.item_classification"
                  class="ulc-input mt-2 w-full"
                >
                  <option value="">All</option>
                  <option v-for="c in classificationOptions" :key="c" :value="c">{{ c }}</option>
                </select>
              </div>
              <div>
                <label class="ulc-field-label">Brand</label>
                <select
                  v-model="filters.brand"
                  class="ulc-input mt-2 w-full"
                >
                  <option value="">All</option>
                  <option v-for="b in brandOptions" :key="b.id" :value="b.id">{{ b.text }}</option>
                </select>
              </div>
              <div>
                <span class="ulc-field-label block">Last Movement (Days)</span>
                <div class="mt-2 flex flex-wrap items-center gap-2">
                  <input
                    v-model="filters.last_movement_days_min"
                    type="number"
                    min="0"
                    max="36500"
                    placeholder="Min"
                    class="ulc-input min-w-[6rem] flex-1"
                  >
                  <span class="text-slate-400">–</span>
                  <input
                    v-model="filters.last_movement_days_max"
                    type="number"
                    min="0"
                    max="36500"
                    placeholder="Max"
                    class="ulc-input min-w-[6rem] flex-1"
                  >
                </div>
              </div>
            </div>
            <div class="mt-6 flex flex-wrap gap-3 border-t border-slate-100 pt-5">
              <button
                type="button"
                class="ulc-btn-primary inline-flex min-h-10 flex-1 items-center justify-center px-5 sm:flex-none"
                :disabled="listLoading"
                @click="loadItems(1)"
              >
                {{ listLoading ? 'Loading…' : 'Find Items' }}
              </button>
              <button
                type="button"
                class="ulc-btn-secondary inline-flex min-h-10 flex-1 items-center justify-center px-5 sm:flex-none"
                @click="resetFilters"
              >
                Reset
              </button>
            </div>
          </div>
        </section>
      </div>

      <!-- Step 2: items found + back to filters -->
      <div v-else class="ulc-workspace w-full min-w-0 space-y-3">
        <div class="flex flex-wrap items-center justify-between gap-3">
          <h2 class="text-base font-bold text-slate-900">
            Review items
          </h2>
          <button
            type="button"
            class="ulc-btn-secondary inline-flex min-h-9 items-center justify-center px-4 text-sm"
            @click="goToStep1"
          >
            Back to filters
          </button>
        </div>
        <section class="min-w-0 w-full">
          <div class="ulc-card overflow-hidden rounded-lg border border-slate-200/90 bg-white shadow-[0_1px_3px_rgba(15,23,42,0.06)]">
            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-white px-4 py-3.5 sm:px-5">
              <p class="flex flex-wrap items-center gap-2 text-sm font-bold text-slate-900">
                <span class="ulc-count-pill tabular-nums">{{ meta.total }}</span>
                <span>items found</span>
              </p>
              <label v-if="rows.length" class="inline-flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-700">
                <input
                  type="checkbox"
                  class="h-4 w-4 rounded border-slate-300 text-[#1976D2] focus:ring-[#1976D2]"
                  :checked="allPageSelected"
                  @change="toggleAll($event.target.checked)"
                >
                <span>Select All</span>
              </label>
            </div>

            <div v-if="listLoading" class="px-5 py-10 text-center text-sm text-slate-600">
              Loading…
            </div>
            <div v-else-if="listError" class="px-5 py-6 text-sm text-red-700">
              {{ listError }}
            </div>
            <div v-else-if="!rows.length" class="px-5 py-10 text-center text-sm text-slate-600">
              No items match the current filters. Click &quot;Back to filters&quot; to adjust your search.
            </div>
            <div v-else class="overflow-x-auto">
              <table class="min-w-full border-collapse text-left text-sm">
                <thead class="ulc-table-head border-b border-slate-200 text-xs font-bold uppercase tracking-wide text-slate-700">
                  <tr>
                    <th class="w-12 px-3 py-3 text-center" scope="col" />
                    <th class="whitespace-nowrap px-3 py-3" scope="col">Item Code</th>
                    <th class="whitespace-nowrap px-3 py-3" scope="col">Name</th>
                    <th class="whitespace-nowrap px-3 py-3" scope="col">Item Classification</th>
                    <th class="whitespace-nowrap px-3 py-3 text-right tabular-nums" scope="col">Global Stock</th>
                    <th class="whitespace-nowrap px-3 py-3 text-right" scope="col">Last Movement</th>
                    <th class="whitespace-nowrap px-3 py-3" scope="col">Last Purchase</th>
                  </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                  <tr
                    v-for="row in rows"
                    :key="row.item_code"
                    class="ulc-table-row transition-colors hover:bg-slate-50/90"
                    :class="selected[row.item_code] ? 'ulc-table-row--selected' : ''"
                  >
                    <td class="px-3 py-2 align-middle">
                      <input
                        type="checkbox"
                        class="h-4 w-4 rounded border-slate-300 text-[#1976D2] focus:ring-[#1976D2]"
                        :checked="!!selected[row.item_code]"
                        @change="onToggleRow(row.item_code, $event.target.checked)"
                      >
                    </td>
                    <td class="whitespace-nowrap px-3 py-2 font-mono text-sm font-semibold text-slate-900">
                      {{ row.item_code }}
                    </td>
                    <td class="max-w-[12rem] px-3 py-2 text-slate-800 sm:max-w-xs">
                      <span class="line-clamp-2" :title="row.name">{{ row.name }}</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-700">
                      {{ row.item_classification || '—' }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-2 text-right tabular-nums text-slate-900">
                      {{ formatQty(row.global_stock) }}
                    </td>
                    <td class="whitespace-nowrap px-3 py-2 text-right text-slate-800">
                      <span v-if="row.last_movement_days != null" class="tabular-nums">{{ row.last_movement_days }} days</span>
                      <span v-else>—</span>
                    </td>
                    <td class="whitespace-nowrap px-3 py-2 text-slate-600">
                      {{ row.last_purchase != null ? formatDate(row.last_purchase) : '—' }}
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div
              v-if="rows.length && meta.last_page > 1"
              class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-50/50 px-4 py-3"
            >
              <p class="text-xs font-medium text-slate-600">
                Page {{ meta.current_page }} of {{ meta.last_page }}
              </p>
              <div class="flex gap-2">
                <button
                  type="button"
                  class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                  :disabled="meta.current_page <= 1 || listLoading"
                  @click="loadItems(meta.current_page - 1)"
                >
                  Previous
                </button>
                <button
                  type="button"
                  class="rounded-md border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-slate-50 disabled:opacity-50"
                  :disabled="meta.current_page >= meta.last_page || listLoading"
                  @click="loadItems(meta.current_page + 1)"
                >
                  Next
                </button>
              </div>
            </div>
          </div>
        </section>
      </div>
    </div>

    <!-- Step 2 only: apply bar -->
    <footer
      v-if="currentStep === 2"
      class="ulc-footer sticky bottom-0 z-20 border-t border-slate-200/90 bg-white shadow-[0_-8px_24px_rgba(15,23,42,0.08)]"
    >
      <div
        class="mx-auto grid max-w-[1600px] grid-cols-1 items-center gap-4 px-4 py-4 sm:px-6 lg:grid-cols-3 lg:gap-6"
      >
        <p class="text-center text-sm font-semibold text-slate-800 lg:text-left">
          <span class="tabular-nums text-[#1976D2]">{{ selectedCount }}</span>
          <span class="text-slate-700"> items selected</span>
        </p>
        <div class="flex flex-wrap items-center justify-center gap-2 lg:justify-center">
          <label for="new-lifecycle-status" class="text-sm font-bold text-slate-800">Set New Status:</label>
          <select
            id="new-lifecycle-status"
            v-model="newStatus"
            class="ulc-input min-w-[13rem] max-w-full py-2.5 font-medium"
          >
            <option v-for="s in lifecycleStatuses" :key="s" :value="s">{{ s }}</option>
          </select>
        </div>
        <div class="flex flex-wrap items-center justify-center gap-3 lg:justify-end">
          <button
            type="button"
            class="ulc-btn-secondary min-h-10 min-w-[7rem] px-6"
            @click="onFooterCancel"
          >
            Cancel
          </button>
          <button
            type="button"
            class="ulc-btn-primary min-h-10 min-w-[8rem] px-6 disabled:cursor-not-allowed disabled:opacity-50"
            :disabled="selectedCount === 0"
            @click="confirmOpen = true"
          >
            Apply Update
          </button>
        </div>
      </div>
    </footer>

    <MassUpdateConfirmModal
      v-model="confirmOpen"
      :count="selectedCount"
      :status-label="newStatus"
      :confirming="bulkSubmitting"
      @cancel="confirmOpen = false"
      @confirm="submitBulkUpdate"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch } from 'vue';
import axios, { isAxiosError } from 'axios';
import MassUpdateConfirmModal from '@/components/phase-out/MassUpdateConfirmModal.vue';

/** 1 = Search filters only; 2 = Items found + footer actions */
const currentStep = ref(1);

const step1RibbonClass = computed(() =>
  currentStep.value === 1 ? 'ulc-stepper__ribbon--active' : 'ulc-stepper__ribbon--done'
);

const step2RibbonClass = computed(() =>
  currentStep.value === 1 ? 'ulc-stepper__ribbon--next' : 'ulc-stepper__ribbon--activeLast'
);

function goToStep1() {
  currentStep.value = 1;
}

const LIFECYCLE_STATUSES = ['Active', 'For Phase Out', 'Discontinued', 'Obsolete'];

const lifecycleStatuses = LIFECYCLE_STATUSES;
const newStatus = ref('For Phase Out');

const filters = reactive({
  item_classification: '',
  brand: '',
  last_movement_days_min: '',
  last_movement_days_max: '',
});

const classificationOptions = ref([]);
const brandOptions = ref([]);

const rows = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 15,
});

const listLoading = ref(false);
const listError = ref(null);

const selected = reactive({});
const confirmOpen = ref(false);
const bulkSubmitting = ref(false);

const selectedCount = computed(() => Object.keys(selected).filter((k) => selected[k]).length);

const allPageSelected = computed(() => {
  if (!rows.value.length) {
    return false;
  }
  return rows.value.every((r) => selected[r.item_code]);
});

function formatQty(n) {
  if (n === null || n === undefined) {
    return '—';
  }
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

function formatDate(val) {
  if (!val) {
    return '—';
  }
  const d = new Date(val);
  if (Number.isNaN(d.getTime())) {
    return String(val);
  }
  return d.toLocaleDateString();
}

function clearSelection() {
  Object.keys(selected).forEach((k) => {
    delete selected[k];
  });
}

function toggleAll(checked) {
  rows.value.forEach((r) => {
    selected[r.item_code] = checked;
  });
}

function onToggleRow(code, checked) {
  selected[code] = checked;
}

function resetFilters() {
  filters.item_classification = '';
  filters.brand = '';
  filters.last_movement_days_min = '';
  filters.last_movement_days_max = '';
  rows.value = [];
  meta.value = { current_page: 1, last_page: 1, total: 0, per_page: 15 };
  listError.value = null;
  clearSelection();
  currentStep.value = 1;
}

function onFooterCancel() {
  clearSelection();
}

const LIST_TIMEOUT_MS = 60000;

function listErrorMessage(err) {
  if (isAxiosError(err)) {
    if (err.code === 'ECONNABORTED' || err.message?.includes('timeout')) {
      return 'Loading items timed out. Try narrowing filters.';
    }
    if (err.response?.status === 422 && err.response?.data?.errors) {
      const first = Object.values(err.response.data.errors)[0];
      return Array.isArray(first) ? first[0] : String(first);
    }
    if (err.response?.status >= 500) {
      return 'Could not load items. Please try again later.';
    }
  }
  return 'Could not load items. Check your connection and try again.';
}

async function loadItems(page) {
  listLoading.value = true;
  listError.value = null;
  try {
    const params = {
      page: page || 1,
      per_page: meta.value.per_page || 15,
    };
    if (filters.item_classification) {
      params.item_classification = filters.item_classification;
    }
    if (filters.brand) {
      params.brand = filters.brand;
    }
    if (filters.last_movement_days_min !== '' && filters.last_movement_days_min != null) {
      params.last_movement_days_min = Number(filters.last_movement_days_min);
    }
    if (filters.last_movement_days_max !== '' && filters.last_movement_days_max != null) {
      params.last_movement_days_max = Number(filters.last_movement_days_max);
    }

    const { data } = await axios.get('/phase-out/mass-update/items', {
      params,
      timeout: LIST_TIMEOUT_MS,
    });

    rows.value = data.data || [];
    meta.value = {
      current_page: data.current_page ?? 1,
      last_page: data.last_page ?? 1,
      total: data.total ?? 0,
      per_page: data.per_page ?? 15,
    };
    currentStep.value = 2;
  } catch (err) {
    rows.value = [];
    listError.value = listErrorMessage(err);
  } finally {
    listLoading.value = false;
  }
}

async function loadFilterOptions() {
  try {
    const { data } = await axios.get('/get_select_filters', { params: { q: '' } });
    const classes = data.item_classification;
    classificationOptions.value = Array.isArray(classes) ? classes : [];
    const brands = data.brand;
    brandOptions.value = Array.isArray(brands) ? brands : [];
  } catch {
    classificationOptions.value = [];
    brandOptions.value = [];
  }
}

async function submitBulkUpdate() {
  const itemIds = Object.keys(selected).filter((k) => selected[k]);
  if (itemIds.length === 0) {
    return;
  }
  bulkSubmitting.value = true;
  try {
    await axios.post('/items/bulk-tag', {
      itemIds,
      tag: newStatus.value,
    });
    confirmOpen.value = false;
    clearSelection();
    if (meta.value.current_page) {
      await loadItems(meta.value.current_page);
    }
    window.alert('Lifecycle status updated successfully.');
  } catch {
    window.alert('Update failed. Please try again.');
  } finally {
    bulkSubmitting.value = false;
  }
}

watch(confirmOpen, (open) => {
  if (!open) {
    bulkSubmitting.value = false;
  }
});

onMounted(() => {
  loadFilterOptions();
});
</script>

<style scoped>
/* Reference: light workspace, blue ribbon stepper, white cards, bold labels */
.ulc-root {
  font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.ulc-field-label {
  display: block;
  font-size: 0.6875rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  color: #475569;
}

.ulc-input {
  border-radius: 0.375rem;
  border: 1px solid #cbd5e1;
  background-color: #fff;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #0f172a;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
}

.ulc-input:focus {
  outline: none;
  border-color: #1976d2;
  box-shadow: 0 0 0 1px #1976d2;
}

.ulc-btn-primary {
  border-radius: 0.375rem;
  border: none;
  background: linear-gradient(180deg, #1e88e5 0%, #1976d2 100%);
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  box-shadow: 0 1px 2px rgba(25, 118, 210, 0.35);
}

.ulc-btn-primary:hover:not(:disabled) {
  background: linear-gradient(180deg, #1976d2 0%, #1565c0 100%);
}

.ulc-btn-primary:disabled {
  opacity: 0.55;
}

.ulc-btn-secondary {
  border-radius: 0.375rem;
  border: 1px solid #cbd5e1;
  background-color: #fff;
  font-size: 0.875rem;
  font-weight: 600;
  color: #334155;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}

.ulc-btn-secondary:hover {
  background-color: #f8fafc;
}

/* Pointed ribbon stepper */
.ulc-stepper-wrap {
  overflow: hidden;
  background: #fff;
  border: 1px solid #e2e8f0;
}

.ulc-stepper {
  display: flex;
  min-height: 3rem;
  width: 100%;
}

.ulc-stepper__ribbon {
  position: relative;
  display: flex;
  flex: 1;
  align-items: center;
  justify-content: center;
  padding: 0.625rem 1.25rem;
  text-align: center;
}

.ulc-stepper__ribbon--active {
  z-index: 2;
  margin-right: -1.125rem;
  background: linear-gradient(180deg, #1e88e5 0%, #1976d2 55%, #1565c0 100%);
  color: #fff;
  padding-right: 1.75rem;
  clip-path: polygon(0 0, calc(100% - 18px) 0, 100% 50%, calc(100% - 18px) 100%, 0 100%);
  box-shadow: 2px 0 6px rgba(25, 118, 210, 0.25);
}

.ulc-stepper__ribbon--next {
  z-index: 1;
  margin-left: 0;
  padding-left: 2rem;
  background: linear-gradient(180deg, #f1f5f9 0%, #e2e8f0 100%);
  color: #64748b;
  clip-path: polygon(18px 0, 100% 0, 100% 100%, 18px 100%, 0 50%);
}

/* Step 1 completed (when on step 2) */
.ulc-stepper__ribbon--done {
  z-index: 1;
  margin-right: -1.125rem;
  background: linear-gradient(180deg, #cbd5e1 0%, #94a3b8 100%);
  color: #1e293b;
  padding-right: 1.75rem;
  clip-path: polygon(0 0, calc(100% - 18px) 0, 100% 50%, calc(100% - 18px) 100%, 0 100%);
  box-shadow: none;
}

/* Step 2 active (right segment) */
.ulc-stepper__ribbon--activeLast {
  z-index: 2;
  margin-left: 0;
  padding-left: 2rem;
  background: linear-gradient(180deg, #1e88e5 0%, #1976d2 55%, #1565c0 100%);
  color: #fff;
  clip-path: polygon(18px 0, 100% 0, 100% 100%, 18px 100%, 0 50%);
  box-shadow: 2px 0 6px rgba(25, 118, 210, 0.25);
}

.ulc-stepper__label {
  font-size: 0.8125rem;
  font-weight: 700;
  letter-spacing: 0.02em;
}

@media (max-width: 640px) {
  .ulc-stepper__label {
    font-size: 0.75rem;
  }

  .ulc-stepper__ribbon--active {
    padding-right: 1.25rem;
    clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
  }

  .ulc-stepper__ribbon--next {
    padding-left: 1.5rem;
    clip-path: polygon(14px 0, 100% 0, 100% 100%, 14px 100%, 0 50%);
  }

  .ulc-stepper__ribbon--done {
    padding-right: 1.25rem;
    clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
  }

  .ulc-stepper__ribbon--activeLast {
    padding-left: 1.5rem;
    clip-path: polygon(14px 0, 100% 0, 100% 100%, 14px 100%, 0 50%);
  }
}

.ulc-count-pill {
  display: inline-flex;
  min-width: 1.75rem;
  align-items: center;
  justify-content: center;
  border-radius: 9999px;
  background: linear-gradient(180deg, #e3f2fd 0%, #bbdefb 100%);
  padding: 0.125rem 0.5rem;
  font-size: 0.8125rem;
  font-weight: 800;
  color: #1565c0;
  box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
}

.ulc-table-head {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
}

.ulc-table-row--selected {
  background-color: rgba(227, 242, 253, 0.65) !important;
  box-shadow: inset 3px 0 0 #1976d2;
}

.ulc-footer {
  backdrop-filter: blur(8px);
}
</style>
