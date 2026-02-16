<template>
  <div class="item-profile-stock-reservation" @click="onContainerClick">
    <div v-if="loading" class="container d-flex justify-content-center align-items-center p-5">
      <div class="spinner-border" role="status"><span class="sr-only">Loading...</span></div>
    </div>
    <template v-else-if="apiData">
      <!-- Website Stock Reservations -->
      <template v-if="apiData.web.data.length > 0">
        <h6 class="font-weight-bold text-uppercase font-responsive"><i class="fas fa-box"></i> Website Stock Reservations</h6>
        <table class="table table-hover table-bordered table-sm stock-ledger-table-font" style="font-size: 9pt !important;">
          <thead>
            <tr>
              <th class="text-center p-1" style="width: 10% !important">Transaction</th>
              <th class="text-center p-1 d-md-none">Details</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Reserved Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Issued Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Warehouse</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Status</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12% !important">Created by</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12% !important">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in apiData.web.data" :key="row.name">
              <td class="text-center align-middle p-1">
                <span class="d-block font-weight-bold">{{ formatDate(row.creation) }}</span>
                <small>{{ row.name }}</small>
                <div class="col-10 d-md-none mx-auto">
                  <template v-if="['Active', 'Partially Issued'].includes(row.status)">
                    <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Update</button>
                    <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Cancel</button>
                  </template>
                  <template v-else><br>No Actions Available</template>
                </div>
              </td>
              <td class="d-md-none font-responsive" style="width: 70%">
                <center><span class="badge" :class="'badge-' + row.badge" style="font-size: 10pt;">{{ row.status }}</span></center><br/>
                <span><b>Reserved Qty:</b> {{ row.reserved_qty_formatted }} {{ row.stock_uom }}</span><br>
                <span><b>Issued Qty:</b> {{ row.consumed_qty_formatted }} {{ row.stock_uom }}</span><br>
                <span><b>Warehouse:</b> {{ row.warehouse }}</span><br>
                <span><b>Created by:</b> {{ row.created_by }}</span><br>
              </td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">
                <span class="font-weight-bold">{{ row.reserved_qty_formatted }}</span>
                <small>{{ row.stock_uom }}</small>
              </td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">
                <span class="font-weight-bold">{{ row.consumed_qty_formatted }}</span>
                <small>{{ row.stock_uom }}</small>
              </td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.warehouse }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">
                <span class="badge" :class="'badge-' + row.badge" style="font-size: 9pt;">{{ row.status }}</span>
              </td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.created_by }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">
                <template v-if="['Active', 'Partially Issued'].includes(row.status)">
                  <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Update</button>
                  <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Cancel</button>
                </template>
                <template v-else>No Actions Available</template>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="box-footer clearfix" style="font-size: 16pt;">
          <nav><ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: apiData.web.meta.current_page <= 1 }">
              <a class="page-link" href="#" @click.prevent="goToPage('web', apiData.web.meta.current_page - 1)">«</a>
            </li>
            <li v-for="p in webPageNumbers" :key="'w'+p" class="page-item" :class="{ active: p === apiData.web.meta.current_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('web', p)">{{ p }}</a>
            </li>
            <li class="page-item" :class="{ disabled: apiData.web.meta.current_page >= apiData.web.meta.last_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('web', apiData.web.meta.current_page + 1)">»</a>
            </li>
          </ul></nav>
        </div>
      </template>

      <!-- Consignment Reservations -->
      <template v-if="apiData.consignment.data.length > 0">
        <h6 class="font-weight-bold text-uppercase font-responsive"><i class="fas fa-box"></i> Consignment Reservations</h6>
        <table class="table table-hover table-bordered table-sm stock-ledger-table-font" style="font-size: 9pt !important;">
          <thead>
            <tr>
              <th class="text-center p-1" style="width: 10% !important">Transaction</th>
              <th class="text-center p-1 d-md-none">Details</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Reserved Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Issued Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Warehouse</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Branch</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Status</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12% !important">Created by</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12% !important">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in apiData.consignment.data" :key="row.name">
              <td class="text-center align-middle p-1">
                <span class="d-block font-weight-bold">{{ formatDate(row.creation) }}</span>
                <small>{{ row.name }}</small>
                <div class="col-10 d-md-none mx-auto">
                  <template v-if="['Active', 'Partially Issued'].includes(row.status)">
                    <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Update</button>
                    <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Cancel</button>
                  </template>
                  <template v-else><br>No Actions Available</template>
                </div>
              </td>
              <td class="d-md-none font-responsive" style="width: 70%">
                <center><span class="badge" :class="'badge-' + row.badge" style="font-size: 10pt;">{{ row.status }}</span></center><br/>
                <span><b>Reserved Qty:</b> {{ row.reserved_qty_formatted }} {{ row.stock_uom }}</span><br>
                <span><b>Issued Qty:</b> {{ row.consumed_qty_formatted }} {{ row.stock_uom }}</span><br>
                <span><b>Warehouse:</b> {{ row.warehouse }}</span><br>
                <span><b>Branch:</b> {{ row.consignment_warehouse || '-' }}</span><br>
                <span><b>Created by:</b> {{ row.created_by }}</span><br>
              </td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">
                <span class="font-weight-bold">{{ row.reserved_qty_formatted }}</span>
                <small>{{ row.stock_uom }}</small>
              </td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">
                <span class="font-weight-bold">{{ row.consumed_qty_formatted }}</span>
                <small>{{ row.stock_uom }}</small>
              </td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.warehouse }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.consignment_warehouse || '-' }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">
                <span class="badge" :class="'badge-' + row.badge" style="font-size: 9pt;">{{ row.status }}</span>
              </td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.created_by }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">
                <template v-if="['Active', 'Partially Issued'].includes(row.status)">
                  <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Update</button>
                  <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Cancel</button>
                </template>
                <template v-else>No Actions Available</template>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="box-footer clearfix" style="font-size: 16pt;">
          <nav><ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: apiData.consignment.meta.current_page <= 1 }">
              <a class="page-link" href="#" @click.prevent="goToPage('consignment', apiData.consignment.meta.current_page - 1)">«</a>
            </li>
            <li v-for="p in consignmentPageNumbers" :key="'c'+p" class="page-item" :class="{ active: p === apiData.consignment.meta.current_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('consignment', p)">{{ p }}</a>
            </li>
            <li class="page-item" :class="{ disabled: apiData.consignment.meta.current_page >= apiData.consignment.meta.last_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('consignment', apiData.consignment.meta.current_page + 1)">»</a>
            </li>
          </ul></nav>
        </div>
      </template>

      <!-- In-house Reservations -->
      <template v-if="apiData.inhouse.data.length > 0">
        <h6 class="font-weight-bold text-uppercase font-responsive"><i class="fas fa-box"></i> In-house Reservations</h6>
        <table class="table table-hover table-bordered table-sm stock-ledger-table-font" style="font-size: 9pt !important;">
          <thead>
            <tr>
              <th class="text-center p-1" style="width: 10% !important">Transaction</th>
              <th class="text-center p-1 d-md-none">Details</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Reserved Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Issued Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Warehouse</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Sales Person</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Validity</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10% !important">Status</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12% !important">Created by</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12% !important">Action</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in apiData.inhouse.data" :key="row.name">
              <td class="text-center align-middle p-1">
                <span class="d-block font-weight-bold">{{ formatDate(row.creation) }}</span>
                <small>{{ row.name }}</small>
                <div class="col-10 d-md-none mx-auto">
                  <template v-if="['Active', 'Partially Issued'].includes(row.status)">
                    <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Edit</button>
                    <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Cancel</button>
                  </template>
                  <template v-else><br>No Actions Available</template>
                </div>
              </td>
              <td class="d-md-none font-responsive" style="width: 70%">
                <center><span class="badge" :class="'badge-' + row.badge" style="font-size: 10pt;">{{ row.status }}</span></center><br/>
                <span><b>Reserved Qty:</b> {{ row.reserved_qty_formatted }} {{ row.stock_uom }}</span><br>
                <span><b>Issued Qty:</b> {{ row.consumed_qty_formatted }} {{ row.stock_uom }}</span><br>
                <span><b>Warehouse:</b> {{ row.warehouse }}</span><br>
                <span><b>Sales Person:</b> {{ row.sales_person || '-' }}</span><br>
                <span><b>Validity:</b> {{ row.valid_until || '-' }}</span><br>
                <span><b>Created by:</b> {{ row.created_by }}</span><br>
              </td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">
                <span class="font-weight-bold">{{ row.reserved_qty_formatted }}</span>
                <small>{{ row.stock_uom }}</small>
              </td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">
                <span class="font-weight-bold">{{ row.consumed_qty_formatted }}</span>
                <small>{{ row.stock_uom }}</small>
              </td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.warehouse }}</td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">{{ row.sales_person || '-' }}</td>
              <td class="text-center align-middle text-break p-1 d-none d-sm-table-cell">{{ row.valid_until || '-' }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">
                <span class="badge" :class="'badge-' + row.badge" style="font-size: 9pt;">{{ row.status }}</span>
              </td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">{{ row.created_by }}</td>
              <td class="text-center align-middle p-1 d-none d-sm-table-cell">
                <template v-if="['Active', 'Partially Issued'].includes(row.status)">
                  <button type="button" class="btn btn-info btn-sm edit-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Edit</button>
                  <button type="button" class="btn btn-danger btn-sm cancel-stock-reservation-btn" :data-reservation-id="row.name" :disabled="!apiData.can_edit">Cancel</button>
                </template>
                <template v-else>No Actions Available</template>
              </td>
            </tr>
          </tbody>
        </table>
        <div class="box-footer clearfix" style="font-size: 16pt;">
          <nav><ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: apiData.inhouse.meta.current_page <= 1 }">
              <a class="page-link" href="#" @click.prevent="goToPage('inhouse', apiData.inhouse.meta.current_page - 1)">«</a>
            </li>
            <li v-for="p in inhousePageNumbers" :key="'i'+p" class="page-item" :class="{ active: p === apiData.inhouse.meta.current_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('inhouse', p)">{{ p }}</a>
            </li>
            <li class="page-item" :class="{ disabled: apiData.inhouse.meta.current_page >= apiData.inhouse.meta.last_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('inhouse', apiData.inhouse.meta.current_page + 1)">»</a>
            </li>
          </ul></nav>
        </div>
      </template>

      <!-- Pending to Submit Stock Entries -->
      <template v-if="apiData.pending.data.length > 0">
        <h6 class="font-weight-bold text-uppercase font-responsive"><i class="fas fa-box"></i> Pending to Submit Stock Entries</h6>
        <table class="table table-hover table-bordered table-sm stock-ledger-table-font" style="font-size: 9pt !important;">
          <thead>
            <tr>
              <th class="text-center p-1" style="width: 10%;">Transaction</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 20%;">Reference</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10%;">Issued Qty</th>
              <th class="text-center p-1 d-none d-sm-table-cell">Warehouse</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 10%;">Owner</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12%;">Issued by</th>
              <th class="text-center p-1 d-none d-sm-table-cell" style="width: 12%;">Issued at</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in apiData.pending.data" :key="row.id + row.warehouse">
              <td class="text-center p-2">
                <span class="d-block font-weight-bold">{{ formatDate(row.date) }}</span>
              </td>
              <td class="text-center p-2">{{ row.id }}</td>
              <td class="text-center p-2"><b>{{ row.qty }}</b> {{ row.uom }}</td>
              <td class="text-center p-2">{{ row.warehouse }}</td>
              <td class="text-center p-2">{{ row.owner }}</td>
              <td class="text-center p-2">{{ row.issued_by }}</td>
              <td class="text-center p-2">{{ formatDateTime(row.issued_at) }}</td>
            </tr>
          </tbody>
        </table>
        <div class="box-footer clearfix" style="font-size: 16pt;">
          <nav><ul class="pagination pagination-sm mb-0">
            <li class="page-item" :class="{ disabled: apiData.pending.meta.current_page <= 1 }">
              <a class="page-link" href="#" @click.prevent="goToPage('pending', apiData.pending.meta.current_page - 1)">«</a>
            </li>
            <li v-for="p in pendingPageNumbers" :key="'p'+p" class="page-item" :class="{ active: p === apiData.pending.meta.current_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('pending', p)">{{ p }}</a>
            </li>
            <li class="page-item" :class="{ disabled: apiData.pending.meta.current_page >= apiData.pending.meta.last_page }">
              <a class="page-link" href="#" @click.prevent="goToPage('pending', apiData.pending.meta.current_page + 1)">»</a>
            </li>
          </ul></nav>
        </div>
      </template>

      <p v-if="!hasAnyData" class="text-center text-muted p-3 mb-0">No stock reservations found.</p>
    </template>
    <p v-else-if="error" class="text-center text-muted p-3">Failed to load stock reservations.</p>
  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import axios from 'axios';

