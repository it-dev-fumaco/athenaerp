<template>
  <div class="login-page">
    <section class="branding">
      <img v-if="logoUrl" :src="logoUrl" alt="Fumaco" class="branding-logo" />
      <h1 class="branding-title">
        <span class="branding-title-with-underline">
          <span>Athena</span>
          <span class="branding-underline" aria-hidden="true"></span>
        </span>
        Inventory
      </h1>
    </section>
    <section class="form-section">
      <div class="form-card">
        <h2>Welcome Back!</h2>
        <p class="subtitle">Access your Athena Inventory Account</p>

        <Transition name="error-fade">
          <div v-if="serverError" class="form-errors" role="alert">
            {{ serverError }}
          </div>
        </Transition>

        <form
          ref="formRef"
          method="POST"
          :action="loginUrl"
          @submit.prevent="onSubmit"
        >
          <input type="hidden" name="_token" :value="csrfToken" />
          <input type="hidden" name="email" :value="email" />
          <input type="hidden" name="password" :value="password" />
          <div class="form-group">
            <label for="login-email">Email Address</label>
            <input
              id="login-email"
              v-model="email"
              type="text"
              placeholder="Email Address"
              spellcheck="false"
              required
              autocomplete="username"
              :disabled="loading"
            />
          </div>
          <div class="form-group">
            <label for="login-password">Password</label>
            <input
              id="login-password"
              v-model="password"
              type="password"
              placeholder="Password"
              spellcheck="false"
              required
              autocomplete="current-password"
              :disabled="loading"
            />
          </div>
          <button
            type="submit"
            class="btn-sign-in"
            name="login"
            :disabled="loading"
          >
            <span v-if="!loading">Sign In</span>
            <span v-else class="btn-loading">
              <span class="spinner" aria-hidden="true"></span>
              Signing in…
            </span>
          </button>
        </form>
      </div>
    </section>
  </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
  csrfToken: { type: String, default: '' },
  initialError: { type: String, default: '' },
  initialEmail: { type: String, default: '' },
  loginUrl: { type: String, default: '/login_user' },
  logoUrl: { type: String, default: '' },
});

const formRef = ref(null);
const email = ref(props.initialEmail || '');
const password = ref('');
const loading = ref(false);
const serverError = ref(props.initialError || '');

function onSubmit() {
  serverError.value = '';
  loading.value = true;
  // Small delay so user sees loading state, then submit form (Laravel handles redirect/validation)
  setTimeout(() => {
    if (formRef.value) {
      try {
        const currentProto = window?.location?.protocol || '';
        const action = String(formRef.value.action || '');
        if (currentProto === 'https:' && action.startsWith('http://')) {
          formRef.value.action = action.replace(/^http:\/\//, 'https://');
        }
      } catch (_) {}
      formRef.value.submit();
    }
  }, 300);
}
</script>

<style scoped>
.login-page {
  flex: 1;
  display: flex;
  min-height: 100vh;
  position: relative;
  background: linear-gradient(180deg, #0d3a6e 0%, #0a2d52 50%, #0d3a6e 100%);
}
.login-page::before {
  content: '';
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255, 255, 255, 0.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255, 255, 255, 0.04) 1px, transparent 1px);
  background-size: 32px 32px;
  pointer-events: none;
}
.login-page::after {
  content: '';
  position: absolute;
  inset: 0;
  background: linear-gradient(180deg, rgba(255,255,255,0.06) 0%, transparent 15%, transparent 85%, rgba(255,255,255,0.06) 100%);
  pointer-events: none;
}

.branding {
  flex: 1;
  display: flex;
  flex-direction: column;
  justify-content: center;
  padding: 3rem 4rem;
  position: relative;
  z-index: 1;
}
.branding-logo {
  max-width: 350px;
  height: auto;
  margin-bottom: 2rem;
  display: block;
}
.branding-title {
  font-size: clamp(2rem, 4vw, 3.5rem);
  font-weight: 700;
  margin: 0 0 0.5rem 0;
  letter-spacing: -0.02em;
}
.branding-title-with-underline {
  display: inline-block;
}
.branding-title-with-underline .branding-underline {
  display: block;
  width: 100%;
  height: 4px;
  background: #f5c542;
  border-radius: 2px;
  margin-top: 0.25rem;
}

.form-section {
  flex: 1;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 2rem;
  position: relative;
  z-index: 1;
}
.form-card {
  width: 100%;
  max-width: 420px;
  padding: 2.5rem;
  background: rgba(55, 65, 81, 0.95);
  backdrop-filter: blur(16px);
  -webkit-backdrop-filter: blur(16px);
  border-radius: 20px;
  border: 1px solid rgba(255, 255, 255, 0.12);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}
.form-card h2 {
  margin: 0 0 0.25rem 0;
  font-size: 1.75rem;
  font-weight: 700;
}
.form-card .subtitle {
  margin: 0 0 1.75rem 0;
  font-size: 0.95rem;
  opacity: 0.9;
}
.form-errors {
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: rgba(206, 30, 9, 0.2);
  border-radius: 10px;
  font-size: 0.9rem;
  color: #ffb3a7;
}
.form-group {
  margin-bottom: 1.25rem;
}
.form-group label {
  display: block;
  font-size: 0.9rem;
  margin-bottom: 0.4rem;
  opacity: 0.95;
}
.form-group input {
  width: 100%;
  padding: 0.85rem 1rem;
  font-size: 1rem;
  color: #fff;
  background: rgba(255, 255, 255, 0.12);
  border: 1px solid rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  -webkit-appearance: none;
  appearance: none;
}
.form-group input::placeholder {
  color: rgba(255, 255, 255, 0.6);
}
.form-group input:focus {
  outline: none;
  border-color: rgba(255, 255, 255, 0.4);
  box-shadow: 0 0 0 3px rgba(255, 255, 255, 0.1);
}
.form-group input:disabled {
  opacity: 0.8;
  cursor: not-allowed;
}
.btn-sign-in {
  width: 100%;
  margin-top: 0.5rem;
  padding: 0.95rem 1.5rem;
  font-size: 1rem;
  font-weight: 600;
  color: #fff;
  background: linear-gradient(180deg, #2563eb 0%, #3b82f6 50%, #60a5fa 100%);
  border: none;
  border-radius: 12px;
  cursor: pointer;
  box-shadow: 0 4px 14px rgba(37, 99, 235, 0.4);
}
.btn-sign-in:hover:not(:disabled) {
  background: linear-gradient(180deg, #1d4ed8 0%, #2563eb 50%, #3b82f6 100%);
  box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
}
.btn-sign-in:disabled {
  cursor: wait;
  opacity: 0.9;
}
.btn-loading {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
}
.spinner {
  width: 1rem;
  height: 1rem;
  border: 2px solid rgba(255, 255, 255, 0.3);
  border-top-color: #fff;
  border-radius: 50%;
  animation: spin 0.7s linear infinite;
}
@keyframes spin {
  to { transform: rotate(360deg); }
}

.error-fade-enter-active,
.error-fade-leave-active {
  transition: opacity 0.2s ease, transform 0.2s ease;
}
.error-fade-enter-from,
.error-fade-leave-to {
  opacity: 0;
  transform: translateY(-4px);
}

@media (max-width: 900px) {
  .login-page {
    flex-direction: column;
  }
  .branding {
    padding: 2rem 2rem 1.5rem;
    text-align: center;
    align-items: center;
  }
  .form-section {
    padding: 1.5rem 1.5rem 3rem;
  }
}
</style>
