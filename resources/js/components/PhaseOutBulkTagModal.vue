<template>
  <Teleport to="body">
    <div
      v-if="model"
      class="phase-out-popup-overlay fixed inset-0 z-[2147483646] flex min-h-0 w-full items-center justify-center overflow-y-auto overscroll-contain px-3 py-6 sm:px-4 sm:py-8"
      role="presentation"
      @click.self="closeModal"
    >
      <div
        ref="panelRef"
        class="phase-out-popup-panel flex min-h-0 w-full max-w-2xl flex-col overflow-hidden rounded-[12px] bg-white shadow-xl ring-1 ring-slate-900/10"
        role="dialog"
        aria-modal="true"
        aria-labelledby="phase-out-modal-title"
        aria-describedby="phase-out-modal-desc"
        tabindex="-1"
        @click.stop
      >
        <span id="phase-out-modal-desc" class="sr-only">
          Select inventory rows, optionally adjust suggested discounts, then tag selected items as For Phase Out.
        </span>

        <BulkTagModalHeader @close="closeModal" />

        <div class="phase-out-modal-body flex min-h-0 flex-1 flex-col overflow-hidden bg-slate-50/50">
          <div class="phase-out-modal-scroll min-h-0 flex-1 overflow-y-auto overscroll-contain">
            <div class="space-y-4 px-6 py-5">
              <BulkTagFilterSection
                :filters="filters"
                :candidates-meta="candidatesMeta"
                :candidates-loading="candidatesLoading"
                :candidates-load-error="candidatesLoadError"
                @update:filters="Object.assign(filters, $event)"
                @apply-filters="loadCandidates(1)"
              />

              <BulkTagDiscountToggleRow v-model="adjustDiscountsEnabled" />

              <BulkTagTableSection
                :rows="candidateRows"
                :selected="selected"
                :loading="candidatesLoading"
                :meta="candidatesMeta"
                :adjust-discounts-enabled="adjustDiscountsEnabled"
                :discount-row-on="discountRowOn"
                @toggle-all="toggleAll"
                @toggle-row="onToggleRow"
                @toggle-discount-row="toggleDiscountRow"
                @page="loadCandidates"
              />

              <BulkTagSelectionSummaryBar v-model="adjustDiscountsEnabled" :selected-count="selectedCount" />

              <BulkTagModalLegend />
            </div>
          </div>
        </div>

        <BulkTagModalFooter
          :selected-count="selectedCount"
          :disabled="selectedCount === 0"
          :tagging="tagging"
          :aria-label="applyButtonAriaLabel"
          @cancel="closeModal"
          @submit="submitTag"
        />
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { ref, reactive, computed, watch, onUnmounted, nextTick } from 'vue';
import axios, { isAxiosError } from 'axios';
import { useModalFocusTrap } from '@/composables/useModalFocusTrap';
import BulkTagModalHeader from '@/components/phase-out/BulkTagModalHeader.vue';
import BulkTagFilterSection from '@/components/phase-out/BulkTagFilterSection.vue';
import BulkTagDiscountToggleRow from '@/components/phase-out/BulkTagDiscountToggleRow.vue';
import BulkTagTableSection from '@/components/phase-out/BulkTagTableSection.vue';
import BulkTagSelectionSummaryBar from '@/components/phase-out/BulkTagSelectionSummaryBar.vue';
import BulkTagModalLegend from '@/components/phase-out/BulkTagModalLegend.vue';
import BulkTagModalFooter from '@/components/phase-out/BulkTagModalFooter.vue';

const model = defineModel({ type: Boolean, default: false });

const emit = defineEmits(['success']);

const panelRef = ref(null);
const candidatesLoading = ref(false);
const candidatesLoadError = ref(null);
const candidateRows = ref([]);
const candidatesMeta = ref(null);
const selected = reactive({});
const tagging = ref(false);
const adjustDiscountsEnabled = ref(true);
const discountRowOn = reactive({});

const filters = reactive({
  brand: '',
  created_before: '',
  no_movement_days: null,
  months: 12,
  excess_stock_only: false,
});

const selectedCount = computed(() => Object.keys(selected).filter((k) => selected[k]).length);

const applyButtonAriaLabel = computed(() => {
  const n = selectedCount.value;
  if (n === 0) {
    return 'Select at least one item to tag';
  }
  return `Tag ${n} items as For Phase Out`;
});

function toggleDiscountRow(name) {
  discountRowOn[name] = !(discountRowOn[name] !== false);
}

function clearSelection() {
  Object.keys(selected).forEach((k) => {
    delete selected[k];
  });
}

function toggleAll(checked) {
  candidateRows.value.forEach((r) => {
    selected[r.name] = checked;
  });
}

function onToggleRow(name, checked) {
  selected[name] = checked;
}

function suppressPageLoaderForModal() {
  const el = document.getElementById('loader-wrapper');
  if (!el) {
    return;
  }
  el.setAttribute('hidden', '');
  el.style.pointerEvents = 'none';
  el.style.opacity = '0';
  el.style.visibility = 'hidden';
}

