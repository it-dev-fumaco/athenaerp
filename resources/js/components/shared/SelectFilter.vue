<template>
  <div class="position-relative">
    <input
      v-if="searchable"
      ref="inputRef"
      type="text"
      class="form-control"
      :placeholder="placeholder"
      :value="searchTerm"
      autocomplete="off"
      @input="onSearchInput"
      @focus="openDropdown"
      @blur="scheduleClose"
    />
    <select
      v-else
      ref="selectRef"
      class="form-control"
      :value="modelValue"
      :disabled="disabled"
      @change="onSelectChange"
    >
      <option value="" disabled>{{ placeholder }}</option>
      <option
        v-for="opt in options"
        :key="opt.id"
        :value="opt.id"
      >{{ opt.text }}</option>
    </select>
    <ul
      v-if="searchable && showDropdown && (resolvedOptions.length > 0 || loading)"
      class="list-group position-absolute w-100 shadow-sm"
      style="z-index: 1050; max-height: 200px; overflow-y: auto;"
    >
      <li v-if="loading" class="list-group-item text-muted">Loading...</li>
      <li
        v-for="opt in resolvedOptions"
        :key="String(opt.id)"
        class="list-group-item list-group-item-action"
        :class="{ active: String(opt.id) === String(modelValue) }"
        @mousedown.prevent="selectOption(opt)"
      >{{ opt.text }}</li>
    </ul>
  </div>
</template>

<script>
import axios from 'axios';

export default {
  name: 'SelectFilter',
  props: {
    modelValue: { type: [String, Number], default: '' },
    placeholder: { type: String, default: 'Select...' },
    options: { type: Array, default: () => [] },
    searchUrl: { type: String, default: '' },
    searchParam: { type: String, default: 'q' },
    disabled: { type: Boolean, default: false },
    searchable: { type: Boolean, default: false },
  },
  emits: ['update:modelValue'],
  methods: {
    onSearchInput(event) {
      this.searchTerm = event.target.value;
      if (this.searchUrl) {
        this.fetchOptions(this.searchTerm);
      }
      this.showDropdown = true;
    },
    openDropdown() {
      this.showDropdown = true;
      if (this.searchUrl && this.options.length === 0) {
        this.fetchOptions('');
      }
    },
    scheduleClose() {
      this.closeTimer = setTimeout(() => {
        this.showDropdown = false;
      }, 200);
    },
    fetchOptions(term) {
      this.loading = true;
      axios.get(this.searchUrl, { params: { [this.searchParam]: term } })
        .then((response) => {
          const data = response.data;
          const arr = Array.isArray(data) ? data : (data.results || data.data || []);
          this.apiOptions = arr.map((item) => ({
            id: item.id ?? item.value ?? item.name,
            text: item.text ?? item.label ?? item.name ?? String(item.id),
          }));
          this.loading = false;
        })
        .catch(() => {
          this.apiOptions = [];
          this.loading = false;
        });
    },
    selectOption(opt) {
      this.$emit('update:modelValue', opt.id);
      this.searchTerm = opt.text;
      this.showDropdown = false;
      clearTimeout(this.closeTimer);
    },
    onSelectChange(event) {
      this.$emit('update:modelValue', event.target.value);
    },
  },
  data() {
    return {
      apiOptions: [],
      searchTerm: '',
      showDropdown: false,
      loading: false,
      closeTimer: null,
    };
  },
  computed: {
    resolvedOptions() {
      if (this.searchUrl && this.searchable) {
        return this.apiOptions;
      }
      return this.options;
    },
  },
};
</script>