const props = defineProps({
  itemCode: { type: String, default: '' },
});

const effectiveItemCode = ref(props.itemCode);
const apiData = ref(null);
const loading = ref(true);
const error = ref(false);
const pageWeb = ref(1);
const pageConsignment = ref(1);
const pageInhouse = ref(1);
const pagePending = ref(1);

const baseUrl = '/get_stock_reservation';

function paginationNumbers(currentPage, lastPage) {
  const delta = 2;
  const range = [];
  const rangeWithDots = [];
  let l = null;
  for (let i = 1; i <= lastPage; i++) {
    if (i === 1 || i === lastPage || (i >= currentPage - delta && i <= currentPage + delta)) {
      range.push(i);
    }
  }
  for (const i of range) {
    if (l !== null && i - l !== 1) rangeWithDots.push('...');
    rangeWithDots.push(i);
    l = i;
  }
  return rangeWithDots.filter((p) => p !== '...');
}

const webPageNumbers = computed(() =>
  apiData.value?.web?.meta ? paginationNumbers(apiData.value.web.meta.current_page, apiData.value.web.meta.last_page) : []
);
const consignmentPageNumbers = computed(() =>
  apiData.value?.consignment?.meta ? paginationNumbers(apiData.value.consignment.meta.current_page, apiData.value.consignment.meta.last_page) : []
);
const inhousePageNumbers = computed(() =>
  apiData.value?.inhouse?.meta ? paginationNumbers(apiData.value.inhouse.meta.current_page, apiData.value.inhouse.meta.last_page) : []
);
const pendingPageNumbers = computed(() =>
  apiData.value?.pending?.meta ? paginationNumbers(apiData.value.pending.meta.current_page, apiData.value.pending.meta.last_page) : []
);

