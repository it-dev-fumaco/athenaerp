<template>
  <div class="flex flex-wrap items-center justify-between gap-3 border-y border-slate-200 bg-slate-100 px-4 py-2.5">
    <span class="flex items-center gap-2 text-sm font-medium text-slate-800">
      <svg class="h-4 w-4 shrink-0 text-slate-500" width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z" />
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
      </svg>
      Adjust Suggested Discounts
    </span>
    <button
      type="button"
      role="switch"
      :aria-checked="enabled"
      class="relative inline-flex h-7 w-12 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#1976D2] focus-visible:ring-offset-2"
      :class="enabled ? 'bg-[#1976D2]' : 'bg-slate-300'"
      @click="toggle"
    >
      <span
        class="pointer-events-none inline-block h-6 w-6 transform rounded-full bg-white shadow ring-0 transition"
        :class="enabled ? 'translate-x-5' : 'translate-x-0.5'"
      />
    </button>
  </div>
</template>

<script setup>
const enabled = defineModel({ type: Boolean, default: true });

function toggle() {
  enabled.value = !enabled.value;
}
</script>
