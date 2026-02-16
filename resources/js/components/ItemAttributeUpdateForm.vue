<template>
  <div></div>
</template>

<script>
import axios from 'axios';

const FORM_ID = 'updateForm';

export default {
  name: 'ItemAttributeUpdateForm',
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
      const formData = new FormData(form);
      const action = form.getAttribute('action');
      if (!action) return;

      const preloader = document.getElementById('preloader-modal');
      if (preloader && typeof window.$ !== 'undefined') {
        window.$(preloader).modal('show');
      }

      axios.post(action, formData, { headers: { Accept: 'application/json' } })
        .then((response) => {
          const data = response.data;
          if (preloader && typeof window.$ !== 'undefined') {
            window.$(preloader).modal('hide');
          }
          if (data.success && data.redirect) {
            if (typeof window.showNotification === 'function') {
              window.showNotification('success', data.message || 'Attribute updated.', 'fa fa-check');
            }
            window.location.href = data.redirect;
          }
        })
        .catch((err) => {
          if (preloader && typeof window.$ !== 'undefined') {
            window.$(preloader).modal('hide');
          }
          const message = err.response?.data?.message ?? 'An error occurred. Please try again.';
          if (typeof window.showNotification === 'function') {
            window.showNotification('danger', message, 'fa fa-info');
          }
        });
    },
  },
};
</script>
