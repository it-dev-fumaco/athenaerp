<template>
  <div class="row pt-2 m-0">
    <div class="col-md-12 col-xl-6 col-lg-12">
      <div class="container pr-0 pl-0">
        <div class="row">
          <div class="col-md-12 col-xl-10 col-lg-12 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
            <h6 class="text-uppercase text-center mb-2 font-italic">Check In Item(s)</h6>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
            <a href="/returns" class="text-dark">
              <div class="info-box bg-primary p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-undo"></i></span>
                <div class="info-box-content pt-1 pb-1">
                  <span class="info-box-text font-weight-bold text-uppercase">Sales Returns</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.pReturns }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
            <a href="/production_to_receive" class="text-dark">
              <div class="info-box bg-info p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="far fa-check-circle"></i></span>
                <div class="info-box-content pt-1 pb-1">
                  <span class="info-box-text font-weight-bold text-uppercase">Feedback</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.materialReceipt }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 offset-lg-0 offset-md-0 offset-xl-2 pr-4 pl-4">
            <a href="/material_transfer" class="text-dark">
              <div class="info-box bg-gray-dark p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-exchange-alt"></i></span>
                <div class="info-box-content pt-1 pb-1">
                  <span class="info-box-text font-weight-bold text-uppercase">Internal Transfers</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.materialTransfer }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
            <a href="/in_transit" class="text-dark">
              <div class="info-box bg-purple p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-boxes"></i></span>
                <div class="info-box-content pt-1 pb-1">
                  <span class="info-box-text font-weight-bold text-uppercase">In Transit</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.pInTransit }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
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
            <h6 class="text-uppercase text-center mb-2 font-italic">Check Out Item(s)</h6>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
            <a href="/material_transfer_for_manufacture" class="text-dark">
              <div class="info-box bg-olive p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-tasks"></i></span>
                <div class="info-box-content text-truncate d-inline-block pt-1 pb-1">
                  <span class="info-box-text font-weight-bold text-uppercase"><span class="d-md-inline-block d-lg-none d-xl-inline-block">Production</span> Withdrawals</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.materialManufacture }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
            <a href="/material_issue" class="text-dark">
              <div class="info-box bg-indigo p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-dolly"></i></span>
                <div class="info-box-content pb-1 pt-1 text-truncate d-inline-block">
                  <span class="info-box-text font-weight-bold text-uppercase">Material Issue</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.materialIssue }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
            <a href="/picking_slip" class="text-dark">
              <div class="info-box bg-navy p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-truck"></i></span>
                <div class="info-box-content pt-1 pb-1 text-truncate d-inline-block">
                  <span class="info-box-text font-weight-bold text-uppercase">Deliveries</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.pickingSlip }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
                </div>
              </div>
            </a>
          </div>
          <div class="col-md-6 col-xl-5 col-lg-6 pr-4 pl-4">
            <a href="/replacements" class="text-dark">
              <div class="info-box bg-teal p-0">
                <span class="info-box-icon" style="width: 30%; background: rgba(0,0,0,0.2);"><i class="fas fa-retweet"></i></span>
                <div class="info-box-content pt-1 pb-1">
                  <span class="info-box-text font-weight-bold text-uppercase">Order Replacement</span>
                  <div class="d-flex flex-row flex-wrap">
                    <div class="p-0 align-middle align-self-center w-100">
                      <h3 class="custom-font m-0 p-1">
                        <span class="ml-3" style="font: Arial; font-weight: 900;">{{ counts.pReplacements }}</span>
                      </h3>
                      <h5 class="mb-1">
                        <small class="pr-2 pl-2">Pending</small>
                      </h5>
                    </div>
                  </div>
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
