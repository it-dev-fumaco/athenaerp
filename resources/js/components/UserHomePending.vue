<template>
  <div class="pending-home">
    <header class="pending-home__welcome">
      <div class="pending-home__welcome-copy">
        <h1 class="pending-home__title">
          <span class="pending-home__wave" aria-hidden="true">👋</span>
          Welcome back<span v-if="firstName">, <span class="pending-home__name">{{ firstName }}</span></span>
        </h1>
        <p class="pending-home__subtitle">{{ seesAll ? 'View and manage all pending Stock Entry Requests and Delivery Requests for Issuance.' : 'View and manage your pending Stock Entry Requests and Delivery Requests for Issuance.' }}</p>
      </div>
      <svg class="pending-home__welcome-art" viewBox="0 0 240 130" aria-hidden="true" focusable="false">
        <g transform="translate(8,22)">
          <path d="M10 30 L52 8 L94 30 L52 52 Z" fill="#dbeafe"/>
          <path d="M10 30 L52 52 L52 98 L10 76 Z" fill="#93c5fd"/>
          <path d="M52 52 L94 30 L94 76 L52 98 Z" fill="#60a5fa"/>
        </g>
        <g transform="translate(46,48)">
          <path d="M8 26 L46 6 L84 26 L46 46 Z" fill="#f8fbff"/>
          <path d="M8 26 L46 46 L46 88 L8 68 Z" fill="#3b82f6"/>
          <path d="M46 46 L84 26 L84 68 L46 88 Z" fill="#2563eb"/>
          <path d="M46 6 L46 46" stroke="#bfdbfe" stroke-width="4"/>
          <rect x="38" y="14" width="16" height="12" rx="1.5" fill="#dbeafe" transform="rotate(-32 46 20)"/>
        </g>
        <g transform="translate(150,8)">
          <rect x="6" y="18" width="76" height="100" rx="8" fill="#ffffff" stroke="#dbeafe" stroke-width="2"/>
          <rect x="26" y="8" width="36" height="16" rx="4" fill="#3b82f6"/>
          <circle cx="44" cy="16" r="3" fill="#eff6ff"/>
          <rect x="18" y="38" width="12" height="12" rx="3" fill="#dcfce7"/>
          <path d="M21 44 l2.4 2.4 l5 -5.2" fill="none" stroke="#16a34a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          <line x1="36" y1="44" x2="70" y2="44" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>
          <rect x="18" y="58" width="12" height="12" rx="3" fill="#dcfce7"/>
          <path d="M21 64 l2.4 2.4 l5 -5.2" fill="none" stroke="#16a34a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>
          <line x1="36" y1="64" x2="70" y2="64" stroke="#cbd5e1" stroke-width="3" stroke-linecap="round"/>
          <rect x="18" y="78" width="12" height="12" rx="3" fill="#f1f5f9" stroke="#e2e8f0"/>
          <line x1="36" y1="84" x2="62" y2="84" stroke="#e2e8f0" stroke-width="3" stroke-linecap="round"/>
        </g>
      </svg>
    </header>

    <div class="pending-home__summary" :class="{ 'pending-home__summary--with-reminder': !reminderHidden }">
      <button type="button" class="pending-home__metric pending-home__metric--blue" @click="showTab('stock')">
        <span class="pending-home__metric-top">
          <span class="pending-home__metric-icon" aria-hidden="true"><i class="fas fa-file-alt"></i></span>
          <span class="pending-home__metric-label">Pending Stock Entry Requests</span>
          <i class="fas fa-chevron-right pending-home__metric-chevron" aria-hidden="true"></i>
        </span>
        <strong>{{ loading ? '—' : stockEntries.length }}</strong>
        <span class="pending-home__metric-hint">Waiting for issuance</span>
      </button>

      <button type="button" class="pending-home__metric pending-home__metric--green" @click="showTab('delivery')">
        <span class="pending-home__metric-top">
          <span class="pending-home__metric-icon" aria-hidden="true"><i class="fas fa-truck"></i></span>
          <span class="pending-home__metric-label">Pending Delivery Requests for Issuance</span>
          <i class="fas fa-chevron-right pending-home__metric-chevron" aria-hidden="true"></i>
        </span>
        <strong>{{ loading ? '—' : deliveries.length }}</strong>
        <span class="pending-home__metric-hint">Waiting for delivery / issuance</span>
      </button>

      <aside v-if="!reminderHidden" class="pending-home__reminder">
        <span class="pending-home__reminder-icon" aria-hidden="true"><i class="fas fa-info-circle"></i></span>
        <div>
          <h2>Important Reminder</h2>
          <p>Please submit all Stock Entries (STE) promptly. <strong>Unsubmitted STE quantities remain floating and may affect inventory accuracy.</strong></p>
        </div>
        <button type="button" class="pending-home__reminder-close" aria-label="Dismiss reminder" @click="dismissReminder">
          <i class="fas fa-times" aria-hidden="true"></i>
        </button>
      </aside>
    </div>

    <div id="pending-home-table" class="pending-home__panel">
      <div class="pending-home__tabs" role="tablist">
        <button
          type="button"
          role="tab"
          :aria-selected="tab === 'stock'"
          :class="{ 'is-active': tab === 'stock' }"
          @click="showTab('stock')"
        >
          <i class="fas fa-file-alt" aria-hidden="true"></i>
          Stock Entry Requests ({{ stockEntries.length }})
        </button>
        <button
          type="button"
          role="tab"
          :aria-selected="tab === 'delivery'"
          :class="{ 'is-active': tab === 'delivery' }"
          @click="showTab('delivery')"
        >
          <i class="fas fa-truck" aria-hidden="true"></i>
          Delivery Requests for Issuance ({{ deliveries.length }})
        </button>
      </div>

      <div class="pending-home__filters">
        <label class="pending-home__search">
          <i class="fas fa-search" aria-hidden="true"></i>
          <input v-model="search" type="search" :placeholder="searchPlaceholder">
        </label>
        <select v-model="status" aria-label="Status">
          <option value="">All Status</option>
          <option v-for="option in statusOptions" :key="option.value" :value="option.value">{{ option.label }}</option>
        </select>
        <select v-model="warehouse" aria-label="Warehouse">
          <option value="">All Warehouses</option>
          <option v-for="name in warehouseOptions" :key="name" :value="name">{{ name }}</option>
        </select>
        <label class="pending-home__dates">
          <span>Date Range</span>
          <input v-model="dateFrom" type="date" aria-label="From date">
          <input v-model="dateTo" type="date" aria-label="To date">
        </label>
      </div>

      <p v-if="loading" class="pending-home__state">Loading pending requests…</p>
      <p v-else-if="error" class="pending-home__state pending-home__state--error">{{ error }}</p>
      <p v-else-if="rows.length === 0" class="pending-home__state">{{ emptyMessage }}</p>
      <div v-else class="pending-home__table-wrap">
        <table class="pending-home__table">
          <thead>
            <tr>
              <th>{{ tab === 'delivery' ? 'DR No.' : 'STE No.' }}</th>
              <th>Date Requested</th>
              <th>Requested By</th>
              <th>Warehouse</th>
              <th>Items / Qty</th>
              <th>Status</th>
              <th>Days Pending</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in pagedRows" :key="row.number">
              <td>
                <div class="pending-home__doc">
                  <span class="pending-home__doc-icon" aria-hidden="true">
                    <i :class="row.type_label === 'Delivery Request' ? 'fas fa-truck' : 'fas fa-file-alt'"></i>
                  </span>
                  <span>
                    <a
                      v-if="erpDocumentUrl(row.number)"
                      class="pending-home__erp-link"
                      :href="erpDocumentUrl(row.number)"
                      target="_blank"
                      rel="noopener"
                    ><strong>{{ row.number }}</strong></a>
                    <strong v-else>{{ row.number }}</strong>
                    <small>{{ row.type_label }}</small>
                  </span>
                </div>
              </td>
              <td>
                <div class="pending-home__date">
                  <strong>{{ row.date_label }}</strong>
                  <small>{{ row.time_label }}</small>
                </div>
              </td>
              <td>
                <div class="pending-home__person">
                  <strong>{{ row.requester_name }}</strong>
                  <small v-if="row.department">{{ row.department }}</small>
                </div>
              </td>
              <td>{{ row.warehouse || '—' }}</td>
              <td>
                <div class="pending-home__qty">
                  <strong>{{ itemLabel(row.item_count) }}</strong>
                  <small v-if="row.qty_label">{{ row.qty_label }}</small>
                </div>
              </td>
              <td>
                <span class="pending-home__badge" :class="'pending-home__badge--' + row.status">{{ row.status_label }}</span>
              </td>
              <td>
                <span class="pending-home__days" :class="dayClass(row.days_pending)">{{ dayLabel(row.days_pending) }}</span>
              </td>
              <td>
                <button type="button" class="pending-home__view" @click="openDocument(row)">View</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-if="!loading && !error && rows.length > 0" class="pending-home__pager">
        <span>Showing {{ pageStart }}–{{ pageEnd }} of {{ rows.length }}</span>
        <div class="pending-home__pager-controls">
          <button type="button" :disabled="page === 1" @click="showPage(page - 1)">Previous</button>
          <button
            v-if="pageNumbers[0] > 1"
            type="button"
            @click="showPage(1)"
          >1</button>
          <span v-if="pageNumbers[0] > 2" class="pending-home__pager-gap">…</span>
          <button
            v-for="number in pageNumbers"
            :key="number"
            type="button"
            :class="{ 'is-active': number === page }"
            @click="showPage(number)"
          >{{ number }}</button>
          <span v-if="pageNumbers[pageNumbers.length - 1] < pageCount - 1" class="pending-home__pager-gap">…</span>
          <button
            v-if="pageNumbers[pageNumbers.length - 1] < pageCount"
            type="button"
            @click="showPage(pageCount)"
          >{{ pageCount }}</button>
          <button type="button" :disabled="page === pageCount" @click="showPage(page + 1)">Next</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import axios from 'axios';

