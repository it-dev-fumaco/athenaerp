<template>
  <span
    v-if="resolved"
    class="lifecycle-status-tag"
    :style="{ backgroundColor: resolved.backgroundColor }"
    :title="resolved.label"
  >{{ resolved.label }}</span>
</template>

<script setup>
import { computed } from 'vue';
import { resolveLifecycleStatusDisplay } from '@/config/lifecycleStatus';

const props = defineProps({
  /** ERP lifecycle status string, or null/empty to hide */
  status: {
    validator: (v) => v == null || typeof v === 'string',
    default: null,
  },
});

const resolved = computed(() => resolveLifecycleStatusDisplay(props.status));
</script>

<style scoped>
.lifecycle-status-tag {
  display: inline-block;
  max-width: 100%;
  padding: 2px 6px;
  border-radius: 4px;
  color: #fff;
  font-weight: 700;
  font-size: 11px;
  line-height: 1.25;
  letter-spacing: 0.02em;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
  pointer-events: none;
}
</style>
