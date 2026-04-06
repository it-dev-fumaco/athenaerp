<template>
  <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div v-if="loading" class="px-5 py-6">
      <div class="overflow-hidden rounded-lg border border-slate-200 bg-slate-50/50">
        <div class="divide-y divide-slate-100 px-4 py-4">
          <div v-for="n in 5" :key="n" class="flex animate-pulse gap-3 py-3">
            <div class="h-11 w-11 shrink-0 rounded-lg bg-slate-200" />
            <div class="min-w-0 flex-1 space-y-2">
              <div class="h-4 w-3/4 max-w-xs rounded bg-slate-200" />
              <div class="h-3 w-1/2 max-w-[12rem] rounded bg-slate-100" />
            </div>
          </div>
        </div>
        <p class="border-t border-slate-200 px-4 py-3 text-center text-sm text-slate-600">Loading candidates…</p>
      </div>
    </div>

    <div v-else class="flex min-h-0 flex-col">
      <div class="overflow-x-auto overscroll-contain">
        <table class="min-w-full border-collapse text-left text-sm">
          <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-700">
            <tr>
              <th scope="col" class="w-14 whitespace-nowrap px-3 py-3 text-center">
                <label class="inline-flex min-h-[44px] min-w-[44px] cursor-pointer items-center justify-center rounded-md p-2 -m-1 hover:bg-slate-200/80 focus-within:ring-2 focus-within:ring-[#1976D2]">
                  <span class="sr-only">Select all on this page</span>
                  <input
                    ref="selectAllCheckboxRef"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-[#1976D2] focus:ring-[#1976D2]"
                    :checked="allSelected"
                    @change="$emit('toggle-all', $event.target.checked)"
                  >
                </label>
              </th>
              <th scope="col" class="whitespace-nowrap px-3 py-3">Item Code</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Entry Date</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-right tabular-nums">Current Stock</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3 text-right">Last Movement</th>
              <th scope="col" class="whitespace-nowrap px-3 py-3">Suggested Discount</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100 bg-white">
            <tr
              v-for="(item, rowIdx) in rows"
              :key="item.name"
              class="transition-colors hover:bg-slate-100/90"
              :class="[
                rowIdx % 2 === 1 ? 'bg-slate-50/80' : '',
                selected[item.name] ? 'bg-sky-50/90 ring-1 ring-inset ring-sky-200/90' : '',
              ]"
              :aria-selected="selected[item.name] ? 'true' : 'false'"
            >
              <td class="whitespace-nowrap px-3 py-2 align-middle">
                <label
                  class="flex min-h-[44px] min-w-[44px] cursor-pointer items-center justify-center rounded-md p-2 -m-1 hover:bg-slate-100/80 focus-within:ring-2 focus-within:ring-[#1976D2]"
                  :for="checkboxId(item.name)"
                >
                  <span class="sr-only">Select {{ item.name }}</span>
                  <input
                    :id="checkboxId(item.name)"
                    type="checkbox"
                    class="h-4 w-4 rounded border-slate-300 text-[#1976D2] focus:ring-[#1976D2]"
                    :checked="!!selected[item.name]"
                    @change="(e) => $emit('toggle-row', item.name, e.target.checked)"
                  >
                </label>
              </td>
              <td class="max-w-[14rem] px-3 py-2.5 align-top sm:max-w-[16rem]">
                <div class="font-mono text-sm font-semibold leading-snug text-slate-900">{{ item.name }}</div>
                <div class="mt-0.5 line-clamp-3 text-xs leading-snug text-slate-600" :title="item.item_name || ''">
                  {{ item.item_name }}
                </div>
              </td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right align-middle tabular-nums text-slate-800">
                {{ formatDate(item.creation) }}
              </td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right align-middle tabular-nums text-slate-900">
                <span class="inline-flex items-center justify-end gap-1.5">
                  {{ formatQty(item.total_actual_qty) }}
                  <svg class="h-4 w-4 shrink-0 text-emerald-500" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                  </svg>
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-2.5 text-right align-middle tabular-nums text-slate-800">
                <span class="inline-flex items-center justify-end gap-1.5">
                  {{ formatDate(item.last_stock_ledger_posting) }}
                  <svg class="h-4 w-4 shrink-0 text-amber-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                  </svg>
                </span>
              </td>
              <td class="whitespace-nowrap px-3 py-2.5 align-middle">
                <div v-if="adjustDiscountsEnabled" class="flex flex-wrap items-center gap-2">
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="discountRowOn[item.name] !== false"
                    :aria-label="'Toggle suggested discount for ' + item.name"
                    class="relative inline-flex h-6 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1976D2]"
                    :class="discountRowOn[item.name] !== false ? 'bg-[#1976D2]' : 'bg-slate-300'"
                    @click="$emit('toggle-discount-row', item.name)"
                  >
                    <span
                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                      :class="discountRowOn[item.name] !== false ? 'translate-x-4' : 'translate-x-0.5'"
                    />
                  </button>
                  <span
                    class="inline-flex items-center gap-1.5 rounded-md px-2 py-0.5 text-sm font-semibold tabular-nums"
                    :class="tierBadgeClass(item)"
                  >
                    {{ suggestedDiscountPct(item) }}%
                    <span class="font-normal opacity-90">{{ discountTier(item).label }}</span>
                  </span>
                </div>
                <span v-else class="text-slate-400">—</span>
              </td>
            </tr>
            <tr v-if="!loading && rows.length === 0">
              <td colspan="6" class="px-3 py-10 text-center text-slate-500">No candidates for current filters.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="meta && meta.last_page > 1"
        class="flex items-center justify-between border-t border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600"
      >
        <span>Page {{ meta.current_page }} / {{ meta.last_page }}</span>
        <div class="flex gap-2">
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-slate-50 disabled:opacity-40 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1976D2]"
            :disabled="meta.current_page <= 1"
            @click="$emit('page', meta.current_page - 1)"
          >
            Prev
          </button>
          <button
            type="button"
            class="rounded border border-slate-300 bg-white px-3 py-1.5 text-sm font-medium hover:bg-slate-50 disabled:opacity-40 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1976D2]"
            :disabled="meta.current_page >= meta.last_page"
            @click="$emit('page', meta.current_page + 1)"
          >
            Next
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, nextTick } from 'vue';

