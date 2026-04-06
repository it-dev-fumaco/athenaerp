<template>
  <Teleport to="body">
    <div
      v-if="model"
      class="phase-out-popup-overlay mass-update-confirm-overlay fixed inset-0 z-[2147483646] flex min-h-0 w-full items-center justify-center overflow-y-auto overscroll-contain px-4 py-10 sm:px-6"
      role="presentation"
      @click.self="emit('cancel')"
    >
      <div
        class="phase-out-popup-panel phase-out-popup-panel--compact mass-update-confirm flex w-full max-w-lg flex-col overflow-hidden text-left"
        role="dialog"
        aria-modal="true"
        aria-labelledby="mass-update-confirm-title"
        aria-describedby="mass-update-confirm-lead mass-update-confirm-desc"
        tabindex="-1"
        @click.stop
      >
        <div class="mass-update-confirm__inner flex flex-col px-8 pb-6 pt-8 sm:px-10 sm:pb-8 sm:pt-10">
          <h2
            id="mass-update-confirm-title"
            class="mass-update-confirm__title text-base font-semibold leading-snug sm:text-lg"
          >
            Confirm Mass Update
          </h2>

          <p
            id="mass-update-confirm-lead"
            class="mass-update-confirm__lead mt-6 text-sm leading-relaxed sm:mt-7"
          >
            You're about to update {{ count }} {{ count === 1 ? 'item' : 'items' }} to '{{ statusLabel }}'.
          </p>

          <div
            id="mass-update-confirm-desc"
            class="mass-update-confirm__notice mt-6 rounded-lg px-4 py-3 sm:mt-7"
            role="status"
          >
            <p class="text-sm font-normal leading-relaxed">
              This action may affect sales and availability.
            </p>
          </div>

          <!-- Segmented actions: primary + secondary read as one control, no accidental gap -->
          <div class="mt-8 flex justify-start sm:mt-10">
            <div
              class="mass-update-confirm__actions inline-flex overflow-hidden rounded-lg shadow-sm ring-1 ring-slate-300/90"
            >
              <button
                type="button"
                class="mass-update-confirm__btn mass-update-confirm__btn--cancel min-h-[2.75rem] min-w-[5.5rem] px-5 text-sm font-medium"
                @click="emit('cancel')"
              >
                Cancel
              </button>
              <button
                type="button"
                class="mass-update-confirm__btn mass-update-confirm__btn--confirm min-h-[2.75rem] min-w-[8.5rem] px-5 text-sm font-semibold disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="confirming"
                @click="emit('confirm')"
              >
                {{ confirming ? 'Updating…' : 'Confirm Update' }}
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  count: { type: Number, required: true },
  statusLabel: { type: String, required: true },
  confirming: { type: Boolean, default: false },
});

const model = defineModel({ type: Boolean, default: false });

const emit = defineEmits(['cancel', 'confirm']);
</script>

<style scoped>
.mass-update-confirm-overlay {
	background-color: rgba(15, 23, 42, 0.42) !important;
	backdrop-filter: blur(4px);
	-webkit-backdrop-filter: blur(4px);
}

.mass-update-confirm__inner {
	font-family:
		ui-sans-serif,
		system-ui,
		-apple-system,
		'Segoe UI',
		Roboto,
		'Helvetica Neue',
		Arial,
		sans-serif;
	-webkit-font-smoothing: antialiased;
}

.mass-update-confirm__title {
	color: #1a1a1a;
}

.mass-update-confirm__lead {
	color: #666666;
}

.mass-update-confirm__notice {
	background-color: #f2f2f2;
}

.mass-update-confirm__notice p {
	color: #4a4a4a;
}

.mass-update-confirm__btn {
	cursor: pointer;
	outline: none;
	border: none;
	margin: 0;
}

.mass-update-confirm__btn:focus-visible {
	box-shadow: inset 0 0 0 2px #ffffff, inset 0 0 0 4px #243b61;
}

.mass-update-confirm__btn--cancel {
	background-color: #e0e0e0 !important;
	color: #1a1a1a !important;
}

.mass-update-confirm__btn--cancel:hover {
	background-color: #d4d4d4 !important;
}

.mass-update-confirm__btn--confirm {
	background-color: #243b61 !important;
	color: #ffffff !important;
	border-left: 1px solid rgba(255, 255, 255, 0.12) !important;
}

.mass-update-confirm__btn--confirm:hover:not(:disabled) {
	background-color: #1d3254 !important;
}

/* Panel: large radius, soft elevation (overrides global phase-out panel) */
.mass-update-confirm {
	border-radius: 20px !important;
	border: 1px solid rgba(0, 0, 0, 0.06) !important;
	background-color: #ffffff !important;
	box-shadow:
		0 24px 64px rgba(15, 23, 42, 0.14),
		0 8px 24px rgba(15, 23, 42, 0.08) !important;
	box-sizing: border-box !important;
}
</style>
