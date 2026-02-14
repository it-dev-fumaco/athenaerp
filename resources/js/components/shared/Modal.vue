<template>
  <Teleport to="body">
    <div
      v-if="modelValue"
      class="modal fade show d-block"
      tabindex="-1"
      role="dialog"
      aria-modal="true"
      :aria-labelledby="titleId"
      style="background: rgba(0,0,0,0.5);"
      @click.self="close"
    >
      <div class="modal-dialog" :class="dialogClass" role="document">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" :id="titleId">
              <slot name="title">{{ title }}</slot>
            </h5>
            <button
              type="button"
              class="btn-close"
              aria-label="Close"
              @click="close"
            ></button>
          </div>
          <div class="modal-body">
            <slot></slot>
          </div>
          <div v-if="$slots.footer" class="modal-footer">
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<script>
export default {
  name: 'Modal',
  props: {
    modelValue: { type: Boolean, default: false },
    title: { type: String, default: '' },
    dialogClass: { type: String, default: '' },
  },
  emits: ['update:modelValue'],
  data() {
    return { uniqueId: 'modal-' + Math.random().toString(36).slice(2, 9) };
  },
  computed: {
    titleId() {
      return this.uniqueId;
    },
  },
  methods: {
    close() {
      this.$emit('update:modelValue', false);
    },
  },
};
</script>
