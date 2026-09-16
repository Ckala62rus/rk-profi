/**
 * Страница входа — разметка Metronic demo1 authentication/flows/basic/sign-in.
 */
<script setup>
import { onMounted, onUnmounted, reactive, ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import { actionTypes as authActions } from '@/store/modules/auth';
import {
    applyMetronicBody,
    clearMetronicBody,
    ensureMetronicJs,
    METRONIC_AUTH_BODY_CLASS,
} from '@/composables/useMetronic';

const store = useStore();

/** Поля формы */
const form = reactive({
    email: '',
    password: '',
});

/** Идёт отправка */
const isSubmitting = ref(false);
/** Ошибка */
const errorText = ref('');

onMounted(async () => {
    applyMetronicBody(METRONIC_AUTH_BODY_CLASS);
    await ensureMetronicJs();
});

onUnmounted(() => {
    clearMetronicBody();
    document.body.removeAttribute('id');
    document.body.className = '';
});

/**
 * Войти в админку.
 *
 * @returns {Promise<void>}
 */
const submit = async () => {
    isSubmitting.value = true;
    errorText.value = '';
    try {
        await store.dispatch(authActions.login, {
            email: form.email,
            password: form.password,
        });
        router.visit('/admin');
    } catch (error) {
        const errors = store.state.auth.authError;
        errorText.value = Array.isArray(errors) ? errors[0] : 'Ошибка входа';
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
  <div>
    <Head title="Вход в админку" />

    <div class="d-flex flex-column flex-root">
      <div
        class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed"
        :style="{ backgroundImage: 'url(/metronic/media/svg/illustrations/progress.svg)' }"
      >
        <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
          <a href="/" class="mb-12 text-dark text-hover-primary fs-2qx fw-bolder">
            РК ПРОФИ
          </a>

          <div class="w-lg-500px bg-white rounded shadow-sm p-10 p-lg-15 mx-auto">
            <form class="form w-100" novalidate @submit.prevent="submit">
              <div class="text-center mb-10">
                <h1 class="text-dark mb-3">Вход в панель</h1>
                <div class="text-gray-400 fw-bold fs-4">Администрирование сайта РК ПРОФИ</div>
              </div>

              <div v-if="errorText" class="alert alert-danger mb-10">{{ errorText }}</div>

              <div class="fv-row mb-10">
                <label class="form-label fs-6 fw-bolder text-dark">Email</label>
                <input
                  v-model="form.email"
                  class="form-control form-control-lg form-control-solid"
                  type="email"
                  name="email"
                  autocomplete="username"
                  required
                >
              </div>

              <div class="fv-row mb-10">
                <div class="d-flex flex-stack mb-2">
                  <label class="form-label fw-bolder text-dark fs-6 mb-0">Пароль</label>
                </div>
                <input
                  v-model="form.password"
                  class="form-control form-control-lg form-control-solid"
                  type="password"
                  name="password"
                  autocomplete="current-password"
                  required
                >
              </div>

              <div class="text-center">
                <button
                  type="submit"
                  class="btn btn-lg btn-primary w-100 mb-5"
                  :disabled="isSubmitting"
                >
                  <span class="indicator-label">{{ isSubmitting ? 'Вход…' : 'Войти' }}</span>
                </button>
              </div>
            </form>
          </div>
        </div>

        <div class="d-flex flex-center flex-column-auto p-10">
          <div class="d-flex align-items-center fw-bold fs-6">
            <a href="/" class="text-muted text-hover-primary px-2">На сайт</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
