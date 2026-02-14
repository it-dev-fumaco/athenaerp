<template>
  <div></div>
</template>

<script>
import axios from 'axios';

const FORM_ID = 'item-attribute-search-form';
const RESULTS_ID = 'item-attribute-search-results';

export default {
  name: 'ItemAttributeSearch',
  mounted() {
    const form = document.getElementById(FORM_ID);
    if (form) {
      form.addEventListener('submit', this.handleSubmit);
    }
  },
  beforeUnmount() {
    const form = document.getElementById(FORM_ID);
    if (form) {
      form.removeEventListener('submit', this.handleSubmit);
    }
  },
  methods: {
    handleSubmit(event) {
      event.preventDefault();
      const form = event.target;
      const itemCode = form.item_code?.value?.trim() ?? '';
      if (!itemCode) return;

      const params = new URLSearchParams({ item_code: itemCode });
      const url = `/search?${params.toString()}`;

      axios.get(url, { responseType: 'text' }).then((response) => {
        const parser = new DOMParser();
        const doc = parser.parseFromString(response.data, 'text/html');
        const newResults = doc.getElementById(RESULTS_ID);
        const currentResults = document.getElementById(RESULTS_ID);
        if (newResults && currentResults) {
          currentResults.innerHTML = newResults.innerHTML;
        }
      }).catch(() => {
        if (typeof window.showNotification === 'function') {
          window.showNotification('danger', 'Search failed. Please try again.', 'fa fa-info');
        }
      });
    },
  },
};
</script>
