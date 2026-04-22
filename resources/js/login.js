/**
 * Login page entry. Vue handles form state, validation display, and loading UI.
 */
import './bootstrap';
import { createApp } from 'vue';
import LoginPage from './components/LoginPage.vue';

const loginEl = document.getElementById('login-app');
if (loginEl) {
  createApp(LoginPage, {
    csrfToken: loginEl.dataset.csrf || '',
    initialError: loginEl.dataset.error || '',
    initialEmail: loginEl.dataset.initialEmail || '',
    loginUrl: loginEl.dataset.loginUrl || '/login_user',
    microsoftLoginUrl: loginEl.dataset.microsoftLoginUrl || '/auth/microsoft/redirect',
    logoUrl: loginEl.dataset.logoUrl || '',
    showMicrosoftLogin: loginEl.dataset.showMicrosoft !== '0',
  }).mount('#login-app');
}