const REMINDER_KEY = 'user-home-pending-reminder-dismissed';

const loading = ref(true);
const error = ref('');
const firstName = ref('');
const seesAll = ref(false);
const erpBaseUrl = ref('');
const stockEntries = ref([]);
const deliveries = ref([]);
const tab = ref('stock');
const PAGE_SIZE = 10;
const page = ref(1);
const search = ref('');
const status = ref('');
const warehouse = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const reminderHidden = ref(false);
const openingId = ref('');

const sourceRows = computed(() => (tab.value === 'delivery' ? deliveries.value : stockEntries.value));

const statusOptions = computed(() => {
  if (tab.value === 'delivery') {
    return [{ value: 'pending_delivery', label: 'Pending Delivery' }];
  }
  return [
    { value: 'pending_issuance', label: 'Pending Issuance' },
  ];
});

const warehouseOptions = computed(() => {
  const names = new Set();
  sourceRows.value.forEach((row) => {
    (row.warehouses || []).forEach((name) => {
      if (name) names.add(name);
    });
  });
  return [...names].sort((a, b) => a.localeCompare(b));
});

const searchPlaceholder = computed(() => (
  tab.value === 'delivery'
    ? 'Search by DR No., Item, or Remarks...'
    : 'Search by STE No., Item, or Remarks...'
));

