<template>
  <div class="ipls-wrap">
    <ItemLifecycleStatusCard
      class="ipls-status-card"
      :current-status="currentStatus"
      :last-updated-label="lastUpdatedLabel"
      :last-updated-detail="lastUpdatedDetail"
      :on-change-status="onChangeStatus"
    />

    <a
      class="ipls-change-btn"
      href="#"
      @click.prevent="open = true"
    >
      <i class="fas fa-pen-to-square" aria-hidden="true"></i>
      <span>Change Status</span>
    </a>

    <ChangeLifecycleStatusModal
      :is-open="open"
      :item-code="itemCode"
      :item-name="itemName"
      :item-tag="itemTag"
      :current-stock="currentStock"
      :last-movement="lastMovement"
      :last-purchase="lastPurchase"
      :current-status="currentStatus"
      :status-options="statusOptions"
      :submitting="submitting"
      :on-cancel="onCancel"
      :on-confirm="onConfirm"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import ChangeLifecycleStatusModal from '@/components/item-profile/ChangeLifecycleStatusModal.vue';
import ItemLifecycleStatusCard from '@/components/item-profile/ItemLifecycleStatusCard.vue';

const props = defineProps({
  itemCode: { type: String, required: true },
  itemName: { type: String, required: true },
  itemTag: { type: String, required: true },
  currentStock: { type: Number, required: true },
  lastMovement: { type: String, required: true },
  lastPurchase: { type: String, required: true },
  currentStatus: { type: String, required: true },
  lastUpdatedLabel: { type: String, required: true },
  lastUpdatedDetail: { type: String, required: true },
  statusOptions: { type: Array, required: true },
});

const open = ref(false);
const submitting = ref(false);
const currentStatus = ref(props.currentStatus);

function onChangeStatus() {
  open.value = true;
}

function notify(type, message) {
  if (window?.$?.notify) {
    window.$.notify({ message }, { type, timer: 500, z_index: 1060, placement: { from: 'top', align: 'center' } });
    return;
  }
  window.alert(message);
}

function onCancel() {
  open.value = false;
}

async function onConfirm(newStatus, reason) {
  submitting.value = true;
  try {
    const url = `/items/${encodeURIComponent(props.itemCode)}/lifecycle-status`;
    const { data } = await axios.post(url, { newStatus, reason });
    currentStatus.value = data?.status ?? newStatus;
    notify('success', 'Lifecycle status updated.');
    open.value = false;
  } catch (e) {
    notify('danger', 'Update failed. Please try again.');
  } finally {
    submitting.value = false;
  }
}
</script>

<style scoped>
.ipls-wrap {
  width: 100%;
}

.ipls-status-card {
  margin: 10px 8px 8px;
}

.ipls-change-btn {
  width: calc(100% - 16px);
  margin: 0 8px 12px;
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  background: #ffffff;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  padding: 10px 12px;
  color: #111827;
  font-size: 12px;
  font-weight: 700;
  text-decoration: none;
  cursor: pointer;
  user-select: none;
}

.ipls-change-btn:hover {
  background: #f9fafb;
}

.ipls-change-btn:focus-visible {
  outline: none;
  box-shadow: 0 0 0 2px #ffffff, 0 0 0 4px rgba(37, 99, 235, 0.55);
}
</style>

