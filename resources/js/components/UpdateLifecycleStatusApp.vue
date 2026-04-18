<template>
  <div class="update-lifecycle-status ulc-root font-sans flex min-h-0 min-w-0 flex-1 flex-col bg-[#f4f7f9]">
    <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6">
      <div class="ulc-shell flex flex-col rounded-lg bg-white shadow-[0_2px_12px_rgba(15,23,42,0.08)]">
        <!-- Header -->
        <header class="border-b border-[#e5e7eb] px-5 sm:px-6 sm:pb-5" style="padding-top: 0.5rem;">
          <div class="flex flex-wrap items-start gap-3">
            <div class="min-w-0 flex-1">
              <h1 class="ulc-page-title font-bold leading-snug text-[#1f2937]">
                Mass Update Lifecycle Status
              </h1>
              <p class="ulc-page-subtitle mt-2 leading-snug text-[#6b7280]">
                Select items to update their lifecycle status.
              </p>
            </div>
          </div>
        </header>

        <!-- Stepper -->
        <div class="ulc-stepper-wrap border-b border-[#e5e7eb]" aria-live="polite">
          <div class="ulc-stepper">
            <div
              :class="[
                'ulc-stepper__step',
                'ulc-stepper__step--first',
                workflowStep === 1 ? 'is-active' : 'is-inactive',
              ]"
              :aria-current="workflowStep === 1 ? 'step' : undefined"
            >
              <span class="ulc-stepper__label">1 Select Items</span>
            </div>
            <div
              :class="[
                'ulc-stepper__step',
                'ulc-stepper__step--second',
                workflowStep === 2 ? 'is-active' : 'is-inactive',
              ]"
              :aria-current="workflowStep === 2 ? 'step' : undefined"
            >
              <span class="ulc-stepper__label">2 Review &amp; Confirm</span>
            </div>
          </div>
        </div>

        <!-- Main grid: Step 1 filters + table | Step 2 review full width -->
        <div class="ulc-main-grid">
          <template v-if="workflowStep === 1">
            <!-- Left: Search Filters -->
            <aside class="ulc-panel ulc-panel--left rounded-lg border border-[#d1d5db] bg-white p-4 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
              <h2 class="ulc-section-title text-[#1f2937]">
                Search Filters
              </h2>
              <div class="mt-3 space-y-3">
                <div class="min-w-0">
                  <label class="ulc-field-label" for="ulc-classification">Item Classification</label>
                  <select
                    id="ulc-classification"
                    v-model="filters.item_classification"
                    class="ulc-input mt-1 w-full max-w-full min-w-0"
                  >
                    <option value="">
                      Select classification
                    </option>
                    <option v-for="c in classificationOptions" :key="c" :value="c">{{ c }}</option>
                  </select>
                </div>
                <div class="min-w-0">
                  <label class="ulc-field-label" for="ulc-brand">Brand</label>
                  <select
                    id="ulc-brand"
                    v-model="filters.brand"
                    class="ulc-input mt-1 w-full max-w-full min-w-0"
                  >
                    <option value="">
                      Select brand
                    </option>
                    <option v-for="b in brandOptions" :key="b.id" :value="b.id">{{ b.text }}</option>
                  </select>
                </div>
                <div class="min-w-0">
                  <label class="ulc-field-label" for="ulc-last-movement-days">Last Movement (Days)</label>
                  <select
                    id="ulc-last-movement-days"
                    v-model="filters.last_movement_days"
                    class="ulc-input mt-1 w-full max-w-full min-w-0"
                  >
                    <option v-for="o in lastMovementOptions" :key="o.value" :value="o.value">
                      {{ o.label }}
                    </option>
                  </select>
                </div>
                <div class="min-w-0">
                  <label class="ulc-field-label" for="ulc-entry-year">Entry Date (Year)</label>
                  <input
                    id="ulc-entry-year"
                    v-model="filters.entry_year"
                    type="number"
                    inputmode="numeric"
                    min="1900"
                    max="2999"
                    step="1"
                    placeholder="yyyy"
                    class="ulc-input mt-1 w-full max-w-full min-w-0"
                  >
                </div>
              </div>
              <div class="flex gap-4 border-t border-[#f3f4f6] pt-4">
                <button
                  type="button"
                  class="ulc-btn-primary ulc-filter-btn-primary inline-flex min-h-10 min-w-0 flex-1 items-center justify-center px-5 mb-2"
                  :disabled="listLoading"
                  @click="loadItems(1)"
                >
                  {{ listLoading ? 'Loading…' : 'Find Items' }}
                </button>
                <button
                  type="button"
                  class="ulc-btn-secondary ulc-filter-btn-secondary inline-flex min-h-10 min-w-0 flex-1 items-center justify-center px-5"
                  @click="resetFilters"
                >
                  Reset
                </button>
              </div>
            </aside>

            <!-- Right: Items found + table -->
            <section class="ulc-panel ulc-panel--right flex min-h-0 min-w-0 flex-col overflow-hidden rounded-lg border border-[#d1d5db] bg-white shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
              <div
                class="ulc-results-top-row shrink-0 border-b border-[#e5e7eb] bg-white px-5"
                :class="rows.length ? 'justify-between pt-3 pb-2.5' : 'py-4'"
              >
                <template v-if="rows.length">
                  <p class="ulc-results-top-items-found m-0 leading-snug text-[#1f2937]">
                    <span class="tabular-nums text-xl font-bold sm:text-2xl">{{ meta.total }}</span>
                    <span class="text-base font-normal sm:text-lg"> Items found</span>
                  </p>
                  <div class="ulc-results-top-status shrink-0">
                    <label for="ulc-step1-new-status" class="ulc-field-label m-0 whitespace-nowrap">
                      Set New Status
                    </label>
                    <select
                      id="ulc-step1-new-status"
                      v-model="newStatus"
                      class="ulc-input ulc-status-select w-64"
                    >
                      <option v-for="s in lifecycleStatuses" :key="s" :value="s">{{ s }}</option>
                    </select>
                  </div>
                </template>
              </div>

              <div
                v-if="listLoading"
                class="ulc-panel-placeholder flex min-h-[min(360px,55vh)] items-center justify-center px-5 py-12 text-center text-sm text-[#6b7280]"
              >
                Loading…
              </div>
              <div
                v-else-if="listError"
                class="ulc-panel-placeholder min-h-[min(360px,55vh)] px-5 py-6 text-sm text-red-700"
              >
                {{ listError }}
              </div>
              <div
                v-else-if="!rows.length"
                class="ulc-panel-placeholder flex min-h-[min(360px,55vh)] items-center justify-center px-5 py-12 text-center text-sm text-[#6b7280]"
              >
                No items loaded yet. Set filters and click &quot;Find Items&quot;, or no items match the current filters.
              </div>
              <div v-else class="ulc-table-with-select-all flex min-h-0 min-w-0 grow-0 flex-col justify-start gap-0">
                <div ref="selectAllRowEl" class="ulc-select-all-above-table">
                  <input
                    id="ulc-select-all"
                    type="checkbox"
                    class="ulc-checkbox ulc-select-all-checkbox h-4 w-4 shrink-0 rounded border-[#cbd5e1]"
                    :checked="allPageSelected"
                    @change="toggleAll($event.target.checked)"
                  >
                  <label
                    for="ulc-select-all"
                    class="ulc-select-all-control inline-flex cursor-pointer items-center whitespace-nowrap"
                  >
                    <span class="ulc-select-all-label whitespace-nowrap">Select All</span>
                  </label>
                  <span class="ulc-select-all-count whitespace-nowrap">
                    {{ selectedCount }} of {{ meta.total }} items selected
                  </span>
                </div>
                <div class="ulc-table-viewport min-h-0">
                  <table ref="resultsTableEl" class="ulc-data-table ulc-data-table--step1">
                  <thead class="ulc-table-head-text text-[#374151]">
                    <tr>
                      <th class="ulc-table-th" scope="col" />
                      <th class="ulc-table-th" scope="col">Item Code</th>
                      <th class="ulc-table-th" scope="col">Name</th>
                      <th class="ulc-table-th max-w-[8rem] whitespace-nowrap" scope="col">Item Classification</th>
                      <th class="ulc-table-th ulc-table-th--num" scope="col">Global Stock</th>
                      <th class="ulc-table-th ulc-table-th--num" scope="col">Last Movement</th>
                      <th class="ulc-table-th ulc-table-th--num" scope="col">Entry Date</th>
                    </tr>
                  </thead>
                  <tbody class="bg-white">
                    <tr
                      v-for="row in rows"
                      :key="row.item_code"
                      class="ulc-table-row transition-colors hover:bg-[#f9fafb]"
                      :class="selected[row.item_code] ? 'ulc-table-row--selected' : ''"
                    >
                      <td class="align-middle">
                        <input
                          type="checkbox"
                          class="ulc-checkbox h-4 w-4 rounded border-[#cbd5e1]"
                          :checked="!!selected[row.item_code]"
                          @change="onToggleRow(row, $event.target.checked)"
                        >
                      </td>
                      <td class="whitespace-nowrap font-bold text-[#1f2937]">
                        {{ row.item_code }}
                      </td>
                      <td class="max-w-[12rem] min-w-0 text-[#1f2937] sm:max-w-xs">
                        <span class="line-clamp-2 break-words" :title="row.name">{{ row.name }}</span>
                      </td>
                      <td class="whitespace-nowrap text-[#374151]">
                        {{ row.item_classification || '—' }}
                      </td>
                      <td class="ulc-table-td--num whitespace-nowrap tabular-nums text-[#1f2937]">
                        {{ formatQty(row.global_stock) }}
                      </td>
                      <td class="ulc-table-td--num whitespace-nowrap text-[#1f2937]">
                        {{ formatDateLong(row.last_movement_date) }}
                      </td>
                      <td class="ulc-table-td--num whitespace-nowrap text-[#1f2937]">
                        {{ formatDateLong(row.entry_date) }}
                      </td>
                    </tr>
                  </tbody>
                </table>
                </div>
              </div>

              <div
                v-if="rows.length"
                class="ulc-step1-table-footer flex shrink-0 items-end justify-between gap-4 border-t border-[#e5e7eb] bg-[#f9fafb] px-4 py-3"
              >
                <div class="flex min-w-0 flex-col items-start gap-2">
                  <template v-if="meta.last_page > 1">
                    <p class="m-0 text-xs font-medium text-[#6b7280]">
                      Page {{ meta.current_page }} of {{ meta.last_page }}
                    </p>
                    <div class="flex flex-wrap gap-2">
                      <button
                        v-if="meta.current_page > 1"
                        type="button"
                        class="rounded-md border border-[#d1d5db] bg-white px-3 py-1.5 text-sm font-medium text-[#1f2937] shadow-sm transition hover:bg-[#f9fafb] disabled:opacity-50"
                        :disabled="listLoading"
                        @click="loadItems(meta.current_page - 1)"
                      >
                        Previous
                      </button>
                      <button
                        type="button"
                        class="rounded-md border border-[#d1d5db] bg-white px-3 py-1.5 text-sm font-medium text-[#1f2937] shadow-sm transition hover:bg-[#f9fafb] disabled:opacity-50 mb-2 mt-2"
                        :disabled="meta.current_page >= meta.last_page || listLoading"
                        @click="loadItems(meta.current_page + 1)"
                      >
                        Next
                      </button>
                    </div>
                  </template>
                </div>
                <button
                  type="button"
                  class="ulc-btn-primary ulc-filter-btn-primary inline-flex min-h-10 shrink-0 items-center justify-center px-6 disabled:cursor-not-allowed disabled:opacity-50"
                  :disabled="selectedCount === 0"
                  @click="proceedToReview"
                >
                  Proceed to Review
                </button>
              </div>
            </section>
          </template>

          <!-- Step 2: Review -->
          <div
            v-else
            class="ulc-review-panel col-span-full min-w-0 rounded-lg border border-[#d1d5db] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)] sm:p-6"
          >
            <div class="ulc-review-top-row">
              <button
                type="button"
                class="ulc-btn-secondary inline-flex min-h-9 items-center px-4 text-sm"
                @click="backToSelection"
              >
                ← Back to selection
              </button>
              <h2 class="ulc-section-title m-0 text-[#1f2937]">
                <span class="tabular-nums">{{ selectedCount }}</span>
                {{ selectedCount === 1 ? ' item' : ' items' }} selected
              </h2>
              <span class="ulc-review-status-badge">
                <span class="ulc-review-status-label">New status:</span>
                {{ newStatus }}
              </span>
            </div>

            <p class="ulc-review-warning-inline">
              <span class="ulc-review-warning-inline-icon" aria-hidden="true">⚠️</span>
              <span>
                This action will update the lifecycle status of all selected items.
                This may affect sales and availability.
              </span>
            </p>

            <div class="ulc-review-table-wrap mt-2 max-h-[min(400px,50vh)] overflow-auto rounded border border-[#e5e7eb]">
              <table class="ulc-data-table">
                <thead class="ulc-table-head-text text-[#374151]">
                  <tr>
                    <th class="ulc-table-th" scope="col">Item Code</th>
                    <th class="ulc-table-th" scope="col">Name</th>
                  </tr>
                </thead>
                <tbody class="bg-white">
                  <tr
                    v-for="r in selectedRowsForReview"
                    :key="r.item_code"
                    class="ulc-table-row"
                  >
                    <td class="whitespace-nowrap font-semibold text-[#1f2937]">
                      {{ r.item_code }}
                    </td>
                    <td class="min-w-0 text-[#1f2937]">
                      <span class="line-clamp-3 break-words" :title="r.name">{{ r.name }}</span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

          </div>
        </div>

        <!-- Footer: Step 2 only, sticky -->
        <footer
          v-if="workflowStep === 2 && rows.length > 0"
          class="ulc-footer-inner shrink-0 border-t border-[#e5e7eb] bg-white px-5 py-4 sm:px-6"
        >
          <div class="ulc-footer-summary">
            <span class="ulc-footer-clear-badge" aria-hidden="true">×</span>
            <span class="ulc-footer-summary-strong">
              <span class="tabular-nums text-[#3b5998]">{{ selectedCount }}</span> items selected
            </span>
            <svg
              class="ulc-footer-info-icon"
              xmlns="http://www.w3.org/2000/svg"
              viewBox="0 0 24 24"
              fill="none"
              stroke="currentColor"
              stroke-width="2"
              stroke-linecap="round"
              stroke-linejoin="round"
              aria-hidden="true"
            >
              <circle cx="12" cy="12" r="10" />
              <path d="M12 16v-4M12 8h.01" />
            </svg>
            <span class="ulc-footer-summary-muted">{{ selectedCount }} of {{ meta.total }} items selected</span>
          </div>
          <div class="ulc-footer-actions">
            <div class="ulc-footer-actions-left">
              <p class="ulc-footer-count-muted">
                {{ selectedCount }} items selected
              </p>
              <div class="ulc-footer-status-field">
                <span
                  id="new-lifecycle-status-label"
                  class="ulc-footer-status-label"
                >Set New Status:</span>
                <select
                  id="new-lifecycle-status"
                  v-model="newStatus"
                  class="ulc-input ulc-status-select ulc-footer-status-select"
                  aria-labelledby="new-lifecycle-status-label"
                >
                  <option v-for="s in lifecycleStatuses" :key="s" :value="s">{{ s }}</option>
                </select>
              </div>
            </div>
            <div class="ulc-footer-actions-right">
              <button
                type="button"
                class="ulc-btn-secondary min-h-10 min-w-[7rem] px-6"
                @click="onFooterCancel"
              >
                Cancel
              </button>
              <button
                type="button"
                class="ulc-btn-apply-solid min-h-10 min-w-[8rem] px-6 disabled:cursor-not-allowed disabled:opacity-50"
                :disabled="selectedCount === 0"
                @click="confirmOpen = true"
              >
                Apply Update
              </button>
            </div>
          </div>
        </footer>
      </div>
    </div>

    <ConfirmMassUpdateModal
      :is-open="confirmOpen"
      :count="selectedCount"
      :status-label="newStatus"
      :warning-message="massUpdateModalWarning"
      :on-cancel="() => (confirmOpen = false)"
      :on-confirm="submitBulkUpdate"
    />
  </div>