const rows = computed(() => {
  const query = search.value.trim().toLowerCase();
  return sourceRows.value.filter((row) => {
    if (query && !String(row.search_text || '').includes(query)) return false;
    if (status.value && row.status !== status.value) return false;
    if (warehouse.value && !(row.warehouses || []).includes(warehouse.value)) return false;
    if (dateFrom.value && row.created_on < dateFrom.value) return false;
    if (dateTo.value && row.created_on > dateTo.value) return false;
    return true;
  });
});

const pageCount = computed(() => Math.max(1, Math.ceil(rows.value.length / PAGE_SIZE)));

const pagedRows = computed(() => {
  const start = (page.value - 1) * PAGE_SIZE;
  return rows.value.slice(start, start + PAGE_SIZE);
});

const pageStart = computed(() => (rows.value.length === 0 ? 0 : (page.value - 1) * PAGE_SIZE + 1));
const pageEnd = computed(() => Math.min(page.value * PAGE_SIZE, rows.value.length));

const pageNumbers = computed(() => {
  const total = pageCount.value;
  const current = page.value;
  let start = Math.max(1, current - 2);
  let end = Math.min(total, start + 4);
  start = Math.max(1, end - 4);
  const pages = [];
  for (let number = start; number <= end; number += 1) pages.push(number);
  return pages;
});