const REPORT_REQUEST_TIMEOUT_MS = 60000;

function reportLoadErrorMessage(err) {
  if (isAxiosError(err)) {
    if (err.code === 'ECONNABORTED' || err.message?.includes('timeout')) {
      return 'Loading candidates timed out. Try narrowing filters or refresh in a moment.';
    }
    const status = err.response?.status;
    if (status === 503 || status === 502) {
      return 'The server is busy. Please try again in a moment.';
    }
    if (status >= 500) {
      return 'Could not load candidates (server error). Please try again later.';
    }
    if (status === 404 || status === 403) {
      return 'Could not load candidates. Check that you are signed in and try again.';
    }
  }
  return 'Could not load candidates. Check your connection and try Refresh.';
}

async function loadCandidates(page) {
  candidatesLoading.value = true;
  candidatesLoadError.value = null;
  try {
    const params = {
      tagged_per_page: 1,
      tagged_page: 1,
      candidates_per_page: 10,
      candidates_page: page,
      months: filters.months || 12,
    };
    if (filters.brand) {
      params.brand = filters.brand;
    }
    if (filters.created_before) {
      params.created_before = filters.created_before;
    }
    if (filters.no_movement_days) {
      params.no_movement_days = filters.no_movement_days;
    }
    if (filters.excess_stock_only) {
      params.excess_stock_only = 1;
    }

    const { data } = await axios.get('/phase-out/report', {
      params,
      timeout: REPORT_REQUEST_TIMEOUT_MS,
    });
    candidateRows.value = data.candidates?.data || [];
    candidatesMeta.value = data.candidates
      ? {
          current_page: data.candidates.current_page,
          last_page: data.candidates.last_page,
          total: data.candidates.total,
        }
      : null;
    clearSelection();
  } catch (err) {
    candidateRows.value = [];
    candidatesMeta.value = null;
    candidatesLoadError.value = reportLoadErrorMessage(err);
  } finally {
    candidatesLoading.value = false;
  }
}

async function submitTag() {
  const itemIds = Object.keys(selected).filter((k) => selected[k]);
  if (itemIds.length === 0) {
    return;
  }
  tagging.value = true;
  try {
    await axios.post('/items/bulk-tag', {
      itemIds,
      tag: 'For Phase Out',
    });
    closeModal();
    emit('success');
  } catch {
    window.alert('Tagging failed. Please try again.');
  } finally {
    tagging.value = false;
  }
}

function closeModal() {
  model.value = false;
  candidatesLoadError.value = null;
}

function onEscapeKey(e) {
  if (e.key === 'Escape' && model.value) {
    closeModal();
  }
}

const isModalOpen = computed(() => model.value);
useModalFocusTrap(panelRef, isModalOpen);

watch(model, (open) => {
  if (open) {
    suppressPageLoaderForModal();
    clearSelection();
    candidatesLoadError.value = null;
    nextTick(() => {
      loadCandidates(1);
    });
    document.addEventListener('keydown', onEscapeKey);
    document.body.style.overflow = 'hidden';
  } else {
    document.removeEventListener('keydown', onEscapeKey);
    document.body.style.overflow = '';
  }
});

onUnmounted(() => {
  document.removeEventListener('keydown', onEscapeKey);
  document.body.style.overflow = '';
});
</script>

<style scoped>
.phase-out-popup-overlay {
  position: fixed;
  inset: 0;
  width: 100vw;
  max-width: 100vw;
  height: 100vh;
  max-height: 100vh;
  min-height: 0;
  box-sizing: border-box;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  z-index: 2147483646;
  background: rgba(15, 23, 42, 0.48);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.phase-out-popup-panel {
  height: min(90vh, 720px);
  max-height: min(90vh, 720px);
  min-height: 0;
  width: min(42rem, calc(100vw - 2rem)) !important;
  max-width: min(42rem, calc(100vw - 2rem)) !important;
  margin-left: auto !important;
  margin-right: auto !important;
  justify-self: center !important;
  align-self: center !important;
  box-sizing: border-box;
  animation: phaseOutPanelPop 0.28s cubic-bezier(0.16, 1, 0.3, 1) both;
}

.phase-out-modal-body {
  -webkit-overflow-scrolling: touch;
  min-height: 0;
}

.phase-out-modal-scroll {
  scrollbar-gutter: stable;
  -webkit-overflow-scrolling: touch;
}

.phase-out-popup-panel svg.phase-out-ico-md {
  width: 1.25rem !important;
  height: 1.25rem !important;
  max-width: 1.25rem !important;
  max-height: 1.25rem !important;
  flex-shrink: 0;
}

@keyframes phaseOutPanelPop {
  from {
    opacity: 0;
    transform: scale(0.94) translateY(18px);
  }
  to {
    opacity: 1;
    transform: scale(1) translateY(0);
  }
}
</style>