</template>

<script setup>
import { ref, reactive, computed, onBeforeUnmount, onMounted, nextTick, watch } from 'vue';
import axios, { isAxiosError } from 'axios';
import ConfirmMassUpdateModal from '@/components/phase-out/ConfirmMassUpdateModal.vue';

const lastMovementOptions = [
  { label: 'All', value: '' },
  { label: '< 30 days', value: '30' },
  { label: '< 60 days', value: '60' },
  { label: '< 90 days', value: '90' },
  { label: '< 120 days', value: '120' },
  { label: '< 150 days', value: '150' },
  { label: '< 300 days', value: '300' },
  { label: '< 1 year', value: '365' },
];

const LIFECYCLE_STATUSES = ['Active', 'For Phase Out', 'Discontinued', 'Obsolete'];

const lifecycleStatuses = LIFECYCLE_STATUSES;
const newStatus = ref('For Phase Out');

const massUpdateModalWarning =
  'This will update the lifecycle status of all selected items and may affect sales and availability.';

const filters = reactive({
  item_classification: '',
  brand: '',
  last_movement_days: '',
  entry_year: '',
});

/** Must match PhaseOutReportService::applyMassUpdateDefaultExclusions() — not selectable in this flow. */
const MASS_UPDATE_EXCLUDED_ITEM_CLASSIFICATIONS = new Set([
  'FY - Factory Supplies',
  'MS - Maintenance Supplies',
  'OS - Office Supplies',
  'SC - Service Charge',
  'MP - Ms Plate',
  'PA - Paints',
  'CH - Chemicals',
  'MD - Medicines',
  'FR - Factory Repair',
  'MA - Maintenance',
]);

