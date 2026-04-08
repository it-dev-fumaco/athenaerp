<template>
  <div
    class="ilsc-card"
    :style="{ backgroundColor: resolved.color }"
    role="status"
    :aria-label="`Current lifecycle status: ${currentStatus}`"
  >
    <div class="ilsc-left">
      <i class="ilsc-icon" :class="resolved.iconClass" aria-hidden="true" />
      <span class="ilsc-status">{{ currentStatus }}</span>
    </div>

    <div class="ilsc-right">
      <div class="ilsc-meta-label">{{ lastUpdatedLabel }}</div>
      <div class="ilsc-meta-detail">{{ lastUpdatedDetail }}</div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
  currentStatus: { type: String, required: true },
  lastUpdatedLabel: { type: String, required: true },
  lastUpdatedDetail: { type: String, required: true },
  onChangeStatus: { type: Function, default: null },
});

const STATUS_CONFIG = {
  Active: { color: '#22C55E', iconClass: 'fas fa-shield-halved' },
  'For Phase Out': { color: '#F59E0B', iconClass: 'far fa-clock' },
  Discontinued: { color: '#6B7280', iconClass: 'far fa-times-circle' },
  Obsolete: { color: '#4B5563', iconClass: 'far fa-minus-square' },
};

const resolved = computed(() => {
  const key = (props.currentStatus || '').trim();
  return (
    STATUS_CONFIG[key] || {
      color: '#6B7280',
      iconClass: 'fas fa-circle-info',
    }
  );
});
</script>

<style scoped>
.ilsc-card {
  width: 100%;
  border-radius: 8px;
  padding: 10px 12px;
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  color: #ffffff;
  box-sizing: border-box;
}

.ilsc-left {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.ilsc-icon {
  width: 18px;
  text-align: center;
}

.ilsc-status {
  font-weight: 800;
  font-size: 14px;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ilsc-right {
  text-align: right;
  min-width: 0;
}

.ilsc-meta-label {
  font-size: 11px;
  font-weight: 700;
  line-height: 1.2;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.ilsc-meta-detail {
  margin-top: 2px;
  font-size: 11px;
  line-height: 1.2;
  color: rgba(255, 255, 255, 0.82);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
</style>