const props = defineProps({
  rows: { type: Array, default: () => [] },
  selected: { type: Object, required: true },
  loading: { type: Boolean, default: false },
  meta: { type: Object, default: null },
  adjustDiscountsEnabled: { type: Boolean, default: true },
  discountRowOn: { type: Object, required: true },
});

defineEmits(['toggle-all', 'toggle-row', 'toggle-discount-row', 'page']);

const selectAllCheckboxRef = ref(null);

const allSelected = computed(() => {
  if (props.rows.length === 0) {
    return false;
  }
  return props.rows.every((r) => props.selected[r.name]);
});

const someSelected = computed(() => props.rows.some((r) => props.selected[r.name]));

watch([allSelected, someSelected, () => props.rows], () => {
  nextTick(() => {
    const el = selectAllCheckboxRef.value;
    if (el) {
      el.indeterminate = someSelected.value && !allSelected.value;
    }
  });
});

function checkboxId(name) {
  return `phase-out-cb-${String(name).replace(/[^a-zA-Z0-9_-]/g, '_')}`;
}

function formatQty(n) {
  if (n === null || n === undefined) return '—';
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

function formatDate(val) {
  if (val === null || val === undefined || val === '') {
    return '—';
  }
  const d = new Date(val);
  if (Number.isNaN(d.getTime())) {
    return String(val).slice(0, 10);
  }
  return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
}

function daysSinceLastMovement(iso) {
  if (!iso) {
    return 0;
  }
  const d = new Date(iso);
  if (Number.isNaN(d.getTime())) {
    return 0;
  }
  return Math.floor((Date.now() - d.getTime()) / 86400000);
}

function discountTier(item) {
  const days = daysSinceLastMovement(item.last_stock_ledger_posting);
  if (days > 365) {
    return { label: 'High', key: 'high' };
  }
  if (days > 180) {
    return { label: 'Medium', key: 'medium' };
  }
  return { label: 'Low', key: 'low' };
}

function suggestedDiscountPct(item) {
  const days = daysSinceLastMovement(item.last_stock_ledger_posting);
  if (days > 365) {
    return 28;
  }
  if (days > 180) {
    return 18;
  }
  return 9;
}

function tierBadgeClass(item) {
  const k = discountTier(item).key;
  if (k === 'high') {
    return 'border border-rose-200 bg-rose-100 text-rose-900';
  }
  if (k === 'medium') {
    return 'border border-amber-200 bg-amber-100 text-amber-900';
  }
  return 'border border-emerald-200 bg-emerald-100 text-emerald-900';
}
</script>
