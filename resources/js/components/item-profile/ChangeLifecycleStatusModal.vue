<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="cls-overlay"
      role="presentation"
      @click.self="onCancel?.()"
    >
      <div
        class="cls-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cls-title"
        @click.stop
      >
        <header class="cls-header">
          <h2 id="cls-title" class="cls-title">Change Lifecycle Status</h2>
          <button
            type="button"
            class="cls-close"
            aria-label="Close"
            @click="onCancel?.()"
          >
            <i class="fas fa-times" aria-hidden="true" />
          </button>
        </header>

        <div class="cls-body">
          <div class="cls-muted">Item Code: {{ itemCode }}</div>

          <div class="cls-item-row">
            <span class="cls-tag" :title="itemTag">{{ itemTag }}</span>
            <div class="cls-item-name" :title="itemName">{{ itemName }}</div>
          </div>

          <div class="cls-summary-row">
            <i class="fas fa-boxes cls-icon" aria-hidden="true" />
            <span>Current Stock, {{ formatQty(currentStock) }} set(s)</span>
          </div>
          <div class="cls-summary-row">
            <i class="far fa-calendar-alt cls-icon" aria-hidden="true" />
            <span>Last Movement: {{ lastMovement }}</span>
          </div>
          <div class="cls-summary-row">
            <i class="far fa-file-alt cls-icon" aria-hidden="true" />
            <span>Last Purchase: {{ lastPurchase }}</span>
          </div>

          <div class="cls-field">
            <label class="cls-label">New Status:</label>
            <select v-model="draftStatus" class="cls-input" :disabled="submitting">
              <option v-for="s in statusOptions" :key="s" :value="s">{{ s }}</option>
            </select>
          </div>

          <div class="cls-field">
            <label class="cls-label">Reason for Change:</label>
            <input
              v-model="reason"
              type="text"
              class="cls-input"
              placeholder="Enter reason (required)"
              :disabled="submitting"
            >
          </div>
        </div>

        <footer class="cls-footer">
          <button type="button" class="cls-btn cls-btn-cancel" @click="onCancel?.()">
            Cancel
          </button>
          <button
            type="button"
            class="cls-btn cls-btn-confirm"
            :disabled="submitting || !canConfirm"
            @click="confirm"
          >
            Confirm Update
          </button>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
import { computed, ref, watch } from 'vue';

const props = defineProps({
  itemCode: { type: String, required: true },
  itemName: { type: String, required: true },
  itemTag: { type: String, required: true },
  currentStock: { type: Number, required: true },
  lastMovement: { type: String, required: true },
  lastPurchase: { type: String, required: true },
  currentStatus: { type: String, required: true },
  statusOptions: { type: Array, required: true },
  onCancel: { type: Function, required: true },
  onConfirm: { type: Function, required: true }, // (newStatus, reason) => void|Promise
  isOpen: { type: Boolean, required: true },
  submitting: { type: Boolean, default: false },
});

const draftStatus = ref(props.currentStatus);
const reason = ref('');

watch(
  () => props.isOpen,
  (open) => {
    if (open) {
      draftStatus.value = props.currentStatus;
      reason.value = '';
    }
  }
);

const canConfirm = computed(() => reason.value.trim().length > 0);

function formatQty(n) {
  const x = Number(n);
  return Number.isFinite(x) ? x.toLocaleString(undefined, { maximumFractionDigits: 2 }) : '0';
}

async function confirm() {
  if (!canConfirm.value || props.submitting) {
    return;
  }
  await props.onConfirm?.(draftStatus.value, reason.value.trim());
}
</script>

<style scoped>
.cls-overlay {
  position: fixed;
  inset: 0;
  z-index: 2147483646;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 24px 16px;
  background: rgba(0, 0, 0, 0.55);
}

.cls-panel {
  width: 100%;
  max-width: 400px;
  background: #ffffff;
  border-radius: 8px;
  box-shadow: 0 24px 64px rgba(0, 0, 0, 0.18);
  overflow: hidden;
  font-family: ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
}

.cls-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 14px 16px;
  border-bottom: 1px solid #e5e7eb;
}

.cls-title {
  margin: 0;
  font-size: 18px;
  font-weight: 700;
  color: #111827;
}

.cls-close {
  border: none;
  background: transparent;
  color: #6b7280;
  width: 32px;
  height: 32px;
  border-radius: 6px;
  cursor: pointer;
}

.cls-close:hover {
  background: rgba(17, 24, 39, 0.06);
  color: #111827;
}

.cls-body {
  padding: 14px 16px 8px;
}

.cls-muted {
  color: #6b7280;
  font-size: 12px;
}

.cls-item-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
}

.cls-tag {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  height: 22px;
  padding: 0 8px;
  border-radius: 9999px;
  background: #e0f2fe;
  color: #0369a1;
  font-size: 12px;
  font-weight: 700;
  white-space: nowrap;
}

.cls-item-name {
  font-weight: 700;
  color: #111827;
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
}

.cls-summary-row {
  display: flex;
  align-items: center;
  gap: 10px;
  margin-top: 10px;
  color: #374151;
  font-size: 13px;
}

.cls-icon {
  width: 16px;
  text-align: center;
  color: #6b7280;
}

.cls-field {
  margin-top: 14px;
}

.cls-label {
  display: block;
  font-weight: 700;
  font-size: 12px;
  color: #374151;
  margin-bottom: 6px;
}

.cls-input {
  width: 100%;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  padding: 9px 10px;
  font-size: 13px;
  outline: none;
}

.cls-input:focus {
  border-color: #2563eb;
  box-shadow: 0 0 0 1px #2563eb;
}

.cls-footer {
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  padding: 12px 16px 16px;
}

.cls-btn {
  border: none;
  cursor: pointer;
  border-radius: 8px;
  padding: 9px 12px;
  font-size: 13px;
  font-weight: 700;
}

.cls-btn-cancel {
  background: transparent;
  color: #6b7280;
}

.cls-btn-cancel:hover {
  color: #374151;
}

.cls-btn-confirm {
  background: #2563eb;
  color: #ffffff;
}

.cls-btn-confirm:disabled {
  opacity: 0.55;
  cursor: not-allowed;
}
</style>

