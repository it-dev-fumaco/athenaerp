<template>
  <footer class="flex shrink-0 flex-col gap-3 border-t border-slate-200 bg-white px-6 py-4 sm:flex-row sm:items-center sm:justify-between sm:gap-4">
    <button
      type="button"
      class="min-h-10 w-full rounded-lg border border-slate-300 bg-slate-100 px-6 text-sm font-semibold text-slate-800 shadow-sm transition hover:bg-slate-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-slate-400 sm:w-auto"
      @click="$emit('cancel')"
    >
      Cancel
    </button>
    <button
      type="button"
      class="phase-out-tag-submit min-h-10 w-full rounded-lg px-6 text-sm font-semibold shadow-md transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed sm:w-auto"
      :class="'bg-[#D32F2F] text-white hover:bg-[#C62828] focus-visible:ring-[#D32F2F]'"
      :disabled="disabled || tagging"
      :aria-label="ariaLabel"
      @click="$emit('submit')"
    >
      Tag {{ selectedCount }} Items as For Phase Out
    </button>
  </footer>
</template>

<script setup>
defineProps({
  selectedCount: { type: Number, required: true },
  disabled: { type: Boolean, default: false },
  tagging: { type: Boolean, default: false },
  ariaLabel: { type: String, default: '' },
});

defineEmits(['cancel', 'submit']);
</script>
