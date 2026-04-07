<template>
  <Teleport to="body">
    <div
      v-if="isOpen"
      class="cmu-overlay"
      role="presentation"
      @click.self="onCancel?.()"
    >
      <div
        class="cmu-dialog"
        role="dialog"
        aria-modal="true"
        aria-labelledby="cmu-title"
        aria-describedby="cmu-subtitle cmu-warning"
        @click.stop
      >
        <header class="cmu-header">
          <h2 id="cmu-title" class="cmu-title">Confirm Mass Update</h2>
        </header>

        <div class="cmu-body">
          <p id="cmu-subtitle" class="cmu-subtitle">
            You're about to update {{ count }} {{ count === 1 ? 'item' : 'items' }} to '{{ statusLabel }}'.
          </p>

          <div id="cmu-warning" class="cmu-warning" role="status">
            <span class="cmu-warning-icon" aria-hidden="true">
              <svg viewBox="0 0 24 24" fill="currentColor">
                <path
                  d="M1.43 20.25h21.14c.64 0 1.04-.7.71-1.25L12.71 3.5a.82.82 0 0 0-1.42 0L.72 19c-.33.55.07 1.25.71 1.25ZM12 9c.55 0 1 .45 1 1v4a1 1 0 0 1-2 0v-4c0-.55.45-1 1-1Zm0 8a1.25 1.25 0 1 1 0-2.5A1.25 1.25 0 0 1 12 17Z"
                />
              </svg>
            </span>
            <p class="cmu-warning-text">{{ warningMessage }}</p>
          </div>
        </div>

        <footer class="cmu-footer">
          <div class="cmu-actions">
            <button type="button" class="cmu-btn cmu-btn-cancel" @click="onCancel?.()">
              Cancel
            </button>
            <button type="button" class="cmu-btn cmu-btn-confirm" @click="onConfirm?.()">
              Confirm Update
            </button>
          </div>
        </footer>
      </div>
    </div>
  </Teleport>
</template>

<script setup>
defineProps({
  count: { type: Number, required: true },
  statusLabel: { type: String, required: true },
  warningMessage: { type: String, required: true },
  onCancel: { type: Function, required: true },
  onConfirm: { type: Function, required: true },
  isOpen: { type: Boolean, required: true },
});
</script>

<style scoped>
.cmu-overlay {
  position: fixed !important;
  inset: 0 !important;
  z-index: 2147483646 !important;
  display: flex !important;
  align-items: center !important;
  justify-content: center !important;
  padding: 16px !important;
  background: rgba(15, 23, 42, 0.42) !important;
}

.cmu-dialog {
  width: min(400px, calc(100vw - 32px)) !important;
  max-width: min(400px, calc(100vw - 32px)) !important;
  border-radius: 8px !important;
  background: #ffffff !important;
  border: 1px solid rgba(0, 0, 0, 0.06) !important;
  box-shadow:
    0 24px 64px rgba(15, 23, 42, 0.14),
    0 8px 24px rgba(15, 23, 42, 0.08) !important;
  overflow: hidden !important;
  box-sizing: border-box !important;
  font-family:
    'Inter',
    ui-sans-serif,
    system-ui,
    -apple-system,
    'Segoe UI',
    Roboto,
    'Helvetica Neue',
    Arial,
    sans-serif !important;
}

.cmu-header {
  padding: 14px 16px 10px !important;
}

.cmu-title {
  margin: 0 !important;
  font-size: 16px !important;
  font-weight: 600 !important;
  line-height: 1.35 !important;
  color: #111827 !important;
}

.cmu-body {
  padding: 0 16px 12px !important;
}

.cmu-subtitle {
  margin: 0 0 12px 0 !important;
  font-size: 14px !important;
  font-weight: 400 !important;
  line-height: 1.45 !important;
  color: #6b7280 !important;
}

.cmu-warning {
  display: flex !important;
  align-items: flex-start !important;
  gap: 10px !important;
  padding: 10px 14px !important;
  border-radius: 8px !important;
  background: #fff8e1 !important;
}

.cmu-warning-icon {
  display: inline-flex !important;
  width: 18px !important;
  height: 18px !important;
  flex: 0 0 18px !important;
  color: #f4b400 !important;
  margin-top: 1px !important;
}

.cmu-warning-icon svg {
  width: 18px !important;
  height: 18px !important;
}

.cmu-warning-text {
  margin: 0 !important;
  font-size: 13px !important;
  font-weight: 400 !important;
  line-height: 1.45 !important;
  color: #6b4f00 !important;
}

.cmu-footer {
  padding: 12px 16px 14px !important;
}

.cmu-actions {
  display: flex !important;
  justify-content: flex-end !important;
  gap: 10px !important;
}

.cmu-btn {
  border-radius: 6px !important;
  font-size: 14px !important;
  line-height: 1.2 !important;
  padding: 8px 16px !important;
  cursor: pointer !important;
  border: 1px solid transparent !important;
  user-select: none !important;
}

.cmu-btn-cancel {
  background: transparent !important;
  border-color: #e5e7eb !important;
  color: #6b7280 !important;
}

.cmu-btn-cancel:hover {
  background: #f9fafb !important;
}

.cmu-btn-confirm {
  background: #2563eb !important;
  border-color: #2563eb !important;
  color: #ffffff !important;
}

.cmu-btn-confirm:hover {
  background: #1d4ed8 !important;
  border-color: #1d4ed8 !important;
}
</style>

