/**
 * Login page entry. Vue handles form state, validation display, and loading UI.
 */
import './bootstrap';
import { createApp } from 'vue';
import LoginPage from './components/LoginPage.vue';

const loginEl = document.getElementById('login-app');
if (loginEl) {
  const rawLoginUrl = loginEl.dataset.loginUrl || '/login_user';
  const loginUrl =
    typeof window !== 'undefined' &&
    window.location?.protocol === 'https:' &&
    typeof rawLoginUrl === 'string' &&
    rawLoginUrl.startsWith('http://')
      ? rawLoginUrl.replace(/^http:\/\//, 'https://')
      : rawLoginUrl;

  createApp(LoginPage, {
    csrfToken: loginEl.dataset.csrf || '',
    initialError: loginEl.dataset.error || '',
    initialEmail: loginEl.dataset.initialEmail || '',
    loginUrl: loginUrl || '/login_user',
    logoUrl: loginEl.dataset.logoUrl || '',
  }).mount('#login-app');
}
