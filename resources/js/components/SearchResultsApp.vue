<template>
  <div class="search-results-app-wrap">
    <!-- TOTAL + view button in navbar on desktop; show here only on mobile -->
    <div class="search-results-header d-lg-none">
      <span class="search-results-total">
        TOTAL: <strong class="search-results-total-badge">{{ formattedTotal }}</strong>
      </span>
      <a href="#" class="search-results-view-btn" title="View options" aria-label="View options" @click.prevent>
        <i class="fas fa-list"></i>
      </a>
    </div>
    <div class="search-results-list-wrap">
      <SearchResultsList />
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import SearchResultsList from '@/components/SearchResultsList.vue';

const props = defineProps({
  totalItems: { type: [Number, String], default: 0 },
});

const formattedTotal = computed(() => {
  const n = Number(props.totalItems);
  return isNaN(n) ? '0' : n.toLocaleString();
});
</script>

<style scoped>
.search-results-app-wrap {
  background: rgba(255, 255, 255, 0.98);
  border-radius: 14px;
  border: 1px solid rgba(13, 58, 110, 0.1);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
  overflow: hidden;
  padding: 0.75rem 1rem 1rem;
}
.search-results-header {
  display: flex;
  justify-content: flex-end;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 0.75rem;
  padding-bottom: 0.5rem;
  border-bottom: 1px solid rgba(13, 58, 110, 0.08);
}
.search-results-total {
  font-size: 0.95rem;
  color: #475569;
  font-weight: 500;
}
.search-results-total-badge {
  display: inline-block;
  padding: 0.25rem 0.6rem;
  border-radius: 8px;
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
  color: #fff;
  font-size: 1rem;
  margin-left: 0.25rem;
}
.search-results-view-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  width: 36px;
  height: 36px;
  border-radius: 8px;
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
  color: #fff;
  text-decoration: none;
}
.search-results-view-btn:hover {
  color: #fff;
  opacity: 0.9;
}
.search-results-list-wrap {
  min-height: 200px;
}
.search-results-list-wrap :deep(.border-outline-secondary) {
  border-radius: 10px;
  border-color: rgba(13, 58, 110, 0.12) !important;
  margin-bottom: 0.5rem;
}
.search-results-list-wrap :deep(.btn-primary) {
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
  border: none;
  border-radius: 8px;
}
.search-results-list-wrap :deep(.pagination .page-link) {
  border-radius: 8px;
  color: #0d3a6e;
}
.search-results-list-wrap :deep(.pagination .page-item.active .page-link) {
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 100%);
  border-color: transparent;
}
</style>
