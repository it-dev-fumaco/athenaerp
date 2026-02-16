<template>
  <DashboardStats v-if="page === 'dashboard'" />
  <div v-else class="vue-app">
    <p class="text-muted small mb-0">Vue 3 mounted. Add page-specific components when migrating a Blade page.</p>
  </div>
</template>

<script setup>
import DashboardStats from '@/components/DashboardStats.vue';

const page = document.getElementById('app')?.dataset?.page ?? '';
</script>
