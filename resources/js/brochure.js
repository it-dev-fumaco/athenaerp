/**
 * Brochure form page entry. Vue for form + modal and Recently Uploads sidebar; Bootstrap for modal.
 */
import './bootstrap';
import { createApp } from 'vue';
import BrochureHistory from './components/BrochureHistory.vue';
import BrochureForm from './components/BrochureForm.vue';

const brochureFormEl = document.getElementById('brochure-form-app');
if (brochureFormEl) {
    const app = createApp(BrochureForm, {
        csrfToken: brochureFormEl.dataset.csrf || '',
        templateUrl: brochureFormEl.dataset.templateUrl || '',
    });
    app.mount('#brochure-form-app');
}

const brochureSidebarEl = document.getElementById('brochure-sidebar');
if (brochureSidebarEl) {
    createApp(BrochureHistory).mount('#brochure-sidebar');
}