function filterMassUpdateClassificationOptions(list) {
  if (!Array.isArray(list)) {
    return [];
  }
  return list.filter((c) => {
    const v = c == null ? '' : String(c).trim();
    return v !== '' && !MASS_UPDATE_EXCLUDED_ITEM_CLASSIFICATIONS.has(v);
  });
}

const classificationOptions = ref([]);
const brandOptions = ref([]);

const rows = ref([]);
const meta = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
  per_page: 20,
});

const listLoading = ref(false);
const listError = ref(null);

const workflowStep = ref(1);
const selected = reactive({});
/** @type {Record<string, string>} */
const selectedItemNames = reactive({});
const confirmOpen = ref(false);
const bulkSubmitting = ref(false);

const selectedCount = computed(() => Object.keys(selected).filter((k) => selected[k]).length);

const selectedRowsForReview = computed(() =>
  Object.keys(selected)
    .filter((k) => selected[k])
    .sort()
    .map((item_code) => ({
      item_code,
      name: selectedItemNames[item_code] ?? '—',
    })),
);

const allPageSelected = computed(() => {
  if (!rows.value.length) {
    return false;
  }
  return rows.value.every((r) => selected[r.item_code]);
});

const resultsTableEl = ref(null);
const selectAllRowEl = ref(null);