const emptyMessage = computed(() => {
  const noun = tab.value === 'delivery' ? 'delivery requests' : 'stock entry requests';
  if (sourceRows.value.length === 0) {
    return `You have no pending ${noun}.`;
  }
  return `No pending ${noun} match these filters.`;
});

function itemLabel(count) {
  const total = Number(count) || 0;
  return `${total} ${total === 1 ? 'item' : 'items'}`;
}

function dayLabel(days) {
  const total = Number(days) || 0;
  return `${total} ${total === 1 ? 'day' : 'days'}`;
}

function dayClass(days) {
  const total = Number(days) || 0;
  if (total >= 30) return 'pending-home__days--late';
  if (total >= 7) return 'pending-home__days--warn';
  return '';
}

function erpDocumentUrl(number) {
  const base = String(erpBaseUrl.value || '').replace(/\/$/, '');
  const name = String(number || '');
  if (!base || !name) return '';
  let slug = '';
  if (name.startsWith('STE')) slug = 'stock-entry';
  else if (name.startsWith('MAT-PAC')) slug = 'packing-slip';
  if (!slug) return '';
  return `${base}/app/${slug}/${encodeURIComponent(name)}`;
}

function showPage(next) {
  page.value = Math.min(pageCount.value, Math.max(1, next));
}

watch([search, status, warehouse, dateFrom, dateTo, tab], () => {
  page.value = 1;
});

