/**
 * Настройки контактов — форма Metronic + карта Яндекса.
 */
<script setup>
import { onMounted, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import settingsApi from '@/api/modules/admin/settings';
import toast from '@/helpers/toast';

/** Успех */
const successText = ref('');
/** Ошибка */
const errorText = ref('');
/** Сохранение */
const isSaving = ref(false);

/** Форма */
const form = reactive({
    phone_display: '',
    phone_tel: '',
    email: '',
    address: '',
    copyright: '',
    map_embed_url: '',
});

/**
 * URL для превью iframe в админке.
 *
 * @returns {string}
 */
const mapPreviewSrc = () => {
    const raw = String(form.map_embed_url || '').trim();
    if (!raw) {
        return '';
    }
    const match = raw.match(/src=["']([^"']+)["']/i);
    if (match?.[1]) {
        return match[1].replace(/&amp;/g, '&');
    }
    return raw;
};

/**
 * Загрузить настройки.
 *
 * @returns {Promise<void>}
 */
const load = async () => {
    errorText.value = '';
    try {
        const response = await settingsApi.showContacts();
        const data = response.data?.data ?? response.data ?? {};
        const phone = data.phones?.[0] || {};
        const email = data.emails?.[0] || {};
        const address = data.addresses?.[0] || {};
        form.phone_display = phone.display || '';
        form.phone_tel = phone.tel || '';
        form.email = email.value || '';
        form.address = address.value || '';
        form.copyright = data.copyright || '';
        form.map_embed_url = data.map_embed_url || '';
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось загрузить';
    }
};

/**
 * Сохранить.
 *
 * @returns {Promise<void>}
 */
const save = async () => {
    isSaving.value = true;
    successText.value = '';
    errorText.value = '';
    try {
        await settingsApi.updateContacts({
            phones: [{ label: 'Основной', display: form.phone_display, tel: form.phone_tel }],
            emails: [{ label: 'Отдел продаж', value: form.email }],
            addresses: [{ label: 'Адрес', value: form.address }],
            copyright: form.copyright,
            map_embed_url: form.map_embed_url,
        });
        successText.value = 'Настройки сохранены.';
        toast.success(successText.value);
        await load();
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Ошибка сохранения';
        toast.error(errorText.value);
    } finally {
        isSaving.value = false;
    }
};

onMounted(load);
</script>

<template>
  <AdminLayout title="Настройки" breadcrumb="Контакты">
    <Head title="Настройки" />

    <div v-if="successText" class="alert alert-success mb-5">{{ successText }}</div>
    <div v-if="errorText" class="alert alert-danger mb-5">{{ errorText }}</div>

    <div class="card">
      <div class="card-header border-0 pt-6">
        <div class="card-title">
          <h3 class="fw-bolder m-0">Контакты сайта</h3>
        </div>
      </div>
      <div class="card-body pt-0">
        <form class="form" @submit.prevent="save">
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label required fw-bold fs-6">Телефон (отображение)</label>
            <div class="col-lg-8">
              <input v-model="form.phone_display" type="text" class="form-control form-control-lg form-control-solid" required>
            </div>
          </div>
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label required fw-bold fs-6">Телефон (tel:)</label>
            <div class="col-lg-8">
              <input v-model="form.phone_tel" type="text" class="form-control form-control-lg form-control-solid" required>
            </div>
          </div>
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label required fw-bold fs-6">Email</label>
            <div class="col-lg-8">
              <input v-model="form.email" type="email" class="form-control form-control-lg form-control-solid" required>
            </div>
          </div>
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-bold fs-6">Адрес</label>
            <div class="col-lg-8">
              <textarea v-model="form.address" class="form-control form-control-solid" rows="2" />
            </div>
          </div>
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-bold fs-6">Карта (Яндекс)</label>
            <div class="col-lg-8">
              <textarea
                v-model="form.map_embed_url"
                class="form-control form-control-solid font-monospace"
                rows="3"
                placeholder="https://yandex.ru/map-widget/v1/?um=constructor%3A...&source=constructor"
              />
              <div class="form-text">
                Вставьте URL из iframe конструктора Яндекс.Карт
                (<code>src="https://yandex.ru/map-widget/..."</code>
                или весь тег <code>&lt;iframe&gt;</code> — система возьмёт src сама.
                Рекомендуется iframe, а не script: стабильнее во Vue.
              </div>
              <div v-if="mapPreviewSrc()" class="mt-4 contacts-admin-map-preview">
                <iframe
                  :src="mapPreviewSrc()"
                  title="Превью карты"
                  style="width: 100%; height: 280px; border: 0; border-radius: 8px;"
                  loading="lazy"
                />
              </div>
            </div>
          </div>
          <div class="row mb-6">
            <label class="col-lg-4 col-form-label fw-bold fs-6">Copyright</label>
            <div class="col-lg-8">
              <input v-model="form.copyright" type="text" class="form-control form-control-solid">
            </div>
          </div>
          <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" :disabled="isSaving">
              {{ isSaving ? 'Сохранение…' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </AdminLayout>
</template>
