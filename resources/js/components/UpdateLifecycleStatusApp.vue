<template>
  <div class="update-lifecycle-status ulc-root font-sans flex min-h-0 min-w-0 flex-1 flex-col bg-[#f4f7f9]">
    <div class="mx-auto w-full max-w-[1600px] p-4 sm:p-6">
      <div class="ulc-shell flex flex-col rounded-lg bg-white shadow-[0_2px_12px_rgba(15,23,42,0.08)]">
        <!-- Header -->
        <header class="border-b border-[#e5e7eb] px-5 pb-4 pt-5 sm:px-6 sm:pb-5 sm:pt-6">
          <div class="flex flex-wrap items-start gap-3">
            <div class="min-w-0 flex-1">
              <h1 class="text-2xl font-bold leading-snug text-[#1f2937]">
                Mass Update Lifecycle Status
              </h1>
              <p class="mt-2 text-sm leading-snug text-[#6b7280]">
                Select items to update their lifecycle status to &quot;For Phase Out&quot;.
              </p>
            </div>
          </div>
        </header>

        <!-- Stepper -->
        <div class="ulc-stepper-wrap border-b border-[#e5e7eb]" aria-live="polite">
          <div class="ulc-stepper">
            <div
              :class="[
                'ulc-stepper__ribbon',
                workflowStep === 1 ? 'ulc-stepper__ribbon--active' : 'ulc-stepper__ribbon--done',
              ]"
              :aria-current="workflowStep === 1 ? 'step' : undefined"
            >
              <span class="ulc-stepper__label">1 Select Items</span>
            </div>
            <div
              :class="[
                'ulc-stepper__ribbon',
                workflowStep === 2 ? 'ulc-stepper__ribbon--active-follow' : 'ulc-stepper__ribbon--next',
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
            <aside class="ulc-panel ulc-panel--left rounded-lg border border-[#d1d5db] bg-white p-5 shadow-[0_1px_2px_rgba(15,23,42,0.04)]">
              <h2 class="ulc-section-title text-[#1f2937]">
                Search Filters
              </h2>
              <div class="mt-4 space-y-4">
                <div class="min-w-0">
                  <label class="ulc-field-label" for="ulc-classification">Item Classification</label>
                  <select
                    id="ulc-classification"
                    v-model="filters.item_classification"
                    class="ulc-input mt-2 w-full max-w-full min-w-0"
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
                    class="ulc-input mt-2 w-full max-w-full min-w-0"
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
                  class="ulc-input mt-2 w-full max-w-full min-w-0"
                >
                  <option v-for="o in lastMovementOptions" :key="o.value" :value="o.value">
                    {{ o.label }}
                  </option>
                </select>
              </div>
              </div>
              <div class="mt-6 flex gap-3 border-t border-[#f3f4f6] pt-5">
                <button
                  type="button"
                  class="ulc-btn-primary ulc-filter-btn-primary inline-flex min-h-10 min-w-0 flex-1 items-center justify-center px-5"
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
              <div class="shrink-0 border-b border-[#e5e7eb] bg-white px-5 py-4">
                <p class="ulc-section-title font-bold leading-snug text-[#1f2937]">
                  <span class="tabular-nums">{{ meta.total }}</span> Items found
                </p>
              </div>
              <div
                v-if="rows.length"
                class="ulc-select-all-row shrink-0 border-b border-[#e5e7eb] bg-white px-5 py-3"
              >
                <label for="ulc-select-all" class="ulc-select-all-control inline-flex cursor-pointer items-center">
                  <input
                    id="ulc-select-all"
                    type="checkbox"
                    class="ulc-checkbox ulc-select-all-checkbox h-4 w-4 shrink-0 rounded border-[#cbd5e1]"
                    :checked="allPageSelected"
                    @change="toggleAll($event.target.checked)"
                  >
                  <span class="ulc-select-all-label whitespace-nowrap text-sm font-bold text-[#1f2937]">Select All</span>
                </label>
                <span class="ulc-select-all-count text-sm text-[#6b7280]">
                  {{ selectedCount }} of {{ meta.total }} items selected
                </span>
              </div>

              <div
                v-if="rows.length"
                class="ulc-step1-status-block shrink-0 border-b border-[#e5e7eb] bg-white px-5 py-3"
              >
                <label class="ulc-field-label mb-2 block" for="ulc-step1-new-status">Set New Status</label>
                <select
                  id="ulc-step1-new-status"
                  v-model="newStatus"
                  class="ulc-input ulc-status-select max-w-md w-full"
                >
                  <option v-for="s in lifecycleStatuses" :key="s" :value="s">{{ s }}</option>
                </select>
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
              <div v-else class="ulc-table-viewport min-h-0">
                <table class="ulc-data-table">
                  <thead class="ulc-table-head-text text-[#374151]">
                    <tr>
                      <th class="ulc-table-th" scope="col" />
                      <th class="ulc-table-th" scope="col">Item Code</th>
                      <th class="ulc-table-th" scope="col">Name</th>
                      <th class="ulc-table-th max-w-[8rem] whitespace-nowrap" scope="col">Item Classification</th>
                      <th class="ulc-table-th ulc-table-th--num" scope="col">Global Stock</th>
                      <th class="ulc-table-th ulc-table-th--num" scope="col">Last Movement</th>
                      <th class="ulc-table-th" scope="col">Last Purchase</th>
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
                      <td class="whitespace-nowrap text-[#6b7280]">
                        {{ row.last_purchase != null ? formatDate(row.last_purchase) : '—' }}
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <div
                v-if="rows.length && meta.last_page > 1"
                class="flex shrink-0 flex-wrap items-center justify-between gap-3 border-t border-[#e5e7eb] bg-[#f9fafb] px-4 py-3"
              >
                <p class="text-xs font-medium text-[#6b7280]">
                  Page {{ meta.current_page }} of {{ meta.last_page }}
                </p>
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="rounded-md border border-[#d1d5db] bg-white px-3 py-1.5 text-sm font-medium text-[#1f2937] shadow-sm transition hover:bg-[#f9fafb] disabled:opacity-50"
                    :disabled="meta.current_page <= 1 || listLoading"
                    @click="loadItems(meta.current_page - 1)"
                  >
                    Previous
                  </button>
                  <button
                    type="button"
                    class="rounded-md border border-[#d1d5db] bg-white px-3 py-1.5 text-sm font-medium text-[#1f2937] shadow-sm transition hover:bg-[#f9fafb] disabled:opacity-50"
                    :disabled="meta.current_page >= meta.last_page || listLoading"
                    @click="loadItems(meta.current_page + 1)"
                  >
                    Next
                  </button>
                </div>
              </div>

              <div
                v-if="rows.length"
                class="shrink-0 border-t border-[#e5e7eb] bg-white px-5 py-4"
              >
                <button
                  type="button"
                  class="ulc-btn-primary ulc-filter-btn-primary inline-flex min-h-10 items-center justify-center px-6 disabled:cursor-not-allowed disabled:opacity-50"
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
            <button
              type="button"
              class="ulc-btn-secondary mb-4 inline-flex min-h-9 items-center px-4 text-sm"
              @click="backToSelection"
            >
              ← Back to selection
            </button>
            <h2 class="ulc-section-title text-[#1f2937]">
              <span class="tabular-nums">{{ selectedCount }}</span>
              {{ selectedCount === 1 ? ' item' : ' items' }} selected
            </h2>
            <p class="mt-2 text-sm text-[#6b7280]">
              <span class="font-semibold text-[#374151]">New status:</span>
              {{ newStatus }}
            </p>

            <div class="ulc-review-table-wrap mt-5 max-h-[min(400px,50vh)] overflow-auto rounded border border-[#e5e7eb]">
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

            <div class="ulc-review-warning mt-5 flex gap-3 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-950">
              <span class="shrink-0 text-lg leading-none" aria-hidden="true">⚠️</span>
              <p class="m-0 leading-snug">
                This action will update the lifecycle status of all selected items.
                This may affect sales and availability.
              </p>
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
import { ref, reactive, computed, onMounted, watch } from 'vue';
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
  rows.value = [];
  meta.value = { current_page: 1, last_page: 1, total: 0, per_page: 15 };
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
      per_page: meta.value.per_page || 15,
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

