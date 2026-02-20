<template>
  <div class="dashboard-stats row pt-2 m-0">
    <div class="col-md-12 col-xl-6 col-lg-12">
      <div class="container pr-0 pl-0">
        <div class="row">
          <div class="col-md-12 col-xl-10 col-lg-12 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
            <h6 class="dashboard-stats__section-title">Check In Item(s)</h6>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4 mb-3">
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
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4 mb-3">
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
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4 mb-3">
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
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4 mb-3">
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
        </div>
      </div>
    </div>
    <div class="col-md-12 col-xl-6 col-lg-12">
      <div class="container pr-0 pl-0">
        <div class="row">
          <div class="col-md-12 col-xl-10 col-lg-12 pr-4 pl-4">
            <h6 class="dashboard-stats__section-title">Check Out Item(s)</h6>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4 mb-3">
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
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4 mb-3">
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
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4 mb-3">
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
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4 mb-3">
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
        </div>
      </div>
    </div>
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
      axios.get('/count_ps_for_issue'),
    ]);

    const issue = rest[0];
    const transfer = rest[1];
    const receipt = rest[2];
    const manufacture = rest[3];
    const productionReceive = rest[4];
    const psIssue = rest[5];

    counts.value = {
      pReturns: receipt.data ?? '-',
      materialReceipt: productionReceive.data ?? '-',
      materialTransfer: transfer.data ?? '-',
      pInTransit: dashboardData.data?.goods_in_transit ?? '-',
      materialManufacture: manufacture.data ?? '-',
      materialIssue: issue.data ?? '-',
      pickingSlip: psIssue.data ?? '-',
      pReplacements: dashboardData.data?.p_replacements ?? '-',
    };
  } catch (_) {
    // Keep placeholder values on error
  }
}

onMounted(loadCounts);
</script>

<style scoped>
.dashboard-stats__section-title {
  text-transform: uppercase;
  text-align: center;
  margin-bottom: 0.5rem;
  font-style: italic;
  font-weight: 600;
  color: #374151;
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
  font-size: 0.7rem;
  font-weight: 700;
  text-transform: uppercase;
  color: #fff;
  letter-spacing: 0.02em;
  margin-bottom: 0.15rem;
}
.dashboard-metric-card__number {
  font-size: 1.6rem;
  font-weight: 900;
  color: #fff;
  line-height: 1.2;
  font-family: Arial, sans-serif;
}
.dashboard-metric-card__status {
  font-size: 0.75rem;
  color: rgba(255, 255, 255, 0.95);
  margin-top: 0.1rem;
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
