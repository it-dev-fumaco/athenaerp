<template>
  <div class="dashboard-app">
    <div v-if="isDirector" class="dashboard-nav-pills">
      <a class="nav-pill active" href="/">In House Warehouse Transaction</a>
      <a class="nav-pill" href="/consignment_dashboard">Consignment Dashboard</a>
    </div>

    <div v-if="activeTab === 'home'" class="dashboard-stats-wrap">
      <DashboardStats />
    </div>

    <div class="dashboard-main">
      <div class="dashboard-tabs-card">
        <div v-if="showItemCostLink" class="dashboard-tabs-header dashboard-tabs-header--solo">
          <span class="dashboard-tabs-header-spacer" aria-hidden="true"></span>
          <a href="/search_item_cost" class="btn-register-cost">Register Item Cost</a>
        </div>
        <div class="dashboard-tabs-content">
          <div v-show="activeTab === 'home'" class="tab-pane">
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Home</h3>
              </div>
              <div class="dashboard-widget-body">
                <p class="mb-0 text-slate-600">
                  Welcome to the inventory dashboard. Use the left sidebar to open stock alerts, movement, accuracy, reserved items, and phase-out tools.
                </p>
              </div>
            </div>
          </div>
          <div v-show="activeTab === 'stock-alert'" class="tab-pane">
            <DashboardLowStock />
          </div>
          <div v-show="activeTab === 'movement'" class="tab-pane">
            <DashboardAthenaLogs />
          </div>
          <div v-show="activeTab === 'recent'" class="tab-pane overflow-auto">
            <DashboardRecentlyReceived />
          </div>
          <div v-show="activeTab === 'inventory-accuracy'" class="tab-pane">
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Inventory Accuracy</h3>
              </div>
              <div class="dashboard-widget-body">
                <DashboardInventoryAccuracy :initial-month="initialMonth" :initial-year="initialYear" />
              </div>
            </div>
          </div>
          <div v-show="activeTab === 'reserved-items'" class="tab-pane">
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Reserved Items</h3>
              </div>
              <div class="dashboard-widget-body">
                <DashboardReservedItems />
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import DashboardStats from '@/components/DashboardStats.vue';
import DashboardLowStock from '@/components/DashboardLowStock.vue';
import DashboardAthenaLogs from '@/components/DashboardAthenaLogs.vue';
import DashboardRecentlyReceived from '@/components/DashboardRecentlyReceived.vue';
import DashboardInventoryAccuracy from '@/components/DashboardInventoryAccuracy.vue';
import DashboardReservedItems from '@/components/DashboardReservedItems.vue';

const VALID_TABS = [
  'home',
  'stock-alert',
  'movement',
  'recent',
  'inventory-accuracy',
  'reserved-items',
];

const props = defineProps({
  userGroup: { type: String, default: '' },
  initialTab: { type: String, default: 'home' },
});

function normalizeTab(tab) {
  const t = (tab || '').trim();
  return VALID_TABS.includes(t) ? t : 'home';
}

const activeTab = ref(normalizeTab(props.initialTab));
const now = new Date();
const initialMonth = now.getMonth() + 1;
const initialYear = now.getFullYear();

const isDirector = computed(() => props.userGroup === 'Director');
const showItemCostLink = computed(() => ['Manager', 'Director'].includes(props.userGroup));

function syncUrlTab(tab) {
  if (typeof window === 'undefined') {
    return;
  }
  const url = new URL(window.location.href);
  url.searchParams.set('tab', tab);
  window.history.replaceState({}, '', url);
}

onMounted(() => {
  activeTab.value = normalizeTab(props.initialTab);
  syncUrlTab(activeTab.value);
});
</script>

<style scoped>
.dashboard-app {
  padding: 0;
  min-height: 860px;
  box-sizing: border-box;
  max-width: 100%;
  min-width: 0;
}
.dashboard-nav-pills {
  display: flex;
  gap: 0.5rem;
  margin-bottom: 1rem;
}
.dashboard-nav-pills .nav-pill {
  padding: 0.5rem 1rem;
  border-radius: 8px;
  background: rgba(13, 58, 110, 0.12);
  color: #0d3a6e;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.9rem;
}
.dashboard-nav-pills .nav-pill:hover {
  background: rgba(13, 58, 110, 0.2);
  color: #0a2d52;
}
.dashboard-nav-pills .nav-pill.active {
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
  color: #fff;
}
.dashboard-stats-wrap {
  width: 100%;
  min-width: 0;
  padding: 0.75rem 1rem 1rem 1rem;
}
.dashboard-main {
  display: block;
  min-width: 0;
}
.dashboard-tabs-card {
  min-width: 0;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 16px;
  border: 1px solid rgba(13, 58, 110, 0.1);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  display: flex;
  flex-direction: column;
}
.dashboard-tabs-card .dashboard-tabs-content {
  overflow-x: auto;
  overflow-y: visible;
  -webkit-overflow-scrolling: touch;
}
.dashboard-tabs-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: flex-end;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid rgba(13, 58, 110, 0.08);
}
.dashboard-tabs-header--solo {
  justify-content: flex-end;
}
.dashboard-tabs-header-spacer {
  flex: 1;
  min-width: 0;
}
.btn-register-cost {
  padding: 0.4rem 0.9rem;
  border-radius: 8px;
  background: #64748b;
  color: #fff;
  font-size: 0.85rem;
  text-decoration: none;
}
.btn-register-cost:hover {
  background: #475569;
  color: #fff;
}
.dashboard-tabs-content {
  padding: 0;
  min-height: 400px;
  min-width: 0;
}
.dashboard-tabs-content .tab-pane {
  padding: 0.5rem;
  min-width: 0;
}
.dashboard-recent-layout {
  display: grid;
  grid-template-columns: minmax(0, 1fr) 320px;
  gap: 1.25rem;
  align-items: start;
}
@media (max-width: 1200px) {
  .dashboard-recent-layout {
    grid-template-columns: 1fr;
  }
}
.dashboard-recent-main {
  min-width: 0;
}
.dashboard-recent-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
  min-width: 0;
}
.card-glass {
  min-width: 0;
  background: rgba(255, 255, 255, 0.95);
  border-radius: 14px;
  border: 1px solid rgba(13, 58, 110, 0.1);
  box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
  overflow: hidden;
}
.dashboard-widget-header {
  padding: 0.75rem 1rem;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid rgba(13, 58, 110, 0.08);
}
.dashboard-widget-title {
  margin: 0;
  font-size: 1rem;
  font-weight: 700;
  color: #0d3a6e;
}
.dashboard-widget-body {
  padding: 0.75rem;
  overflow-x: auto;
  overflow-y: visible;
  min-width: 0;
  word-wrap: break-word;
}

</style>