onMounted(() => {
  loadFilterOptions();
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
  max-height: min(400px, 55vh);
  min-height: 0;
  overflow: auto;
  -webkit-overflow-scrolling: touch;
}

/* Select All row */
.ulc-select-all-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 10px;
}

.ulc-step1-status-block {
  margin-bottom: 16px;
}

.ulc-select-all-count {
  font-weight: 500;
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
  overflow: hidden;
  background: #fff;
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
  background: linear-gradient(180deg, #4a6fb5 0%, #3b5998 55%, #334b82 100%);
  color: #fff;
  padding-right: 1.75rem;
  clip-path: polygon(0 0, calc(100% - 18px) 0, 100% 50%, calc(100% - 18px) 100%, 0 100%);
  box-shadow: 2px 0 6px rgba(59, 89, 152, 0.28);
}

.ulc-stepper__ribbon--done {
  z-index: 1;
  margin-right: -1.125rem;
  padding-right: 1.75rem;
  background: linear-gradient(180deg, #e5e7eb 0%, #d1d5db 100%);
  color: #4b5563;
  clip-path: polygon(0 0, calc(100% - 18px) 0, 100% 50%, calc(100% - 18px) 100%, 0 100%);
}

.ulc-stepper__ribbon--active-follow {
  z-index: 2;
  margin-left: 0;
  padding-left: 2rem;
  background: linear-gradient(180deg, #4a6fb5 0%, #3b5998 55%, #334b82 100%);
  color: #fff;
  clip-path: polygon(18px 0, 100% 0, 100% 100%, 18px 100%, 0 50%);
  box-shadow: 2px 0 6px rgba(59, 89, 152, 0.28);
}

.ulc-stepper__ribbon--next {
  z-index: 1;
  margin-left: 0;
  padding-left: 2rem;
  background: linear-gradient(180deg, #f3f4f6 0%, #e5e7eb 100%);
  color: #6b7280;
  clip-path: polygon(18px 0, 100% 0, 100% 100%, 18px 100%, 0 50%);
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

  .ulc-stepper__ribbon--active,
  .ulc-stepper__ribbon--done {
    padding-right: 1.25rem;
    clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
  }

  .ulc-stepper__ribbon--active-follow,
  .ulc-stepper__ribbon--next {
    padding-left: 1.5rem;
    clip-path: polygon(14px 0, 100% 0, 100% 100%, 14px 100%, 0 50%);
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