const hasAnyData = computed(() => {
  if (!apiData.value) return false;
  return (
    apiData.value.web.data.length > 0 ||
    apiData.value.consignment.data.length > 0 ||
    apiData.value.inhouse.data.length > 0 ||
    apiData.value.pending.data.length > 0
  );
});

function formatDate(value) {
  if (!value) return '-';
  const d = new Date(value);
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' }).replace(/,/g, '');
}

function formatDateTime(value) {
  if (!value) return '-';
  const d = new Date(value);
  return d.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric', hour: 'numeric', minute: '2-digit' }).replace(/,/g, '');
}

async function load() {
  loading.value = true;
  error.value = false;
  try {
    const { data } = await axios.get(`${baseUrl}/${effectiveItemCode.value}`, {
      headers: { Accept: 'application/json' },
      params: { tbl_1: pageWeb.value, tbl_2: pageConsignment.value, tbl_3: pageInhouse.value, page: pagePending.value },
    });
    if (data && data.web && data.consignment && data.inhouse && data.pending) {
      apiData.value = data;
    } else {
      error.value = true;
    }
  } catch (_) {
    error.value = true;
  } finally {
    loading.value = false;
  }
}

function goToPage(section, page) {
  const p = Math.max(1, parseInt(page, 10));
  if (section === 'web') pageWeb.value = p;
  else if (section === 'consignment') pageConsignment.value = p;
  else if (section === 'inhouse') pageInhouse.value = p;
  else if (section === 'pending') pagePending.value = p;
  load();
}

function onContainerClick(event) {
  const link = event.target.closest('a[href*="get_stock_reservation"]');
  if (!link || !link.href) return;
  event.preventDefault();
  const href = link.getAttribute('href') || link.href;
  const match = href.match(/get_stock_reservation\/([^/?]+)/);
  if (match) {
    pageWeb.value = 1;
    pageConsignment.value = 1;
    pageInhouse.value = 1;
    pagePending.value = 1;
    load();
  }
}

function onRefresh() {
  pageWeb.value = 1;
  pageConsignment.value = 1;
  pageInhouse.value = 1;
  pagePending.value = 1;
  load();
}

defineExpose({ refresh: onRefresh });

onMounted(() => {
  if (!effectiveItemCode.value) {
    const el = document.getElementById('item-profile-stock-reservation');
    if (el?.dataset?.itemCode) effectiveItemCode.value = el.dataset.itemCode;
  }
  load();
  window.addEventListener('item-profile-stock-reservation-refresh', onRefresh);
});

onUnmounted(() => {
  window.removeEventListener('item-profile-stock-reservation-refresh', onRefresh);
});
</script>
