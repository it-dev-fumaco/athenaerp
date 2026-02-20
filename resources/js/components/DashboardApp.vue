<template>
  <div class="dashboard-app">
    <div v-if="isDirector" class="dashboard-nav-pills">
      <a class="nav-pill active" href="/">In House Warehouse Transaction</a>
      <a class="nav-pill" href="/consignment_dashboard">Consignment Dashboard</a>
    </div>

    <div class="dashboard-stats-wrap">
      <DashboardStats />
    </div>

    <div class="dashboard-main">
      <div class="dashboard-tabs-card">
        <div class="dashboard-tabs-header">
          <ul class="dashboard-tabs">
            <li><a href="#tab_1-1" :class="{ active: activeTab === 'stock-alert' }" @click.prevent="activeTab = 'stock-alert'">
              <i class="fas fa-exclamation-triangle"></i> ▲ Stock Level Alert
            </a></li>
            <li><a href="#tab_2-1" :class="{ active: activeTab === 'movement' }" @click.prevent="activeTab = 'movement'">
              <i class="fas fa-list-alt"></i> Stock Movement
            </a></li>
            <li><a href="#tab_3-1" :class="{ active: activeTab === 'recent' }" @click.prevent="activeTab = 'recent'">
              <i class="fas fa-list-alt"></i> Recently Received Item(s)*
            </a></li>
          </ul>
          <a v-if="showItemCostLink" href="/search_item_cost" class="btn-register-cost">Register Item Cost</a>
        </div>
        <div class="dashboard-tabs-content">
          <div v-show="activeTab === 'stock-alert'" class="tab-pane">
            <DashboardLowStock />
          </div>
          <div v-show="activeTab === 'movement'" class="tab-pane">
            <DashboardAthenaLogs />
          </div>
          <div v-show="activeTab === 'recent'" class="tab-pane overflow-auto">
            <DashboardRecentlyReceived />
          </div>
        </div>
      </div>
      <div class="dashboard-sidebar">
        <div class="dashboard-widget card-glass">
          <div class="dashboard-widget-header">
            <h3 class="dashboard-widget-title">Inventory Accuracy</h3>
          </div>
          <div class="dashboard-widget-body">
            <DashboardInventoryAccuracy :initial-month="initialMonth" :initial-year="initialYear" />
          </div>
        </div>
        <div class="dashboard-widget card-glass">
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
</template>

<script setup>
import { ref, computed } from 'vue';
import DashboardStats from '@/components/DashboardStats.vue';
import DashboardLowStock from '@/components/DashboardLowStock.vue';
import DashboardAthenaLogs from '@/components/DashboardAthenaLogs.vue';
import DashboardRecentlyReceived from '@/components/DashboardRecentlyReceived.vue';
import DashboardInventoryAccuracy from '@/components/DashboardInventoryAccuracy.vue';
import DashboardReservedItems from '@/components/DashboardReservedItems.vue';

const props = defineProps({
  userGroup: { type: String, default: '' },
});

const activeTab = ref('stock-alert');
const now = new Date();
const initialMonth = now.getMonth() + 1;
const initialYear = now.getFullYear();

const isDirector = computed(() => props.userGroup === 'Director');
const showItemCostLink = computed(() => ['Manager', 'Director'].includes(props.userGroup));
</script>

<style scoped>
.dashboard-app {
  padding: 0;
  min-height: 900px;
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
.dashboard-main {
  display: grid;
  grid-template-columns: 1fr 380px;
  gap: 1.25rem;
  align-items: start;
}
@media (max-width: 1200px) {
  .dashboard-main {
    grid-template-columns: 1fr;
  }
}
.dashboard-tabs-card {
  background: rgba(255, 255, 255, 0.95);
  border-radius: 16px;
  border: 1px solid rgba(13, 58, 110, 0.1);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
  overflow: hidden;
}
.dashboard-tabs-header {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  justify-content: space-between;
  gap: 0.75rem;
  padding: 0.75rem 1rem;
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  border-bottom: 1px solid rgba(13, 58, 110, 0.08);
}
.dashboard-tabs {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
  list-style: none;
  margin: 0;
  padding: 0;
}
.dashboard-tabs a {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  padding: 0.5rem 1rem;
  border-radius: 10px;
  color: #475569;
  text-decoration: none;
  font-size: 0.9rem;
  font-weight: 500;
}
.dashboard-tabs a:hover {
  background: rgba(13, 58, 110, 0.08);
  color: #0d3a6e;
}
.dashboard-tabs a.active {
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
  color: #fff;
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
}
.dashboard-tabs-content .tab-pane {
  padding: 0.5rem;
}
.dashboard-sidebar {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}
.card-glass {
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
}

</style>
