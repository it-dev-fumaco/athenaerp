import { watch, onUnmounted, nextTick } from 'vue';

const FOCUSABLE_SELECTOR =
  'button:not([disabled]), [href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])';

/**
 * Trap Tab / Shift+Tab inside container while `isActive` is true.
 * Call when opening a modal dialog; pair with tabindex="-1" on the container for initial focus.
 */
export function useModalFocusTrap(containerRef, isActiveRef) {
  let active = false;

  function getFocusableElements() {
    const root = containerRef.value;
    if (!root || !root.querySelectorAll) {
      return [];
    }
    return Array.from(root.querySelectorAll(FOCUSABLE_SELECTOR)).filter(
      (el) => el.offsetParent !== null || el === document.activeElement
    );
  }

  function onDocumentKeydown(e) {
    if (!active || e.key !== 'Tab') {
      return;
    }
    const focusable = getFocusableElements();
    if (focusable.length === 0) {
      return;
    }
    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    if (e.shiftKey) {
      if (document.activeElement === first || !containerRef.value?.contains(document.activeElement)) {
        e.preventDefault();
        last.focus();
      }
    } else if (document.activeElement === last) {
      e.preventDefault();
      first.focus();
    }
  }

  watch(
    isActiveRef,
    (open) => {
      active = !!open;
      if (open) {
        nextTick(() => {
          containerRef.value?.focus({ preventScroll: true });
        });
        document.addEventListener('keydown', onDocumentKeydown, true);
      } else {
        document.removeEventListener('keydown', onDocumentKeydown, true);
      }
    },
    { flush: 'post' }
  );

  onUnmounted(() => {
    document.removeEventListener('keydown', onDocumentKeydown, true);
  });
}