let pendingSelectAllSync = false;
function queueSelectAllSync() {
  if (pendingSelectAllSync) {
    return;
  }
  pendingSelectAllSync = true;
  nextTick(() => {
    pendingSelectAllSync = false;
    syncSelectAllColumnWidths();
  });
}

function syncSelectAllColumnWidths() {
  // Select-all row is a single flex line; no column-width sync needed.
  if (!resultsTableEl.value || !selectAllRowEl.value) {
    return;
  }
}

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

function formatDateLong(val) {
  if (!val) {
    return '—';
  }
  const d = new Date(val);
  if (Number.isNaN(d.getTime())) {
    return String(val);
  }
  return d.toLocaleDateString('en-US', { month: 'long', day: 'numeric', year: 'numeric' });
}

function clearSelection() {
  Object.keys(selected).forEach((k) => {
    delete selected[k];
  });
  Object.keys(selectedItemNames).forEach((k) => {
    delete selectedItemNames[k];
  });
}

function toggleAll(checked) {
  rows.value.forEach((r) => {
    const code = r.item_code;
    selected[code] = checked;
    if (checked) {
      selectedItemNames[code] = r.name ?? '';
    } else {
      delete selectedItemNames[code];
    }
  });
}

function onToggleRow(row, checked) {
  const code = row.item_code;
  selected[code] = checked;
  if (checked) {
    selectedItemNames[code] = row.name ?? '';
  } else {
    delete selectedItemNames[code];
  }
}

