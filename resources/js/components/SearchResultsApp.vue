<template>
  <div class="search-results-app-wrap">
    <div class="search-results-list-wrap">
      <SearchResultsList />
    </div>
  </div>
</template>

<script setup>
import SearchResultsList from '@/components/SearchResultsList.vue';
</script>

<style scoped>
.search-results-app-wrap {
  background: rgba(255, 255, 255, 0.98);
  border-radius: 14px;
  border: 1px solid rgba(13, 58, 110, 0.1);
  box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
  overflow-x: auto;
  overflow-y: visible;
  min-width: 0;
  width: 100%;
  padding: 0.75rem 1rem 1rem;
}
.search-results-list-wrap {
  min-height: 200px;
  min-width: 0;
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
