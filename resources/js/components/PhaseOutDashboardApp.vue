<template>
  <div class="phase-out-dashboard space-y-6 p-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
      <div>
        <h1 class="text-xl font-semibold text-slate-800">Inventory Phase-Out Management</h1>
        <p class="mt-1 text-sm text-slate-600">
          Track items tagged For Phase Out and identify candidates for tagging.
        </p>
      </div>
      <button
        type="button"
        class="phase-out-bulk-tag-btn inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-semibold text-white shadow-lg transition-all duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-400 focus-visible:ring-offset-2"
        @click="openModal"
      >
        Bulk tag as For Phase Out
      </button>
    </div>

    <div v-if="summaryLoading" class="flex justify-center py-6">
      <div class="h-8 w-8 animate-spin rounded-full border-2 border-slate-300 border-t-slate-700" />
    </div>
    <template v-else>
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">Phase-Out items</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ summary.tagged_count }}</p>
          <p class="mt-1 text-sm text-slate-600">Total stock value: {{ formatMoney(summary.total_stock_value) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <p class="text-sm font-medium text-slate-500">Total stock (units)</p>
          <p class="mt-2 text-3xl font-semibold tabular-nums text-slate-900">{{ formatQty(summary.total_units) }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 lg:col-span-1">
          <p class="text-sm font-medium text-slate-500">By brand (value)</p>
          <p class="mt-2 text-sm text-slate-600">See charts below for distribution.</p>
        </div>
      </div>

      <div class="grid gap-6 lg:grid-cols-2">
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-sm font-semibold text-slate-800">Items by brand</h2>
          <div class="mt-4 space-y-3">
            <div v-for="row in brandBars" :key="row.brand" class="flex items-center gap-3 text-sm">
              <span class="w-28 shrink-0 truncate text-slate-600" :title="row.brand">{{ row.brand }}</span>
              <div class="h-6 min-w-0 flex-1 overflow-hidden rounded bg-slate-100">
                <div
                  class="h-full rounded bg-sky-600 transition-all"
                  :style="{ width: row.pct + '%' }"
                />
              </div>
              <span class="w-10 shrink-0 text-right tabular-nums text-slate-700">{{ row.item_count }}</span>
            </div>
            <p v-if="brandBars.length === 0" class="text-sm text-slate-500">No data.</p>
          </div>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
          <h2 class="text-sm font-semibold text-slate-800">Stock value by brand</h2>
          <div class="mt-4 flex flex-col items-center gap-4 sm:flex-row">
            <div
              class="h-40 w-40 shrink-0 rounded-full border border-slate-200 shadow-inner"
              :style="donutStyle"
              role="img"
              :aria-label="'Stock value distribution'"
            />
            <ul class="min-w-0 flex-1 space-y-2 text-sm">
              <li v-for="(row, idx) in donutLegend" :key="row.brand" class="flex items-center gap-2">
                <span class="h-3 w-3 shrink-0 rounded-sm" :style="{ background: row.color }" />
                <span class="truncate text-slate-700">{{ row.brand }}</span>
                <span class="ml-auto tabular-nums text-slate-600">{{ formatMoney(row.stock_value) }}</span>
              </li>
            </ul>
          </div>
        </div>
      </div>
    </template>

    <section>
      <h2 class="mb-3 text-sm font-semibold text-slate-800">Phase-Out items</h2>
      <PhaseOutTaggedTable ref="taggedTableRef" :per-page="8" />
    </section>

    <Teleport to="body">
      <div
        v-if="modalOpen"
        class="phase-out-popup-overlay fixed inset-0 z-[2147483646] flex min-h-0 w-full items-center justify-center overflow-y-auto overscroll-contain px-3 py-6 sm:px-4 sm:py-8"
        role="dialog"
        aria-modal="true"
        aria-labelledby="phase-out-modal-title"
        @click.self="closeModal"
      >
        <div
          class="phase-out-popup-panel flex max-h-[min(78vh,520px)] w-auto max-w-2xl flex-col overflow-hidden rounded-xl bg-white shadow-xl ring-1 ring-slate-900/10"
          @click.stop
        >
          <!-- Header: icon + title + close -->
          <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
              <span class="phase-out-header-icon-wrap flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-700" aria-hidden="true">
                <svg class="phase-out-ico-md h-5 w-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
              </span>
              <h2 id="phase-out-modal-title" class="text-lg font-semibold leading-snug text-slate-900">
                Bulk Tag Inventory Items as For Phase Out
              </h2>
            </div>
            <button
              type="button"
              class="shrink-0 rounded-lg p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-700"
              aria-label="Close"
              @click="closeModal"
            >
              <svg class="phase-out-ico-md h-5 w-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <div class="phase-out-modal-body min-h-0 flex-1 overflow-y-auto overflow-x-hidden overscroll-contain">
          <!-- Info alert -->
          <div class="mx-4 mt-4 flex gap-3 rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-950">
            <span class="mt-0.5 shrink-0 text-sky-600" aria-hidden="true">
              <svg class="phase-out-ico-md h-5 w-5" width="20" height="20" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
              </svg>
            </span>
            <p>
              <template v-if="candidatesMeta">
                We identified <strong>{{ candidatesMeta.total }}</strong> items that may be suitable for phase-out based on your criteria below.
              </template>
              <template v-else-if="candidatesLoading">Finding candidates…</template>
              <template v-else>Adjust filters and apply to load candidates.</template>
            </p>
          </div>

          <!-- Criteria rows (reference-style) -->
          <div class="space-y-3 px-4 py-4">
            <div class="phase-out-filter-row flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/90 p-3 sm:flex-nowrap">
              <span class="phase-out-filter-check flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-sky-600 bg-sky-600 text-xs font-bold text-white" aria-hidden="true">✓</span>
              <span class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Brand</span>
              <input
                v-model="filters.brand"
                type="text"
                class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                placeholder="Any brand"
              >
            </div>
            <div class="phase-out-filter-row flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/90 p-3 sm:flex-nowrap">
              <span class="phase-out-filter-check flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-sky-600 bg-sky-600 text-xs font-bold text-white" aria-hidden="true">✓</span>
              <span class="w-24 shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Entry date</span>
              <div class="flex min-w-0 flex-1 flex-wrap gap-2 sm:flex-nowrap">
                <input
                  v-model="filters.created_before"
                  type="date"
                  class="min-w-0 flex-1 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                >
                <input
                  v-model.number="filters.no_movement_days"
                  type="number"
                  min="1"
                  class="w-full min-w-[7rem] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm sm:max-w-[8rem] focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                  placeholder="Days idle"
                >
              </div>
            </div>
            <div class="phase-out-filter-row flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/90 p-3 sm:flex-nowrap">
              <span class="phase-out-filter-check flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-sky-600 bg-sky-600 text-xs font-bold text-white" aria-hidden="true">✓</span>
              <span class="min-w-[8rem] shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">No stock movement</span>
              <div class="flex min-w-0 flex-1 items-center gap-2">
                <span class="text-sm text-slate-600">for</span>
                <input
                  v-model.number="filters.months"
                  type="number"
                  min="1"
                  max="120"
                  class="w-24 rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-sky-500 focus:outline-none focus:ring-1 focus:ring-sky-500"
                >
                <span class="text-sm text-slate-600">months (if days empty)</span>
              </div>
            </div>
            <div class="phase-out-filter-row flex flex-wrap items-center gap-3 rounded-xl border border-slate-200 bg-slate-50/90 p-3 sm:flex-nowrap">
              <span class="phase-out-filter-check flex h-6 w-6 shrink-0 items-center justify-center rounded border-2 border-sky-600 bg-sky-600 text-xs font-bold text-white" aria-hidden="true">✓</span>
              <span class="min-w-[8rem] shrink-0 text-xs font-semibold uppercase tracking-wide text-slate-500">Excess stock</span>
              <label class="flex cursor-pointer items-center gap-2 text-sm text-slate-800">
                <input v-model="filters.excess_stock_only" type="checkbox" class="h-4 w-4 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                Stock level &gt; 2× reorder level (any warehouse)
              </label>
            </div>

            <div class="flex flex-wrap gap-2 pt-1">
              <button
                type="button"
                class="rounded-lg border border-sky-600 bg-white px-4 py-2 text-sm font-semibold text-sky-700 shadow-sm hover:bg-sky-50 disabled:opacity-50"
                :disabled="candidatesLoading"
                @click="loadCandidates(1)"
              >
                Apply filters
              </button>
            </div>

            <div
              v-if="candidatesLoadError"
              class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-2 text-sm text-rose-900"
              role="alert"
            >
              {{ candidatesLoadError }}
            </div>
          </div>

          <!-- Table region -->
          <div class="px-4 pb-2">
            <div v-if="candidatesLoading" class="rounded-lg border border-slate-200 py-12 text-center text-sm text-slate-500">
              Loading candidates…
            </div>
            <div v-else class="overflow-hidden rounded-lg border border-slate-200">
              <!-- Toolbar -->
              <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 bg-slate-100 px-3 py-2.5 text-sm">
                <span class="flex items-center gap-2 font-medium text-slate-700">
                  <svg class="phase-out-ico-sm h-4 w-4 text-slate-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                  </svg>
                  Adjust Suggested Discounts
                </span>
                <button
                  type="button"
                  role="switch"
                  :aria-checked="adjustDiscountsEnabled"
                  class="phase-out-toggle relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 focus-visible:ring-offset-2"
                  :class="adjustDiscountsEnabled ? 'bg-sky-600' : 'bg-slate-300'"
                  @click="adjustDiscountsEnabled = !adjustDiscountsEnabled"
                >
                  <span
                    class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition"
                    :class="adjustDiscountsEnabled ? 'translate-x-5' : 'translate-x-0.5'"
                  />
                </button>
              </div>

              <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                  <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wide text-slate-600">
                    <tr>
                      <th class="whitespace-nowrap px-3 py-3">
                        <input
                          type="checkbox"
                          class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                          :checked="allSelected"
                          @change="toggleAll($event.target.checked)"
                        >
                      </th>
                      <th class="whitespace-nowrap px-3 py-3">Item code</th>
                      <th class="whitespace-nowrap px-3 py-3">Entry date</th>
                      <th class="whitespace-nowrap px-3 py-3">Current stock</th>
                      <th class="whitespace-nowrap px-3 py-3">Last movement</th>
                      <th class="whitespace-nowrap px-3 py-3">Suggested discount</th>
                    </tr>
                  </thead>
                  <tbody class="divide-y divide-slate-100 bg-white">
                    <tr v-for="item in candidateRows" :key="item.name" class="hover:bg-slate-50/80">
                      <td class="whitespace-nowrap px-3 py-2.5 align-middle">
                        <input
                          type="checkbox"
                          class="rounded border-slate-300 text-sky-600 focus:ring-sky-500"
                          :checked="!!selected[item.name]"
                          @change="(e) => { selected[item.name] = e.target.checked; }"
                        >
                      </td>
                      <td class="max-w-[10rem] px-3 py-2.5 align-middle">
                        <div class="font-mono text-sm font-medium text-slate-900">{{ item.name }}</div>
                        <div class="truncate text-xs text-slate-500">{{ item.item_name }}</div>
                      </td>
                      <td class="whitespace-nowrap px-3 py-2.5 align-middle text-slate-700">{{ formatModalDate(item.creation) }}</td>
                      <td class="whitespace-nowrap px-3 py-2.5 align-middle">
                        <span class="inline-flex items-center gap-1.5 tabular-nums text-slate-800">
                          {{ formatQty(item.total_actual_qty) }}
                          <svg class="phase-out-ico-sm h-4 w-4 shrink-0 text-emerald-500" width="16" height="16" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                          </svg>
                        </span>
                      </td>
                      <td class="whitespace-nowrap px-3 py-2.5 align-middle text-slate-700">
                        <span class="inline-flex items-center gap-1.5">
                          {{ formatModalDate(item.last_stock_ledger_posting) }}
                          <svg class="phase-out-ico-sm h-4 w-4 shrink-0 text-amber-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                          </svg>
                        </span>
                      </td>
                      <td class="whitespace-nowrap px-3 py-2.5 align-middle">
                        <div v-if="adjustDiscountsEnabled" class="flex items-center gap-2">
                          <button
                            type="button"
                            role="switch"
                            :aria-checked="discountRowOn[item.name] !== false"
                            class="phase-out-toggle relative inline-flex h-6 w-10 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none"
                            :class="discountRowOn[item.name] !== false ? 'bg-sky-600' : 'bg-slate-300'"
                            @click="toggleDiscountRow(item.name)"
                          >
                            <span
                              class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                              :class="discountRowOn[item.name] !== false ? 'translate-x-4' : 'translate-x-0.5'"
                            />
                          </button>
                          <span class="tabular-nums text-slate-800">{{ suggestedDiscountPct(item) }}%</span>
                          <span class="text-xs text-slate-500">({{ discountTier(item).label }})</span>
                        </div>
                        <span v-else class="text-slate-400">—</span>
                      </td>
                    </tr>
                    <tr v-if="candidateRows.length === 0">
                      <td colspan="6" class="px-3 py-8 text-center text-slate-500">
                        <template v-if="candidatesLoadError">Could not load candidates. Adjust filters and try again.</template>
                        <template v-else>No candidates for current filters.</template>
                      </td>
                    </tr>
                  </tbody>
                </table>
              </div>

              <!-- Legend -->
              <div class="flex flex-wrap items-center gap-x-6 gap-y-2 border-t border-slate-200 bg-slate-50/80 px-3 py-2 text-xs text-slate-600">
                <span class="inline-flex items-center gap-1.5">
                  <span class="h-2 w-2 rounded-full bg-rose-500" aria-hidden="true" />
                  High priority, 26–30%
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <span class="h-2 w-2 rounded-full bg-amber-400" aria-hidden="true" />
                  Medium, 15–20%
                </span>
                <span class="inline-flex items-center gap-1.5">
                  <span class="h-2 w-2 rounded-full bg-emerald-500" aria-hidden="true" />
                  Low, 9%
                </span>
              </div>

              <!-- Blue selection bar -->
              <div class="flex flex-wrap items-center justify-between gap-3 bg-sky-600 px-4 py-2.5 text-sm font-medium text-white">
                <span>{{ selectedCount }} Items selected</span>
                <span class="flex items-center gap-2 text-sky-100">
                  <span class="hidden sm:inline">Adjust Suggested Discounts</span>
                  <button
                    type="button"
                    role="switch"
                    :aria-checked="adjustDiscountsEnabled"
                    class="relative inline-flex h-6 w-10 shrink-0 cursor-pointer rounded-full border-2 border-white/40 transition-colors"
                    :class="adjustDiscountsEnabled ? 'bg-white/30' : 'bg-white/10'"
                    @click="adjustDiscountsEnabled = !adjustDiscountsEnabled"
                  >
                    <span
                      class="pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow transition"
                      :class="adjustDiscountsEnabled ? 'translate-x-4' : 'translate-x-0.5'"
                    />
                  </button>
                </span>
              </div>

              <div
                v-if="candidatesMeta && candidatesMeta.last_page > 1"
                class="flex items-center justify-between border-t border-slate-200 bg-white px-3 py-2 text-sm text-slate-600"
              >
                <span>Page {{ candidatesMeta.current_page }} / {{ candidatesMeta.last_page }}</span>
                <div class="flex gap-2">
                  <button
                    type="button"
                    class="rounded border border-slate-300 bg-white px-2 py-1 text-sm hover:bg-slate-50 disabled:opacity-40"
                    :disabled="candidatesMeta.current_page <= 1"
                    @click="loadCandidates(candidatesMeta.current_page - 1)"
                  >
                    Prev
                  </button>
                  <button
                    type="button"
                    class="rounded border border-slate-300 bg-white px-2 py-1 text-sm hover:bg-slate-50 disabled:opacity-40"
                    :disabled="candidatesMeta.current_page >= candidatesMeta.last_page"
                    @click="loadCandidates(candidatesMeta.current_page + 1)"
                  >
                    Next
                  </button>
                </div>
              </div>
            </div>
          </div>
          </div>

          <!-- Footer actions -->
          <div class="flex shrink-0 flex-wrap items-center justify-end gap-3 border-t border-slate-200 bg-white px-4 py-3">
            <button
              type="button"
              class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-800 shadow-sm hover:bg-slate-50"
              @click="closeModal"
            >
              Cancel
            </button>
            <button
              type="button"
              class="phase-out-tag-primary-btn rounded-lg px-5 py-2.5 text-sm font-semibold text-white shadow-md transition hover:brightness-95 disabled:opacity-40"
              :disabled="tagging || selectedCount === 0"
              @click="submitTag"
            >
              Tag {{ selectedCount }} Items as For Phase Out
            </button>
          </div>
        </div>
      </div>
    </Teleport>
  </div>
</template>

<script setup>
import { ref, reactive, computed, onMounted, watch, onUnmounted, nextTick } from 'vue';
import axios, { isAxiosError } from 'axios';
import PhaseOutTaggedTable from '@/components/PhaseOutTaggedTable.vue';

const summaryLoading = ref(true);
const summary = ref({
  tagged_count: 0,
  total_units: 0,
  total_stock_value: 0,
  by_brand: [],
});

const taggedTableRef = ref(null);

const modalOpen = ref(false);
const candidatesLoading = ref(false);
const candidatesLoadError = ref(null);
const candidateRows = ref([]);
const candidatesMeta = ref(null);
const selected = reactive({});
const tagging = ref(false);
const adjustDiscountsEnabled = ref(true);
/** Row toggles for suggested-discount column (UI only; default on). */
const discountRowOn = reactive({});

const filters = reactive({
  brand: '',
  created_before: '',
  no_movement_days: null,
  months: 12,
  excess_stock_only: false,
});

const DONUT_COLORS = ['#0ea5e9', '#8b5cf6', '#f59e0b', '#10b981', '#ec4899', '#64748b'];

function formatMoney(n) {
  if (n === null || n === undefined) return '—';
  return new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD', maximumFractionDigits: 0 }).format(
    Number(n)
  );
}

function formatQty(n) {
  if (n === null || n === undefined) return '—';
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '—';
}

function formatModalDate(val) {
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

function toggleDiscountRow(name) {
  discountRowOn[name] = !(discountRowOn[name] !== false);
}

const brandBars = computed(() => {
  const rows = summary.value.by_brand || [];
  const max = Math.max(1, ...rows.map((r) => r.item_count));
  return rows.map((r) => ({
    brand: r.brand,
    item_count: r.item_count,
    pct: (r.item_count / max) * 100,
  }));
});

const donutLegend = computed(() => {
  const rows = summary.value.by_brand || [];
  return rows.map((r, i) => ({
    brand: r.brand,
    stock_value: r.stock_value,
    color: DONUT_COLORS[i % DONUT_COLORS.length],
  }));
});

const donutStyle = computed(() => {
  const rows = summary.value.by_brand || [];
  const total = rows.reduce((s, r) => s + (Number(r.stock_value) || 0), 0);
  if (total <= 0) {
    return { background: '#e2e8f0' };
  }
  let acc = 0;
  const parts = [];
  rows.forEach((r, i) => {
    const v = Number(r.stock_value) || 0;
    const pct = (v / total) * 100;
    const start = acc;
    acc += pct;
    const color = DONUT_COLORS[i % DONUT_COLORS.length];
    parts.push(`${color} ${start}% ${acc}%`);
  });
  return { background: `conic-gradient(${parts.join(', ')})` };
});

const selectedCount = computed(() => Object.keys(selected).filter((k) => selected[k]).length);

const allSelected = computed(() => {
  if (candidateRows.value.length === 0) {
    return false;
  }
  return candidateRows.value.every((r) => selected[r.name]);
});

async function loadSummary() {
  summaryLoading.value = true;
  try {
    const { data } = await axios.get('/phase-out/summary');
    summary.value = data;
  } catch {
    summary.value = { tagged_count: 0, total_units: 0, total_stock_value: 0, by_brand: [] };
  } finally {
    summaryLoading.value = false;
  }
}

function clearSelection() {
  Object.keys(selected).forEach((k) => {
    delete selected[k];
  });
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

async function openModal() {
  suppressPageLoaderForModal();
  modalOpen.value = true;
  clearSelection();
  candidatesLoadError.value = null;
  await nextTick();
  await new Promise((resolve) => {
    requestAnimationFrame(() => resolve());
  });
  loadCandidates(1);
}

function closeModal() {
  modalOpen.value = false;
  candidatesLoadError.value = null;
}

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

function toggleAll(checked) {
  candidateRows.value.forEach((r) => {
    selected[r.name] = checked;
  });
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
    await loadSummary();
    taggedTableRef.value?.load(1);
  } catch {
    window.alert('Tagging failed. Please try again.');
  } finally {
    tagging.value = false;
  }
}

function onEscapeKey(e) {
  if (e.key === 'Escape') {
    closeModal();
  }
}

watch(modalOpen, (open) => {
  if (open) {
    suppressPageLoaderForModal();
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

onMounted(() => {
  loadSummary();
});
</script>

<style scoped>
.phase-out-bulk-tag-btn {
  background: linear-gradient(135deg, #38bdf8 0%, #2563eb 45%, #1d4ed8 100%);
  box-shadow: 0 10px 25px -5px rgb(37 99 235 / 0.45);
}
.phase-out-bulk-tag-btn:hover {
  background: linear-gradient(135deg, #0ea5e9 0%, #1d4ed8 50%, #1e40af 100%);
  box-shadow: 0 14px 32px -6px rgb(37 99 235 / 0.55);
  transform: translateY(-1px);
}
.phase-out-bulk-tag-btn:active {
  transform: translateY(0);
}

.phase-out-popup-overlay {
  /* Lock to viewport — min-h-screen + tall content was growing the overlay (~5k px) and centering the dialog off-screen */
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
  /* Above layout #loader / #loader-wrapper and any AdminLTE/Bootstrap layers */
  z-index: 2147483646;
  background: rgba(15, 23, 42, 0.48);
  backdrop-filter: blur(6px);
  -webkit-backdrop-filter: blur(6px);
}

.phase-out-popup-panel {
  /* Do not use width:100% here — it fills the row and sticks the dialog to the left */
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
}

/* AdminLTE/Bootstrap often use svg { max-width: 100% } — without a bounded parent, icons fill the screen */
.phase-out-header-icon-wrap {
  width: 2.5rem !important;
  height: 2.5rem !important;
  min-width: 2.5rem !important;
  min-height: 2.5rem !important;
}

.phase-out-popup-panel svg.phase-out-ico-md {
  width: 1.25rem !important;
  height: 1.25rem !important;
  max-width: 1.25rem !important;
  max-height: 1.25rem !important;
  flex-shrink: 0;
}

.phase-out-popup-panel svg.phase-out-ico-sm {
  width: 1rem !important;
  height: 1rem !important;
  max-width: 1rem !important;
  max-height: 1rem !important;
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

.phase-out-tag-primary-btn {
  background-color: #d9534f;
}
.phase-out-tag-primary-btn:hover:not(:disabled) {
  background-color: #c9302c;
}
</style>