function proceedToReview() {
  if (selectedCount.value < 1) {
    return;
  }
  workflowStep.value = 2;
}

function backToSelection() {
  workflowStep.value = 1;
}

function resetFilters() {
  workflowStep.value = 1;
  filters.item_classification = '';
  filters.brand = '';
  filters.last_movement_days = '';
  filters.entry_year = '';
  rows.value = [];
  meta.value = { current_page: 1, last_page: 1, total: 0, per_page: 20 };
  listError.value = null;
  clearSelection();
}

function onFooterCancel() {
  workflowStep.value = 1;
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
      per_page: meta.value.per_page || 20,
    };
    if (filters.item_classification) {
      params.item_classification = filters.item_classification;
    }
    if (filters.brand) {
      params.brand = filters.brand;
    }
    if (filters.last_movement_days !== '' && filters.last_movement_days != null) {
      params.last_movement_days = Number(filters.last_movement_days);
    }
    if (filters.entry_year !== '' && filters.entry_year != null) {
      params.entry_year = Number(filters.entry_year);
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
      per_page: data.per_page ?? 20,
    };
    queueSelectAllSync();
    // Refresh names for any selected codes visible on this page
    rows.value.forEach((r) => {
      if (selected[r.item_code]) {
        selectedItemNames[r.item_code] = r.name ?? '';
      }
    });
  } catch (err) {
    rows.value = [];
    listError.value = listErrorMessage(err);
  } finally {
    listLoading.value = false;
    queueSelectAllSync();
  }
}

