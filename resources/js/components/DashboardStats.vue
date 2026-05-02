<template>
  <div class="dashboard-stats">
    <section class="dashboard-stats__section">
      <h6 class="dashboard-stats__section-title">Check In Item(s)</h6>
      <div class="dashboard-stats__grid">
        <a href="/returns" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--blue">
            <div class="dashboard-metric-card__icon"><i class="fas fa-undo"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Sales Returns</span>
              <span class="dashboard-metric-card__number">{{ counts.pReturns }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
        <a href="/production_to_receive" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--yellow">
            <div class="dashboard-metric-card__icon"><i class="far fa-check-circle"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Feedback</span>
              <span class="dashboard-metric-card__number">{{ counts.materialReceipt }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
        <a href="/material_transfer" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--charcoal">
            <div class="dashboard-metric-card__icon"><i class="fas fa-exchange-alt"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Internal Transfers</span>
              <span class="dashboard-metric-card__number">{{ counts.materialTransfer }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
        <a href="/in_transit" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--purple">
            <div class="dashboard-metric-card__icon"><i class="fas fa-boxes"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">In Transit</span>
              <span class="dashboard-metric-card__number">{{ counts.pInTransit }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
      </div>
    </section>
    <section class="dashboard-stats__section">
      <h6 class="dashboard-stats__section-title">Check Out Item(s)</h6>
      <div class="dashboard-stats__grid">
        <a href="/material_transfer_for_manufacture" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--teal">
            <div class="dashboard-metric-card__icon"><i class="fas fa-tasks"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Production Withdrawals</span>
              <span class="dashboard-metric-card__number">{{ counts.materialManufacture }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
        <a href="/material_issue" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--indigo">
            <div class="dashboard-metric-card__icon"><i class="fas fa-dolly"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Material Issue</span>
              <span class="dashboard-metric-card__number">{{ counts.materialIssue }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
        <a href="/picking_slip" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--navy">
            <div class="dashboard-metric-card__icon"><i class="fas fa-truck"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Deliveries</span>
              <span class="dashboard-metric-card__number">{{ counts.pickingSlip }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
        <a href="/replacements" class="dashboard-metric-card-link">
          <div class="dashboard-metric-card dashboard-metric-card--grey">
            <div class="dashboard-metric-card__icon"><i class="fas fa-retweet"></i></div>
            <div class="dashboard-metric-card__content">
              <span class="dashboard-metric-card__title">Order Replacement</span>
              <span class="dashboard-metric-card__number">{{ counts.pReplacements }}</span>
              <span class="dashboard-metric-card__status">Pending</span>
            </div>
          </div>
        </a>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const counts = ref({
  pReturns: '-',
  materialReceipt: '-',
  materialTransfer: '-',
  pInTransit: '-',
  materialManufacture: '-',
  materialIssue: '-',
  pickingSlip: '-',
  pReplacements: '-',
});

async function loadCounts() {
  try {
    const [dashboardData, ...rest] = await Promise.all([
      axios.get('/dashboard_data'),
      axios.get('/count_ste_for_issue/Material%20Issue'),
      axios.get('/count_ste_for_issue/Material%20Transfer'),
      axios.get('/count_ste_for_issue/Material%20Receipt'),
      axios.get('/count_ste_for_issue/Material%20Transfer%20for%20Manufacture'),
      axios.get('/count_production_to_receive'),
      axios.get('/count_deliveries'),
    ]);

    const issue = rest[0];
    const transfer = rest[1];
    const receipt = rest[2];
    const manufacture = rest[3];
    const productionReceive = rest[4];
    const deliveryCount = rest[5];

    counts.value = {
      pReturns: receipt.data ?? '-',
      materialReceipt: productionReceive.data ?? '-',
      materialTransfer: transfer.data ?? '-',
      pInTransit: dashboardData.data?.goods_in_transit ?? '-',
      materialManufacture: manufacture.data ?? '-',
      materialIssue: issue.data ?? '-',
      pickingSlip: deliveryCount.data ?? '-',
      pReplacements: dashboardData.data?.p_replacements ?? '-',
    };
  } catch (_) {
    // Keep placeholder values on error
  }
}

onMounted(loadCounts);
</script>

<style scoped>
.dashboard-stats {
  width: 100%;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin: 0;
  padding: 0;
}
.dashboard-stats__section {
  min-width: 0;
}
.dashboard-stats__section-title {
  text-transform: uppercase;
  text-align: center;
  margin: 0 0 0.5rem 0;
  font-style: italic;
  font-weight: 600;
  color: #374151;
  font-size: 0.9rem;
}
.dashboard-stats__grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 0.75rem;
}
.dashboard-stats__grid .dashboard-metric-card-link {
  min-width: 0;
}
/* Tablets / narrow inventory main: stack sections and cards (sidebar still visible above 768) */
@media (max-width: 1024px) {
  .dashboard-stats {
    grid-template-columns: 1fr;
  }
  .dashboard-stats__grid {
    grid-template-columns: 1fr;
  }
}
@media (max-width: 768px) {
  .dashboard-metric-card__icon {
    width: 3.5rem;
    min-width: 3.5rem;
    max-width: 3.5rem;
  }
  .dashboard-metric-card__content {
    padding: 0.55rem 0.65rem;
  }
  .dashboard-metric-card__number {
    font-size: clamp(1.25rem, 6vw, 1.6rem);
  }
}
.dashboard-metric-card-link {
  text-decoration: none;
  color: inherit;
  display: block;
}
.dashboard-metric-card-link:hover {
  opacity: 0.96;
}
.dashboard-metric-card {
  display: flex;
  align-items: stretch;
  min-height: 100px;
  border-radius: 14px;
  overflow: hidden;
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
}
.dashboard-metric-card__icon {
  width: 26%;
  min-width: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  flex-shrink: 0;
}
.dashboard-metric-card__icon i {
  font-size: 1.75rem;
  color: #fff;
}
.dashboard-metric-card__content {
  flex: 1;
  padding: 0.6rem 0.85rem;
  display: flex;
  flex-direction: column;
  justify-content: center;
  position: relative;
  min-width: 0;
}
.dashboard-metric-card__content::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image: radial-gradient(circle at 1px 1px, rgba(255, 255, 255, 0.18) 1px, transparent 0);
  background-size: 12px 12px;
  pointer-events: none;
}
.dashboard-metric-card__title {
  display: block;
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #fff;
  letter-spacing: 0.02em;
  margin-bottom: 0.15rem;
  white-space: normal;
  overflow-wrap: break-word;
  word-break: normal;
}
.dashboard-metric-card__number {
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  line-height: 1.2;
  font-family: Arial, sans-serif;
  min-width: 0;
}
.dashboard-metric-card__status {
  display: block;
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.95);
  margin-top: 0.1rem;
  white-space: normal;
  overflow-wrap: break-word;
  word-break: normal;
}

/* Solid left strip + gradient right with pattern */
.dashboard-metric-card--blue .dashboard-metric-card__icon { background: #1e40af; }
.dashboard-metric-card--blue .dashboard-metric-card__content {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 50%, #1e40af 100%);
}

.dashboard-metric-card--yellow .dashboard-metric-card__icon { background: #b45309; }
.dashboard-metric-card--yellow .dashboard-metric-card__content {
  background: linear-gradient(135deg, #d97706 0%, #b45309 50%, #92400e 100%);
}

.dashboard-metric-card--charcoal .dashboard-metric-card__icon { background: #374151; }
.dashboard-metric-card--charcoal .dashboard-metric-card__content {
  background: linear-gradient(135deg, #4b5563 0%, #374151 50%, #1f2937 100%);
}

.dashboard-metric-card--purple .dashboard-metric-card__icon { background: #6b21a8; }
.dashboard-metric-card--purple .dashboard-metric-card__content {
  background: linear-gradient(135deg, #7c3aed 0%, #6b21a8 50%, #581c87 100%);
}

.dashboard-metric-card--teal .dashboard-metric-card__icon { background: #0f766e; }
.dashboard-metric-card--teal .dashboard-metric-card__content {
  background: linear-gradient(135deg, #0d9488 0%, #0f766e 50%, #115e59 100%);
}

.dashboard-metric-card--indigo .dashboard-metric-card__icon { background: #4338ca; }
.dashboard-metric-card--indigo .dashboard-metric-card__content {
  background: linear-gradient(135deg, #4f46e5 0%, #4338ca 50%, #3730a3 100%);
}

.dashboard-metric-card--navy .dashboard-metric-card__icon { background: #1e3a5f; }
.dashboard-metric-card--navy .dashboard-metric-card__content {
  background: linear-gradient(135deg, #1e40af 0%, #1e3a5f 50%, #172554 100%);
}

.dashboard-metric-card--grey .dashboard-metric-card__icon { background: #4b5563; }
.dashboard-metric-card--grey .dashboard-metric-card__content {
  background: linear-gradient(135deg, #6b7280 0%, #4b5563 50%, #374151 100%);
}
</style>