function showTab(next) {
  if (tab.value !== next) {
    tab.value = next;
    status.value = '';
    warehouse.value = '';
  }
  nextTick(() => {
    document.getElementById('pending-home-table')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
}

function dismissReminder() {
  reminderHidden.value = true;
  try {
    sessionStorage.setItem(REMINDER_KEY, '1');
  } catch (_) {}
}

function notify(type, message) {
  const $ = window.jQuery;
  if ($?.notify) {
    $.notify({
      icon: type === 'success' ? 'fa fa-check' : 'fa fa-info',
      message,
    }, {
      type,
      timer: 500,
      z_index: 1060,
      placement: { from: 'top', align: 'center' },
    });
    return;
  }
  window.alert(String(message).replace(/<[^>]*>/g, ''));
}

function csrfToken() {
  return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function errorText(err) {
  const data = err?.response?.data;
  if (data && typeof data === 'object') {
    return data.modal_message || data.message || 'Could not open this document.';
  }
  if (typeof data === 'string') {
    try {
      const parsed = JSON.parse(data);
      return parsed.modal_message || parsed.message || 'Could not open this document.';
    } catch (_) {}
  }
  return 'Could not open this document.';
}

async function loadPending() {
  const { data } = await axios.get('/user-home/pending');
  firstName.value = typeof data.first_name === 'string' ? data.first_name : '';
  seesAll.value = data.sees_all === true;
  erpBaseUrl.value = typeof data.erp_web_base_url === 'string' ? data.erp_web_base_url : '';
  stockEntries.value = Array.isArray(data.stock_entries) ? data.stock_entries : [];
  deliveries.value = Array.isArray(data.deliveries) ? data.deliveries : [];
}

function bindCheckoutForm($form) {
  const $ = window.jQuery;
  if (!$form?.length || typeof $.fn.validate !== 'function') return;
  $form.validate({
    rules: {
      barcode: { required: true },
      qty: { required: true },
    },
    messages: {
      barcode: { required: 'Please enter barcode' },
      qty: { required: 'Please enter quantity' },
    },
    errorElement: 'span',
    errorPlacement(error, element) {
      error.addClass('invalid-feedback');
      element.closest('.form-group').append(error);
    },
    highlight(element) {
      $(element).addClass('is-invalid');
    },
    unhighlight(element) {
      $(element).removeClass('is-invalid');
    },
    submitHandler(form) {
      const $current = $(form);
      $current.find('button').prop('disabled', true);
      $.ajax({
        type: 'POST',
        url: $current.attr('action'),
        data: $current.serialize(),
        success(response) {
          $current.find('button').prop('disabled', false);
          if (response?.status || response?.success) {
            notify('success', response.message || 'Saved.');
            $current.closest('.modal').modal('hide');
            loadPending().catch(() => {});
          } else {
            notify('danger', response?.message || 'Could not check out this item.');
          }
        },
        error(xhr) {
          $current.find('button').prop('disabled', false);
          const payload = xhr.responseJSON || {};
          notify('danger', payload.modal_message || payload.message || 'Could not check out this item.');
        },
      });
    },
  });
}

function mountModal(selector, html, checkoutUrl) {
  const $ = window.jQuery;
  const $modal = $(selector);
  if (!$modal.parent().is('body')) {
    $modal.appendTo(document.body);
  }
  if (html.includes('modal-dialog')) {
    $modal.html(html);
  } else {
    $modal.html(
      `<form method="POST" action="${checkoutUrl}">
        <input type="hidden" name="_token" value="${csrfToken()}">
        <div class="modal-dialog">${html}</div>
      </form>`
    );
  }
  bindCheckoutForm($modal.find('form').first());
  $modal.modal('show');
}

async function openDocument(row) {
  if (!row?.line_id) {
    if (row?.view_url) window.location.href = row.view_url;
    return;
  }
  if (openingId.value) return;
  openingId.value = row.line_id;
  try {
    const isDelivery = row.modal === 'ps';
    const { data } = await axios.get(
      isDelivery
        ? `/get_ps_details/${encodeURIComponent(row.line_id)}`
        : `/get_ste_details/${encodeURIComponent(row.line_id)}`,
      {
        params: isDelivery ? { type: 'picking_slip' } : {},
        responseType: 'text',
        transformResponse: [(value) => value],
      },
    );
    const html = typeof data === 'string' ? data.trim() : '';
    if (!html || html.startsWith('{') || html.startsWith('[')) {
      let message = 'Could not open this document.';
      try {
        const parsed = JSON.parse(html);
        message = parsed.modal_message || parsed.message || message;
      } catch (_) {}
      notify('danger', message);
      return;
    }
    mountModal(isDelivery ? '#ps-modal' : '#ste-modal', html, row.checkout_url || '');
  } catch (err) {
    notify('danger', errorText(err));
  } finally {
    openingId.value = '';
  }
}

function bindCheckoutButtons() {
  const $ = window.jQuery;
  if (!$ || window.__userHomeCheckoutBound) return;
  window.__userHomeCheckoutBound = true;
  $(document).on('click', '#btn-check-out, #btn-deduct-res, #btn-check-out-1, #btn-deduct-res-1', function (event) {
    const $modal = $(this).closest('.modal');
    if (!$modal.is('#ste-modal') && !$modal.is('#ps-modal')) return;
    event.preventDefault();
    $modal.find('input[name="deduct_reserve"]').val(String(this.id).includes('deduct') ? 1 : 0);
    $modal.find('form').trigger('submit');
  });
}

onMounted(async () => {
  bindCheckoutButtons();
  try {
    reminderHidden.value = sessionStorage.getItem(REMINDER_KEY) === '1';
  } catch (_) {}

  try {
    await loadPending();
  } catch (_) {
    error.value = 'Pending requests could not be loaded.';
  } finally {
    loading.value = false;
  }
});
</script>

<style scoped>
.pending-home {
  box-sizing: border-box;
  width: 100%;
  max-width: 1280px;
  margin: 0 auto;
  padding: 1rem 1.1rem 1.75rem;
  color: #0f172a;
}
.pending-home__welcome {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 1rem;
  margin-bottom: 0.9rem;
  padding: 1rem 1.15rem 1rem 1.3rem;
  border-radius: 1rem;
  background: linear-gradient(90deg, #e7f2ff 0%, #f4f9ff 58%, #eef6ff 100%);
  overflow: hidden;
}
.pending-home__welcome-copy {
  min-width: 0;
}
.pending-home__wave {
  margin-right: 0.3rem;
}
.pending-home__title {
  margin: 0;
  font-size: 1.35rem;
  font-weight: 700;
  line-height: 1.25;
  color: #0f172a;
}
.pending-home__name {
  color: #2563eb;
}
.pending-home__subtitle {
  margin: 0.3rem 0 0;
  font-size: 0.86rem;
  color: #64748b;
}
.pending-home__welcome-art {
  flex: 0 0 auto;
  width: 11.5rem;
  height: auto;
}
@media (max-width: 699px) {
  .pending-home__welcome-art {
    display: none;
  }
}
.pending-home__summary,
.pending-home__previews {
  display: grid;
  grid-template-columns: 1fr;
  gap: 0.75rem;
  margin-bottom: 0.75rem;
}
@media (min-width: 900px) {
  .pending-home__summary,
  .pending-home__previews {
    grid-template-columns: 1fr 1fr;
  }
  .pending-home__summary--with-reminder {
    grid-template-columns: 1.1fr 1.1fr 0.9fr;
  }
}
.pending-home__metric {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
  gap: 0.15rem;
  min-width: 0;
  padding: 0.85rem 0.95rem;
  border: 1px solid transparent;
  border-radius: 0.85rem;
  text-align: left;
  cursor: pointer;
}
.pending-home__metric--blue {
  background: #eef4ff;
  border-color: #d7e4ff;
}
.pending-home__metric--green {
  background: #e9f8ef;
  border-color: #cfeedd;
}
.pending-home__metric-top {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  width: 100%;
  min-width: 0;
}
.pending-home__metric-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.9rem;
  height: 1.9rem;
  flex-shrink: 0;
  border-radius: 0.55rem;
  background: #fff;
  font-size: 0.85rem;
}
.pending-home__metric--blue .pending-home__metric-icon { color: #2563eb; }
.pending-home__metric--green .pending-home__metric-icon { color: #16a34a; }
.pending-home__metric-label {
  flex: 1;
  min-width: 0;
  font-size: 0.82rem;
  font-weight: 700;
  color: #0f172a;
}
.pending-home__metric-chevron {
  flex-shrink: 0;
  color: #94a3b8;
  font-size: 0.75rem;
}
.pending-home__metric strong {
  margin-top: 0.35rem;
  font-size: 1.7rem;
  line-height: 1;
  color: #0f172a;
}
.pending-home__metric-hint {
  font-size: 0.75rem;
  color: #64748b;
}
.pending-home__reminder {
  display: flex;
  align-items: flex-start;
  gap: 0.5rem;
  padding: 0.75rem 0.8rem;
  border: 1px solid #d6e6fb;
  border-radius: 0.85rem;
  background: #eef6ff;
}
.pending-home__reminder h2 {
  margin: 0;
  font-size: 0.8rem;
  font-weight: 700;
  color: #1d4ed8;
}
.pending-home__reminder p {
  margin: 0.15rem 0 0;
  font-size: 0.72rem;
  line-height: 1.4;
  color: #334155;
}
.pending-home__reminder-icon {
  color: #2563eb;
  font-size: 1rem;
  line-height: 1.2;
}
.pending-home__reminder-close {
  margin-left: auto;
  border: 0;
  background: transparent;
  color: #64748b;
  cursor: pointer;
  padding: 0;
}
.pending-home__card,
.pending-home__panel {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 0.85rem;
  box-shadow: 0 4px 14px rgba(15, 23, 42, 0.04);
  overflow: hidden;
}
.pending-home__panel { margin-bottom: 0.75rem; }
.pending-home__card-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 0.9rem 0.55rem;
}
.pending-home__card-header h2,
.pending-home__card-title {
  display: flex;
  align-items: center;
  gap: 0.55rem;
  min-width: 0;
  margin: 0;
  font-size: 0.95rem;
  font-weight: 700;
  color: #0f172a;
}
.pending-home__card-logo {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.75rem;
  height: 1.75rem;
  flex-shrink: 0;
  border-radius: 0.45rem;
  background: #e8f1ff;
  color: #2563eb;
  font-size: 0.8rem;
}
.pending-home__card-logo--green {
  background: #e8f8ef;
  color: #16a34a;
}
.pending-home__view-all {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  flex-shrink: 0;
  border: 0;
  background: transparent;
  color: #2563eb;
  font-size: 0.82rem;
  font-weight: 600;
  cursor: pointer;
  padding: 0;
}
span.pending-home__view-all {
  cursor: default;
}
.pending-home__tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  padding: 0.4rem 0.7rem 0;
  border-bottom: 1px solid #e2e8f0;
}
.pending-home__tabs button {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  margin-bottom: -1px;
  padding: 0.45rem 0.6rem;
  border: 0;
  border-bottom: 2px solid transparent;
  background: transparent;
  color: #64748b;
  font-size: 0.8rem;
  font-weight: 600;
  cursor: pointer;
}
.pending-home__tabs button.is-active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}
.pending-home__filters {
  display: flex;
  flex-wrap: wrap;
  gap: 0.45rem;
  align-items: center;
  padding: 0.55rem 0.7rem;
}
.pending-home__search {
  display: flex;
  align-items: center;
  gap: 0.45rem;
  flex: 1 1 220px;
  min-width: 200px;
  padding: 0.3rem 0.55rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.6rem;
  background: #fff;
  color: #94a3b8;
}
.pending-home__search input,
.pending-home__filters select,
.pending-home__dates input {
  border: 1px solid #e2e8f0;
  border-radius: 0.6rem;
  background: #fff;
  color: #0f172a;
  font-size: 0.8rem;
  padding: 0.32rem 0.5rem;
}
.pending-home__search input {
  flex: 1;
  min-width: 0;
  border: 0;
  padding: 0;
  outline: none;
}
.pending-home__dates {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  color: #475569;
  font-size: 0.82rem;
  font-weight: 600;
}
.pending-home__state {
  margin: 0;
  padding: 1.1rem 0.9rem 1.25rem;
  text-align: center;
  color: #64748b;
  font-size: 0.82rem;
}
.pending-home__state--error { color: #b91c1c; }
.pending-home__table-wrap { overflow-x: auto; }
.pending-home__previews .pending-home__table-wrap { overflow-x: hidden; }
.pending-home__preview,
.pending-home__table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.8rem;
}
.pending-home__preview {
  table-layout: fixed;
}
.pending-home__preview th:nth-child(1),
.pending-home__preview td:nth-child(1) { width: 27%; }
.pending-home__preview th:nth-child(2),
.pending-home__preview td:nth-child(2) { width: 22%; }
.pending-home__preview th:nth-child(3),
.pending-home__preview td:nth-child(3) { width: 18%; }
.pending-home__preview th:nth-child(4),
.pending-home__preview td:nth-child(4) { width: 20%; }
.pending-home__preview th:nth-child(5),
.pending-home__preview td:nth-child(5) { width: 13%; }
.pending-home__preview th,
.pending-home__table th {
  padding: 0.45rem 0.7rem;
  border-top: 1px solid #e2e8f0;
  background: #f8fafc;
  color: #64748b;
  font-size: 0.72rem;
  font-weight: 700;
  text-align: left;
  white-space: nowrap;
}
.pending-home__preview th,
.pending-home__preview td {
  padding: 0.4rem 0.45rem;
}
.pending-home__preview td,
.pending-home__table td {
  padding: 0.55rem 0.7rem;
  border-top: 1px solid #eef2f7;
  vertical-align: middle;
  color: #0f172a;
}
.pending-home__preview td {
  padding: 0.4rem 0.45rem;
}
.pending-home__clip {
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}
.pending-home__preview .pending-home__age {
  overflow: hidden;
  color: #64748b;
  white-space: nowrap;
  text-overflow: ellipsis;
}
.pending-home__doc-link,
.pending-home__doc,
.pending-home__date,
.pending-home__person,
.pending-home__qty {
  display: flex;
  flex-direction: column;
  gap: 0.1rem;
}
.pending-home__doc-link,
.pending-home__doc {
  flex-direction: row;
  align-items: center;
  gap: 0.5rem;
  min-width: 0;
  color: inherit;
  text-decoration: none;
}
button.pending-home__doc-link {
  width: 100%;
  border: 0;
  background: transparent;
  padding: 0;
  font: inherit;
  text-align: left;
  cursor: pointer;
}
.pending-home__preview .pending-home__doc-link strong {
  min-width: 0;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  font-size: 0.75rem;
}
.pending-home__preview .pending-home__doc-icon {
  width: 1.25rem;
  height: 1.25rem;
  font-size: 0.65rem;
}
.pending-home__preview .pending-home__badge {
  display: inline-block;
  max-width: 100%;
  overflow: hidden;
  text-overflow: ellipsis;
  vertical-align: middle;
  padding: 0.08rem 0.35rem;
  font-size: 0.65rem;
}
.pending-home__doc-link strong,
.pending-home__doc strong,
.pending-home__date strong,
.pending-home__person strong,
.pending-home__qty strong {
  font-weight: 700;
  color: #0f172a;
}
.pending-home__doc-link small,
.pending-home__doc small,
.pending-home__date small,
.pending-home__person small,
.pending-home__qty small {
  display: block;
  color: #64748b;
  font-size: 0.72rem;
}
.pending-home__doc-icon {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 1.55rem;
  height: 1.55rem;
  flex-shrink: 0;
  border-radius: 0.45rem;
  background: #eef4ff;
  color: #2563eb;
}
.pending-home__doc-icon--green {
  background: #e9f8ef;
  color: #16a34a;
}
.pending-home__badge {
  display: inline-flex;
  align-items: center;
  padding: 0.12rem 0.45rem;
  border-radius: 999px;
  font-size: 0.7rem;
  font-weight: 700;
  white-space: nowrap;
}
.pending-home__badge--pending_issuance { background: #e8f1ff; color: #1d4ed8; }
.pending-home__badge--pending_delivery { background: #f3e8ff; color: #7e22ce; }
.pending-home__days {
  font-weight: 600;
  color: #334155;
  white-space: nowrap;
}
.pending-home__days--warn { color: #c2410c; }
.pending-home__days--late { color: #b91c1c; }
.pending-home__view {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-width: 3.4rem;
  padding: 0.22rem 0.55rem;
  border: 1px solid #dbe3ee;
  border-radius: 0.5rem;
  background: #fff;
  color: #334155 !important;
  font-size: 0.8rem;
  font-weight: 700;
  font-family: inherit;
  text-decoration: none !important;
  cursor: pointer;
}
.pending-home__view:hover { background: #f8fafc; }
.pending-home__pager {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.6rem;
  padding: 0.65rem 0.75rem;
  border-top: 1px solid #e2e8f0;
  color: #64748b;
  font-size: 0.78rem;
}
.pending-home__pager-controls {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 0.3rem;
}
.pending-home__pager-controls button {
  min-width: 2rem;
  padding: 0.28rem 0.5rem;
  border: 1px solid #e2e8f0;
  border-radius: 0.45rem;
  background: #fff;
  color: #334155;
  font-size: 0.78rem;
  font-weight: 600;
  cursor: pointer;
}
.pending-home__pager-controls button.is-active {
  border-color: #2563eb;
  background: #2563eb;
  color: #fff;
}
.pending-home__pager-controls button:disabled {
  color: #94a3b8;
  cursor: default;
}
.pending-home__pager-gap {
  padding: 0 0.15rem;
  color: #94a3b8;
}
.pending-home__erp-link {
  color: inherit;
  text-decoration: none;
}
.pending-home__erp-link:hover strong {
  color: #2563eb;
}
a.pending-home__doc-link {
  cursor: pointer;
}
</style>
