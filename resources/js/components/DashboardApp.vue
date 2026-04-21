<template>
  <div class="dashboard-app">
    <div v-if="isDirector" class="dashboard-nav-pills">
      <a class="nav-pill active" href="/">In House Warehouse Transaction</a>
      <a class="nav-pill" href="/consignment_dashboard">Consignment Dashboard</a>
    </div>

    <div
      class="dashboard-stats-wrap"
      :style="{ display: activeTab === 'home' ? 'block' : 'none' }"
    >
      <DashboardStats />
    </div>

    <div class="dashboard-main">
      <div class="dashboard-tabs-card">
        <div v-if="showItemCostLink" class="dashboard-tabs-header dashboard-tabs-header--solo">
          <span class="dashboard-tabs-header-spacer" aria-hidden="true"></span>
          <a href="/search_item_cost" class="btn-register-cost">Register Item Cost</a>
        </div>
        <div class="dashboard-tabs-content">
          <div class="tab-pane" :style="{ display: activeTab === 'home' ? 'block' : 'none' }">
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Stock Level Alerts</h3>
              </div>
              <div class="dashboard-widget-body">
                <DashboardLowStock />
              </div>
            </div>
          </div>
          <div v-show="activeTab === 'movement'" class="tab-pane">
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Stock Movement</h3>
              </div>
              <div class="dashboard-widget-body">
                <DashboardAthenaLogs />
              </div>
            </div>
          </div>
          <div v-show="activeTab === 'recent'" class="tab-pane">
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Recently Received Items</h3>
              </div>
              <div class="dashboard-widget-body">
                <DashboardRecentlyReceived />
              </div>
            </div>
          </div>
          <div
            class="tab-pane"
            :style="{ display: activeTab === 'inventory-accuracy' ? 'block' : 'none' }"
          >
            <div class="card-glass">
              <div class="dashboard-widget-header">
                <h3 class="dashboard-widget-title">Inventory Accuracy</h3>
              </div>
              <div class="dashboard-widget-body">
                <DashboardInventoryAccuracy :initial-month="initialMonth" :initial-year="initialYear" />
              </div>
            </div>
          </div>
          <div
            class="tab-pane"
            :style="{ display: activeTab === 'reserved-items' ? 'block' : 'none' }"
          >
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
import { ref, computed, onMounted, onBeforeUnmount } from 'vue';
import DashboardStats from '@/components/DashboardStats.vue';
import DashboardLowStock from '@/components/DashboardLowStock.vue';
import DashboardAthenaLogs from '@/components/DashboardAthenaLogs.vue';
import DashboardRecentlyReceived from '@/components/DashboardRecentlyReceived.vue';
import DashboardInventoryAccuracy from '@/components/DashboardInventoryAccuracy.vue';
import DashboardReservedItems from '@/components/DashboardReservedItems.vue';

const VALID_TABS = [
  'home',
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

function syncUrlTab(tab, replace = true) {
  if (typeof window === 'undefined') {
    return;
  }
  const url = new URL(window.location.href);
  url.searchParams.set('tab', tab);
  if (replace) {
    window.history.replaceState({}, '', url);
  } else {
    window.history.pushState({}, '', url);
  }
}

/** Same document path as the dashboard (supports subdirectory installs). */
const dashboardPathname = ref('');

function syncSidebarNavActive(tab) {
  if (typeof document === 'undefined') {
    return;
  }
  const nav = document.querySelector('#inventory-sidebar .inventory-sidebar-nav');
  if (!nav) {
    return;
  }
  nav.querySelectorAll('a.inventory-sidebar__link[href*="tab="]').forEach((el) => {
    let u;
    try {
      u = new URL(el.href);
    } catch {
      return;
    }
    if (u.pathname !== dashboardPathname.value) {
      return;
    }
    const linkTab = normalizeTab(u.searchParams.get('tab') || 'home');
    el.classList.toggle('inventory-sidebar__link--active', linkTab === tab);
  });
}

function onPopState() {
  if (typeof window === 'undefined') {
    return;
  }
  const url = new URL(window.location.href);
  if (url.pathname !== dashboardPathname.value) {
    return;
  }
  const tab = normalizeTab(url.searchParams.get('tab') || 'home');
  activeTab.value = tab;
  syncSidebarNavActive(tab);
}

function onDashboardTabLinkClick(e) {
  if (typeof window === 'undefined') {
    return;
  }
  const a = e.target.closest?.('a');
  if (!a || !a.href) {
    return;
  }
  if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
    return;
  }
  if (a.getAttribute('download') != null || a.getAttribute('target') === '_blank') {
    return;
  }
  let url;
  try {
    url = new URL(a.href);
  } catch {
    return;
  }
  if (url.origin !== window.location.origin) {
    return;
  }
  if (url.pathname !== dashboardPathname.value) {
    return;
  }
  if (!url.searchParams.has('tab')) {
    return;
  }
  const tab = normalizeTab(url.searchParams.get('tab') || 'home');
  e.preventDefault();
  activeTab.value = tab;
  syncUrlTab(tab, false);
  syncSidebarNavActive(tab);
}

onMounted(() => {
  dashboardPathname.value = window.location.pathname;
  activeTab.value = normalizeTab(props.initialTab);
  syncUrlTab(activeTab.value);
  syncSidebarNavActive(activeTab.value);
  document.addEventListener('click', onDashboardTabLinkClick, true);
  window.addEventListener('popstate', onPopState);
});

onBeforeUnmount(() => {
  document.removeEventListener('click', onDashboardTabLinkClick, true);
  window.removeEventListener('popstate', onPopState);
});
</script>

<style scoped>
.dashboard-app {
  padding: 0;
  min-height: 100dvh;
  box-sizing: border-box;
  max-width: 100%;
  min-width: 0;
  display: flex;
  flex-direction: column;
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
  display: flex;
  flex: 1 1 auto;
  min-width: 0;
  min-height: 0;
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
  flex: 1 1 auto;
  min-height: 0;
}
.dashboard-tabs-card .dashboard-tabs-content {
  overflow-x: auto;
  overflow-y: hidden;
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
  display: flex;
  flex-direction: column;
  flex: 1 1 auto;
  min-height: 0;
}
.dashboard-tabs-content .tab-pane {
  padding: 0.5rem;
  min-width: 0;
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
}
.dashboard-tabs-content .tab-pane > .card-glass {
  flex: 1 1 auto;
  min-height: 0;
  display: flex;
  flex-direction: column;
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
  overflow-y: auto;
  min-width: 0;
  word-wrap: break-word;
  flex: 1 1 auto;
  min-height: 0;
}

</style>
