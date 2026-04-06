<template>
  <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 bg-slate-100 px-4 py-3 sm:px-5">
    <span class="text-sm font-semibold text-slate-800">{{ selectedCount }} Items selected</span>
    <div class="flex items-center gap-2">
      <span class="hidden text-xs text-slate-600 sm:inline">Adjust Suggested Discounts</span>
      <button
        type="button"
        role="switch"
        :aria-checked="enabled"
        class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1976D2] focus-visible:ring-offset-2"
        :class="enabled ? 'bg-[#1976D2]' : 'bg-slate-300'"
        @click="toggle"
      >
        <span
          class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow transition"
          :class="enabled ? 'translate-x-5' : 'translate-x-0.5'"
        />
      </button>
    </div>
  </div>
</template>

<script setup>
defineProps({
  selectedCount: { type: Number, required: true },
});

const enabled = defineModel({ type: Boolean, default: true });

function toggle() {
  enabled.value = !enabled.value;
}
</script>
