/**
 * Vite + Vue entry. Axios/CSRF via bootstrap.js.
 * Vue mounts where #app and dashboard mount points exist (incremental migration).
 */
import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import DashboardInventoryAccuracy from './components/DashboardInventoryAccuracy.vue';
import DashboardLowStock from './components/DashboardLowStock.vue';
import DashboardReservedItems from './components/DashboardReservedItems.vue';
import DashboardAthenaLogs from './components/DashboardAthenaLogs.vue';
import DashboardRecentlyReceived from './components/DashboardRecentlyReceived.vue';
import ConsignmentReplenishTable from './components/ConsignmentReplenishTable.vue';
import ConsignmentStockTransferList from './components/ConsignmentStockTransferList.vue';
import ConsignmentBeginningInventoryList from './components/ConsignmentBeginningInventoryList.vue';
import SupervisorStockTransferReport from './components/SupervisorStockTransferReport.vue';
import SupervisorInventoryAuditList from './components/SupervisorInventoryAuditList.vue';
import SalesReportList from './components/SalesReportList.vue';
import ItemProfileStockReservation from './components/ItemProfileStockReservation.vue';
import ItemProfileAthenaTransactions from './components/ItemProfileAthenaTransactions.vue';
import ItemProfileStockLedger from './components/ItemProfileStockLedger.vue';
import ItemProfilePurchaseHistory from './components/ItemProfilePurchaseHistory.vue';
import ItemProfileConsignmentStockMovement from './components/ItemProfileConsignmentStockMovement.vue';
import SearchResultsList from './components/SearchResultsList.vue';
import StockReservationModals from './components/StockReservationModals.vue';
import ItemAttributeSearch from './components/ItemAttributeSearch.vue';
import ItemAttributeUpdateForm from './components/ItemAttributeUpdateForm.vue';

const appEl = document.getElementById('app');
if (appEl) {
    createApp(App).mount('#app');
}

const invAccuracyEl = document.getElementById('dashboard-inv-accuracy');
if (invAccuracyEl) {
    createApp(DashboardInventoryAccuracy).mount('#dashboard-inv-accuracy');
}

const lowStockEl = document.getElementById('dashboard-low-stock');
if (lowStockEl) {
    createApp(DashboardLowStock).mount('#dashboard-low-stock');
}

const reservedEl = document.getElementById('dashboard-reserved-items');
if (reservedEl) {
    createApp(DashboardReservedItems).mount('#dashboard-reserved-items');
}

const athenaLogsEl = document.getElementById('dashboard-athena-logs');
if (athenaLogsEl) {
    createApp(DashboardAthenaLogs).mount('#dashboard-athena-logs');
}

const recentReceivedEl = document.getElementById('dashboard-recently-received');
if (recentReceivedEl) {
    createApp(DashboardRecentlyReceived).mount('#dashboard-recently-received');
}

const consignmentReplenishEl = document.getElementById('consignment-replenish');
if (consignmentReplenishEl) {
    createApp(ConsignmentReplenishTable).mount('#consignment-replenish');
}

const consignmentOrdersSupervisorEl = document.getElementById('consignment-orders-supervisor');
if (consignmentOrdersSupervisorEl) {
    createApp(ConsignmentReplenishTable).mount('#consignment-orders-supervisor');
}

const consignmentStockTransferListEl = document.getElementById('consignment-stock-transfer-list');
if (consignmentStockTransferListEl) {
    createApp(ConsignmentStockTransferList).mount('#consignment-stock-transfer-list');
}

const consignmentBeginningInvEl = document.getElementById('consignment-beginning-inventory-list');
if (consignmentBeginningInvEl) {
    createApp(ConsignmentBeginningInventoryList).mount('#consignment-beginning-inventory-list');
}

const supervisorStockTransferReportEl = document.getElementById('supervisor-stock-transfer-report');
if (supervisorStockTransferReportEl) {
    createApp(SupervisorStockTransferReport).mount('#supervisor-stock-transfer-report');
}

const supervisorInventoryAuditEl = document.getElementById('supervisor-inventory-audit-list');
if (supervisorInventoryAuditEl) {
    createApp(SupervisorInventoryAuditList).mount('#supervisor-inventory-audit-list');
}

const salesReportListEl = document.getElementById('sales-report-list');
if (salesReportListEl) {
    createApp(SalesReportList).mount('#sales-report-list');
}

const itemProfileStockReservationEl = document.getElementById('item-profile-stock-reservation');
if (itemProfileStockReservationEl) {
    createApp(ItemProfileStockReservation, {
        itemCode: itemProfileStockReservationEl.dataset.itemCode || '',
    }).mount('#item-profile-stock-reservation');
}

const itemProfileAthenaTransactionsEl = document.getElementById('item-profile-athena-transactions');
if (itemProfileAthenaTransactionsEl) {
    createApp(ItemProfileAthenaTransactions, {
        itemCode: itemProfileAthenaTransactionsEl.dataset.itemCode || '',
    }).mount('#item-profile-athena-transactions');
}

const itemProfileStockLedgerEl = document.getElementById('item-profile-stock-ledger');
if (itemProfileStockLedgerEl) {
    createApp(ItemProfileStockLedger, {
        itemCode: itemProfileStockLedgerEl.dataset.itemCode || '',
    }).mount('#item-profile-stock-ledger');
}

const itemProfilePurchaseHistoryEl = document.getElementById('item-profile-purchase-history');
if (itemProfilePurchaseHistoryEl) {
    createApp(ItemProfilePurchaseHistory, {
        itemCode: itemProfilePurchaseHistoryEl.dataset.itemCode || '',
    }).mount('#item-profile-purchase-history');
}

const itemProfileConsignmentStockMovementEl = document.getElementById('item-profile-consignment-stock-movement');
if (itemProfileConsignmentStockMovementEl) {
    createApp(ItemProfileConsignmentStockMovement, {
        itemCode: itemProfileConsignmentStockMovementEl.dataset.itemCode || '',
    }).mount('#item-profile-consignment-stock-movement');
}

const searchResultsAppEl = document.getElementById('search-results-app');
if (searchResultsAppEl) {
    createApp(SearchResultsList).mount('#search-results-app');
}

const stockReservationModalsEl = document.getElementById('stock-reservation-modals-app');
if (stockReservationModalsEl) {
    createApp(StockReservationModals).mount('#stock-reservation-modals-app');
}

const itemAttributeSearchEl = document.getElementById('item-attribute-search-app');
if (itemAttributeSearchEl) {
    createApp(ItemAttributeSearch).mount('#item-attribute-search-app');
}

const itemAttributeUpdateFormEl = document.getElementById('item-attribute-update-form-app');
if (itemAttributeUpdateFormEl) {
    createApp(ItemAttributeUpdateForm).mount('#item-attribute-update-form-app');
}