async function loadFilterOptions() {
  try {
    const { data } = await axios.get('/get_select_filters', { params: { q: '' } });
    const classes = data.item_classification;
    const filtered = filterMassUpdateClassificationOptions(Array.isArray(classes) ? classes : []);
    classificationOptions.value = filtered;
    if (filters.item_classification && !filtered.includes(filters.item_classification)) {
      filters.item_classification = '';
    }
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
    workflowStep.value = 1;
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

watch(
  () => rows.value,
  () => {
    queueSelectAllSync();
  },
);

watch(
  () => meta.value.per_page,
  () => {
    queueSelectAllSync();
  },
);

onMounted(() => {
  loadFilterOptions();
  window.addEventListener('resize', queueSelectAllSync);
});

onBeforeUnmount(() => {
  window.removeEventListener('resize', queueSelectAllSync);
});
</script>

<style scoped>
.ulc-root {
  box-sizing: border-box;
  font-family:
    'Inter',
    ui-sans-serif,
    system-ui,
    -apple-system,
    'Segoe UI',
    Roboto,
    'Helvetica Neue',
    Arial,
    sans-serif;
}

.ulc-root *,
.ulc-root *::before,
.ulc-root *::after {
  box-sizing: border-box;
}

.ulc-shell {
  border-radius: 0.5rem;
}

.ulc-title-badge {
  background: linear-gradient(180deg, #4a6fb5 0%, #3b5998 100%);
  box-shadow: 0 1px 2px rgba(59, 89, 152, 0.35);
}

.ulc-page-title {
  font-size: 26px;
}

.ulc-page-subtitle {
  font-size: 13px;
}

.ulc-main-grid {
  position: relative;
  z-index: 1;
  display: grid;
  width: 100%;
  min-width: 0;
  align-items: start;
  gap: 1.5rem;
  padding: 1.5rem;
  isolation: isolate;
  grid-template-columns: 300px minmax(0, 1fr);
}

.ulc-review-panel.col-span-full {
  grid-column: 1 / -1;
}

.ulc-section-title {
  margin: 0;
  font-size: 1.125rem;
  font-weight: 700;
  line-height: 1.35;
}

.ulc-review-top-row {
  display: flex;
  width: 100%;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
}

.ulc-review-status-badge {
  display: inline-flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.375rem;
  border-radius: 999px;
  border: 1px solid #bfdbfe;
  background: #eff6ff;
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
  font-weight: 600;
  color: #1e40af;
}

.ulc-review-status-label {
  font-weight: 700;
}

.ulc-review-warning-inline {
  display: flex;
  align-items: flex-start;
  gap: 0.375rem;
  margin: 0.5rem 0 0.75rem;
  color: #6b7280;
  font-size: 0.8125rem;
  line-height: 1.35;
}

.ulc-review-warning-inline-icon {
  flex-shrink: 0;
  line-height: 1;
}

.ulc-panel--left,
.ulc-panel--right {
  align-self: start;
  max-width: 100%;
}

.ulc-panel--left {
  width: 100%;
  min-width: 0;
  overflow-x: hidden;
}

.ulc-panel--left select.ulc-input {
  width: 100% !important;
  max-width: 100% !important;
  min-width: 0 !important;
}

.ulc-panel--left input.ulc-input[type='number'] {
  width: 100%;
  max-width: 100%;
  min-width: 0;
}

.ulc-panel--right {
  width: 100%;
  min-width: 0;
}

.ulc-table-viewport {
  max-height: none;
  min-height: 0;
  overflow-x: auto;
  overflow-y: visible;
  -webkit-overflow-scrolling: touch;
}

/* Results header row: items found (left) vs status (right) */
.ulc-results-top-row {
  display: flex;
  flex-wrap: nowrap;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
}

.ulc-results-top-status {
  display: inline-flex;
  flex-wrap: nowrap;
  align-items: center;
  gap: 10px;
}

.ulc-results-top-status .ulc-status-select {
  width: 16rem;
}

.ulc-select-all-above-table {
  display: flex;
  align-items: center;
  justify-content: flex-start;
  gap: 12px;
  min-width: 0;
  margin: 0;
  padding-left: 1.25rem;
  padding-right: 1.25rem;
}

.ulc-select-all-above-table .ulc-select-all-label {
  font-weight: 600;
  font-size: 0.875rem;
  color: #1f2937;
}

.ulc-select-all-above-table .ulc-select-all-count {
  color: #6b7280;
  font-size: 13px;
}

.ulc-step1-status-block {
  margin-bottom: 16px;
}

/* Select All row */
.ulc-select-all-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.ulc-select-all-checkbox {
  appearance: none;
  -webkit-appearance: none;
  background-color: #fff;
  border: 1px solid #cbd5e1;
  border-radius: 0.25rem;
  width: 1rem;
  height: 1rem;
  display: inline-grid;
  place-content: center;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
}

.ulc-select-all-control {
  gap: 0.5rem;
}

.ulc-select-all-checkbox:checked {
  background-color: #3b5998;
  border-color: #3b5998;
}

.ulc-select-all-checkbox:checked::after {
  content: '';
  width: 0.55rem;
  height: 0.55rem;
  background: #fff;
  clip-path: polygon(14% 54%, 0 68%, 36% 100%, 100% 22%, 86% 8%, 36% 74%);
}

.ulc-select-all-checkbox:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 89, 152, 0.25);
}

/* Data table grid */
.ulc-data-table {
  border-collapse: collapse;
  width: 100%;
  table-layout: auto;
  font-size: 0.875rem;
  line-height: 1.4;
}

.ulc-data-table :is(th, td) {
  border: 1px solid #e5e7eb;
  padding: 10px 14px;
  text-align: left;
  vertical-align: middle;
}

.ulc-data-table thead tr {
  background: #f1f5f9;
  font-weight: 600;
}

.ulc-data-table tbody tr:nth-child(even) {
  background: #f9fafb;
}

.ulc-data-table th:first-child,
.ulc-data-table td:first-child {
  width: 40px;
  text-align: center;
  padding: 10px;
}

/* Review table (no checkbox column): reset first-column narrow rule */
.ulc-review-table-wrap .ulc-data-table th:first-child,
.ulc-review-table-wrap .ulc-data-table td:first-child {
  width: auto;
  text-align: left;
  padding: 10px 14px;
}

.ulc-table-th {
  position: sticky;
  top: 0;
  z-index: 2;
  background: #f1f5f9;
  box-shadow: 0 1px 0 #e5e7eb;
}

.ulc-table-th--num,
.ulc-table-td--num {
  text-align: right;
}

.ulc-table-head-text {
  font-size: 0.875rem;
}

.ulc-data-table tbody td {
  font-size: 0.875rem;
}

/* Step 1 list: taller rows (more vertical padding), same borders as base table */
.ulc-data-table--step1 :is(th, td) {
  padding: 16px 18px;
}

.ulc-data-table--step1 th:first-child,
.ulc-data-table--step1 td:first-child {
  padding: 16px;
}

@media (max-width: 1024px) {
  .ulc-main-grid {
    grid-template-columns: minmax(0, 1fr);
  }
}

.ulc-field-label {
  display: block;
  font-size: 0.875rem;
  font-weight: 600;
  line-height: 1.35;
  color: #374151;
}

.ulc-input {
  border-radius: 0.375rem;
  border: 1px solid #d1d5db;
  background-color: #fff;
  padding: 0.5rem 0.75rem;
  font-size: 0.875rem;
  line-height: 1.25rem;
  color: #111827;
  box-shadow: 0 1px 0 rgba(15, 23, 42, 0.03);
}

.ulc-input:focus {
  outline: none;
  border-color: #3b5998;
  box-shadow: 0 0 0 1px #3b5998;
}

.ulc-status-select {
  background-color: #fff7ed;
  border-color: #fed7aa;
}

.ulc-footer-summary {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.5rem 0.75rem;
  margin-bottom: 1rem;
  font-size: 0.875rem;
  line-height: 1.35;
}

.ulc-footer-clear-badge {
  display: inline-flex;
  height: 1.25rem;
  min-width: 1.25rem;
  align-items: center;
  justify-content: center;
  border-radius: 0.25rem;
  background: #3b5998;
  font-size: 0.875rem;
  font-weight: 700;
  line-height: 1;
  color: #fff;
}

.ulc-footer-summary-strong {
  font-weight: 600;
  color: #1f2937;
}

.ulc-footer-info-icon {
  width: 1rem;
  height: 1rem;
  flex-shrink: 0;
  color: #9ca3af;
}

.ulc-footer-summary-muted {
  color: #6b7280;
  font-size: 0.875rem;
}

.ulc-footer-actions {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
}

.ulc-footer-actions-left {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 1rem;
  min-width: 0;
}

.ulc-footer-count-muted {
  margin: 0;
  font-size: 0.875rem;
  font-weight: 500;
  color: #6b7280;
}

.ulc-footer-status-field {
  display: inline-flex;
  max-width: 100%;
  align-items: stretch;
  overflow: hidden;
  border-radius: 0.5rem;
  border: 1px solid #e5e7eb;
  background: #fff;
}

.ulc-footer-status-label {
  display: inline-flex;
  flex-shrink: 0;
  align-items: center;
  padding: 0.5rem 0.75rem;
  border-right: 1px solid #e5e7eb;
  background: #f3f4f6;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  white-space: nowrap;
}

.ulc-footer-status-field .ulc-footer-status-select {
  min-width: min(13rem, 100%);
  max-width: 100%;
  flex: 1 1 auto;
  border: none;
  border-radius: 0;
  background-color: #fff7ed;
  box-shadow: none;
  font-size: 0.875rem;
  font-weight: 500;
  line-height: 1.25rem;
}

.ulc-footer-status-field .ulc-footer-status-select:focus {
  outline: none;
  box-shadow: inset 0 0 0 2px #3b5998;
}

.ulc-footer-actions-right {
  display: flex;
  flex-shrink: 0;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
}

.ulc-btn-primary {
  border-radius: 0.375rem;
  border: none;
  background: linear-gradient(180deg, #4a6fb5 0%, #3b5998 100%);
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  box-shadow: 0 1px 2px rgba(59, 89, 152, 0.35);
}

.ulc-btn-primary:hover:not(:disabled) {
  background: linear-gradient(180deg, #3b5998 0%, #334b82 100%);
}

.ulc-btn-primary:disabled {
  opacity: 0.55;
}

.ulc-filter-btn-primary {
  box-shadow: 0 2px 6px rgba(59, 89, 152, 0.25);
}

.ulc-btn-apply-solid {
  border-radius: 0.375rem;
  border: 1px solid #2563eb;
  background: #2563eb;
  font-size: 0.875rem;
  font-weight: 600;
  color: #fff;
  box-shadow: 0 1px 2px rgba(37, 99, 235, 0.35);
}

.ulc-btn-apply-solid:hover:not(:disabled) {
  background: #1d4ed8;
  border-color: #1d4ed8;
}

.ulc-btn-apply-solid:disabled {
  opacity: 0.55;
}

.ulc-btn-secondary {
  border-radius: 0.375rem;
  border: 1px solid #d1d5db;
  background-color: #f9fafb;
  font-size: 0.875rem;
  font-weight: 600;
  color: #374151;
  box-shadow: 0 1px 2px rgba(15, 23, 42, 0.05);
}

.ulc-btn-secondary:hover {
  background-color: #f3f4f6;
}

.ulc-filter-btn-secondary {
  background-color: #ffffff;
}

.ulc-filter-btn-secondary:hover {
  background-color: #f8fafc;
}

.ulc-stepper-wrap {
  position: relative;
  z-index: 0;
  overflow: visible;
  background: #fff;
}

.ulc-stepper {
  display: flex;
  min-height: 3rem;
  width: 100%;
}

.ulc-stepper__step {
  --ulc-stepper-arrow: 1.125rem;
  --ulc-stepper-bg: #e5e7eb;
  position: relative;
  display: flex;
  flex: 1 1 0;
  align-items: center;
  justify-content: center;
  min-height: 3rem;
  padding: 0.625rem 1rem;
  background: var(--ulc-stepper-bg);
  color: #6b7280;
  text-align: center;
  border-radius: 0;
}

.ulc-stepper__step.is-active {
  --ulc-stepper-bg: #3b52d4;
  z-index: 2;
  color: #fff;
}

.ulc-stepper__step.is-active:not(.ulc-stepper__step--second)::after {
  content: '';
  position: absolute;
  top: 0;
  right: calc(-1 * var(--ulc-stepper-arrow));
  width: 0;
  height: 0;
  border-top: 1.5rem solid transparent;
  border-bottom: 1.5rem solid transparent;
  border-left: var(--ulc-stepper-arrow) solid var(--ulc-stepper-bg);
}

.ulc-stepper__step.is-inactive:not(.ulc-stepper__step--first)::before {
  content: '';
  position: absolute;
  left: 0;
  top: 0;
  width: 0;
  height: 0;
  border-top: 1.5rem solid transparent;
  border-bottom: 1.5rem solid transparent;
  border-left: var(--ulc-stepper-arrow) solid #f4f7f9;
  transform: translateX(-1px);
  z-index: 3;
}

.ulc-stepper__step--second {
  padding-left: 1.5rem;
}

.ulc-stepper__step--first.is-active {
  padding-right: 1.5rem;
}

.ulc-stepper__step--second.is-inactive {
  z-index: 1;
}

.ulc-stepper__step--first.is-inactive {
  z-index: 0;
}

.ulc-stepper__step--first.is-active::after {
  z-index: 2;
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

  .ulc-stepper__step {
    --ulc-stepper-arrow: 0.875rem;
    padding-inline: 0.75rem;
  }

  .ulc-stepper__step.is-active:not(.ulc-stepper__step--second)::after,
  .ulc-stepper__step.is-inactive:not(.ulc-stepper__step--first)::before {
    border-top-width: 1.5rem;
    border-bottom-width: 1.5rem;
    border-left-width: var(--ulc-stepper-arrow);
  }

  .ulc-stepper__step--second {
    padding-left: 1.125rem;
  }

  .ulc-stepper__step--first.is-active {
    padding-right: 1.125rem;
  }
}

.ulc-table-row--selected {
  background-color: rgba(235, 242, 252, 0.85) !important;
  box-shadow: inset 3px 0 0 #3b5998;
}

.ulc-checkbox {
  accent-color: #3b5998;
}

.ulc-checkbox:focus {
  outline: none;
  box-shadow: 0 0 0 2px rgba(59, 89, 152, 0.25);
}

.ulc-icon-btn {
  display: inline-flex;
  height: 1.75rem;
  min-width: 1.75rem;
  align-items: center;
  justify-content: center;
  border-radius: 0.25rem;
  font-size: 0.875rem;
  line-height: 1;
  opacity: 0.85;
}

.ulc-footer-inner {
  position: sticky;
  bottom: 0;
  z-index: 20;
  flex-shrink: 0;
  font-size: 0.875rem;
  box-shadow: 0 -4px 12px rgba(15, 23, 42, 0.06);
}
</style>
