<template>
  <header class="phase-out-bulk-tag-header flex shrink-0 flex-nowrap items-center justify-between gap-4 border-b border-slate-200 bg-white px-6 py-4">
    <div class="flex min-w-0 flex-1 items-start gap-3">
      <span
        class="phase-out-header-icon-wrap mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-[#1976D2] ring-1 ring-sky-200/80"
        aria-hidden="true"
      >
        <svg class="phase-out-ico-md h-5 w-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
      </span>
      <h2 id="phase-out-modal-title" class="min-w-0 text-base font-bold leading-snug text-slate-800 sm:text-lg">
        Bulk Tag Inventory Items as For Phase Out
      </h2>
    </div>
    <button
      type="button"
      class="phase-out-modal-close inline-flex shrink-0 items-center justify-center rounded-lg text-slate-400 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1976D2] focus-visible:ring-offset-2"
      aria-label="Close dialog"
      @click="$emit('close')"
    >
      <svg class="phase-out-ico-md h-5 w-5" width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
      </svg>
    </button>
  </header>
</template>

<script setup>
defineEmits(['close']);
</script>

<style scoped>
.phase-out-bulk-tag-header {
  display: flex !important;
  flex-direction: row !important;
  flex-wrap: nowrap !important;
  align-items: center !important;
}
.phase-out-modal-close {
  box-sizing: border-box !important;
  width: 2.5rem !important;
  height: 2.5rem !important;
  min-width: 2.5rem !important;
  min-height: 2.5rem !important;
  max-height: 2.5rem !important;
  padding: 0 !important;
  flex-shrink: 0 !important;
}
.phase-out-header-icon-wrap {
  width: 2.5rem !important;
  height: 2.5rem !important;
  min-width: 2.5rem !important;
  min-height: 2.5rem !important;
}
.phase-out-ico-md {
  width: 1.25rem !important;
  height: 1.25rem !important;
  max-width: 1.25rem !important;
  max-height: 1.25rem !important;
  flex-shrink: 0;
}
</style>
