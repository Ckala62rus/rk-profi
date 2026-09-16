/**
 * Форма заявки по разметке site-demo (request-fab + request-modal).
 * Teleport в body — CSS шаблона ожидает FAB/модалку прямыми детьми body.
 */
<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import leadsApi from '@/api/modules/leads';
import { actionTypes as uiActions } from '@/store/modules/ui';

defineProps({
    /** Встроенный режим без FAB (на странице контактов) */
    embedded: { type: Boolean, default: false },
});

const store = useStore();
const page = usePage();

/** Ключ Cloudflare Turnstile (пусто = виджет не показываем) */
const turnstileSiteKey = computed(() => page.props.turnstileSiteKey || '');

/** Поля формы */
const form = reactive({
    name: '',
    phone: '',
    email: '',
    message: '',
    company_site: '',
    privacy_accepted: false,
});

/** Момент открытия формы (мс), для антиспам-таймера */
const formOpenedAt = ref(Date.now());
/** Токен Turnstile */
const turnstileToken = ref('');
/** Widget id Turnstile (модалка) */
const turnstileWidgetId = ref(null);
/** Widget id Turnstile (embedded) */
const turnstileEmbeddedWidgetId = ref(null);
/** Ref контейнера виджета в модалке */
const turnstileMount = ref(null);
/** Ref контейнера виджета во встроенной форме */
const turnstileEmbeddedMount = ref(null);

/** Файлы вложений */
const attachmentFiles = ref([]);
/** Файлы карточки предприятия */
const companyCardFiles = ref([]);
/** Drag-over: материалы */
const attachmentDragOver = ref(false);
/** Drag-over: карточка */
const companyCardDragOver = ref(false);
/** Ref input материалов (модалка) */
const attachmentInputRef = ref(null);
/** Ref input карточки (модалка) */
const companyCardInputRef = ref(null);
/** Ref input материалов (embedded) */
const attachmentInputEmbeddedRef = ref(null);
/** Ref input карточки (embedded) */
const companyCardInputEmbeddedRef = ref(null);

/** Идёт отправка */
const isSubmitting = ref(false);
/** Success-модалка */
const successOpen = ref(false);
/** Ошибки полей */
const errors = ref({});
/** Текст статуса */
const statusText = ref('');

const MAX_FILE_SIZE = 10 * 1024 * 1024;

/**
 * Открыта ли модалка заявки.
 *
 * @type {import('vue').ComputedRef<boolean>}
 */
const isOpen = computed(() => store.state.ui.requestModalOpen);

/**
 * Подгрузить скрипт Turnstile один раз.
 *
 * @returns {Promise<void>}
 */
const ensureTurnstileScript = () =>
    new Promise((resolve, reject) => {
        if (!turnstileSiteKey.value) {
            resolve();
            return;
        }
        if (window.turnstile) {
            resolve();
            return;
        }
        const existing = document.querySelector('script[data-turnstile]');
        if (existing) {
            existing.addEventListener('load', () => resolve());
            existing.addEventListener('error', reject);
            return;
        }
        const script = document.createElement('script');
        script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
        script.async = true;
        script.dataset.turnstile = '1';
        script.onload = () => resolve();
        script.onerror = reject;
        document.head.appendChild(script);
    });

/**
 * Смонтировать виджет Turnstile в контейнер.
 *
 * @param {HTMLElement|null} el Контейнер
 * @param {import('vue').Ref<number|null>} widgetIdRef Ref id виджета
 * @returns {void}
 */
const renderTurnstile = (el, widgetIdRef) => {
    if (!turnstileSiteKey.value || !el || !window.turnstile) {
        return;
    }
    if (widgetIdRef.value !== null) {
        window.turnstile.reset(widgetIdRef.value);
        return;
    }
    widgetIdRef.value = window.turnstile.render(el, {
        sitekey: turnstileSiteKey.value,
        callback: (token) => {
            turnstileToken.value = token;
        },
        'expired-callback': () => {
            turnstileToken.value = '';
        },
    });
};

/**
 * Сбросить метку времени формы.
 *
 * @returns {void}
 */
const markFormOpened = () => {
    formOpenedAt.value = Date.now();
};

/**
 * Открыть модалку.
 *
 * @returns {void}
 */
const open = () => {
    markFormOpened();
    store.dispatch(uiActions.openRequestModal);
};

/**
 * Закрыть модалку.
 *
 * @returns {void}
 */
const close = () => {
    store.dispatch(uiActions.closeRequestModal);
    errors.value = {};
    statusText.value = '';
};

/**
 * Закрыть success.
 *
 * @returns {void}
 */
const closeSuccess = () => {
    successOpen.value = false;
    document.body.classList.remove('popup-open', 'popup-blur-active');
};

/**
 * Форматирует размер файла.
 *
 * @param {number} bytes
 * @returns {string}
 */
const formatFileSize = (bytes) => {
    if (!bytes) {
        return '0 КБ';
    }
    if (bytes >= 1024 * 1024) {
        return `${(bytes / (1024 * 1024)).toFixed(1).replace('.0', '')} МБ`;
    }
    return `${Math.max(1, Math.round(bytes / 1024))} КБ`;
};

/**
 * Ключ файла для дедупликации.
 *
 * @param {File} file
 * @returns {string}
 */
const fileKey = (file) =>
    [file.name, file.size, file.lastModified, file.type].join('::');

/**
 * Объединяет списки файлов без дублей, отсекает слишком большие.
 *
 * @param {File[]} current
 * @param {File[]} incoming
 * @returns {{ files: File[], rejected: number }}
 */
const mergeFiles = (current, incoming) => {
    const map = {};
    let rejected = 0;
    [...current, ...incoming].forEach((file) => {
        if (!(file instanceof File)) {
            return;
        }
        if (file.size > MAX_FILE_SIZE) {
            rejected += 1;
            return;
        }
        map[fileKey(file)] = file;
    });
    return { files: Object.values(map), rejected };
};

/**
 * Применить выбранные/сброшенные файлы к слоту.
 *
 * @param {'attachment'|'company_card'} slot
 * @param {File[]} incoming
 * @param {boolean} [replace=false] Заменить список (из input change)
 * @returns {void}
 */
const applyFiles = (slot, incoming, replace = false) => {
    const current = replace
        ? []
        : slot === 'attachment'
            ? attachmentFiles.value
            : companyCardFiles.value;
    const { files, rejected } = mergeFiles(current, incoming);
    if (slot === 'attachment') {
        attachmentFiles.value = files;
    } else {
        companyCardFiles.value = files;
    }
    if (rejected > 0) {
        statusText.value = `Часть файлов пропущена: больше 10 МБ (${rejected}).`;
    }
};

/**
 * Открыть системный диалог выбора файла.
 *
 * @param {'attachment'|'company_card'} slot
 * @param {boolean} [embedded=false]
 * @returns {void}
 */
const openFilePicker = (slot, embedded = false) => {
    const refMap = embedded
        ? {
            attachment: attachmentInputEmbeddedRef,
            company_card: companyCardInputEmbeddedRef,
        }
        : {
            attachment: attachmentInputRef,
            company_card: companyCardInputRef,
        };
    const input = refMap[slot]?.value;
    if (!input) {
        return;
    }
    // Сброс value — повторный выбор того же файла сработает
    input.value = '';
    input.click();
};

/**
 * Клик по зоне загрузки (не по кнопке удаления чипа).
 *
 * @param {MouseEvent} event
 * @param {'attachment'|'company_card'} slot
 * @param {boolean} [embedded=false]
 * @returns {void}
 */
const onZoneClick = (event, slot, embedded = false) => {
    if (event.target.closest('.request-form__file-chip-remove')) {
        return;
    }
    openFilePicker(slot, embedded);
};

/**
 * Enter/Space на зоне.
 *
 * @param {KeyboardEvent} event
 * @param {'attachment'|'company_card'} slot
 * @param {boolean} [embedded=false]
 * @returns {void}
 */
const onZoneKeydown = (event, slot, embedded = false) => {
    if (event.key !== 'Enter' && event.key !== ' ') {
        return;
    }
    event.preventDefault();
    openFilePicker(slot, embedded);
};

/**
 * change у input.
 *
 * @param {Event} event
 * @param {'attachment'|'company_card'} slot
 * @returns {void}
 */
const onFileInputChange = (event, slot) => {
    const input = /** @type {HTMLInputElement} */ (event.target);
    applyFiles(slot, Array.from(input.files || []), true);
};

/**
 * Drop файлов.
 *
 * @param {DragEvent} event
 * @param {'attachment'|'company_card'} slot
 * @returns {void}
 */
const onZoneDrop = (event, slot) => {
    event.preventDefault();
    if (slot === 'attachment') {
        attachmentDragOver.value = false;
    } else {
        companyCardDragOver.value = false;
    }
    const dropped = Array.from(event.dataTransfer?.files || []);
    if (!dropped.length) {
        return;
    }
    applyFiles(slot, dropped, false);
};

/**
 * Удалить файл из слота.
 *
 * @param {'attachment'|'company_card'} slot
 * @param {number} index
 * @returns {void}
 */
const removeFile = (slot, index) => {
    if (slot === 'attachment') {
        attachmentFiles.value = attachmentFiles.value.filter((_, i) => i !== index);
        return;
    }
    companyCardFiles.value = companyCardFiles.value.filter((_, i) => i !== index);
};

/**
 * Сброс файловых input после успешной отправки.
 *
 * @returns {void}
 */
const clearFileInputs = () => {
    [
        attachmentInputRef,
        companyCardInputRef,
        attachmentInputEmbeddedRef,
        companyCardInputEmbeddedRef,
    ].forEach((r) => {
        if (r.value) {
            r.value.value = '';
        }
    });
};

/**
 * Отправить заявку.
 *
 * @returns {Promise<void>}
 */
const submit = async () => {
    isSubmitting.value = true;
    errors.value = {};
    statusText.value = '';

    const data = new FormData();
    data.append('name', form.name);
    data.append('phone', form.phone);
    data.append('email', form.email);
    data.append('message', form.message);
    data.append('company_site', form.company_site);
    data.append('form_opened_at', String(formOpenedAt.value));
    if (form.privacy_accepted) {
        data.append('privacy_accepted', '1');
    }
    if (turnstileToken.value) {
        data.append('cf_turnstile_response', turnstileToken.value);
    }
    // Laravel ждёт attachment[] / company_card[] как массивы файлов
    attachmentFiles.value.forEach((file) => data.append('attachment[]', file));
    companyCardFiles.value.forEach((file) => data.append('company_card[]', file));

    try {
        await leadsApi.create(data);
        form.name = '';
        form.phone = '';
        form.email = '';
        form.message = '';
        form.privacy_accepted = false;
        attachmentFiles.value = [];
        companyCardFiles.value = [];
        clearFileInputs();
        turnstileToken.value = '';
        markFormOpened();
        if (turnstileWidgetId.value !== null && window.turnstile) {
            window.turnstile.reset(turnstileWidgetId.value);
        }
        if (turnstileEmbeddedWidgetId.value !== null && window.turnstile) {
            window.turnstile.reset(turnstileEmbeddedWidgetId.value);
        }
        close();
        successOpen.value = true;
        document.body.classList.add('popup-open', 'popup-blur-active');
    } catch (error) {
        errors.value = error.response?.data?.errors || {};
        statusText.value = error.response?.data?.message || 'Не удалось отправить заявку';
    } finally {
        isSubmitting.value = false;
    }
};

watch(isOpen, async (openNow) => {
    if (!openNow || !turnstileSiteKey.value) {
        return;
    }
    await ensureTurnstileScript();
    renderTurnstile(turnstileMount.value, turnstileWidgetId);
});

onMounted(async () => {
    markFormOpened();
    if (!turnstileSiteKey.value) {
        return;
    }
    try {
        await ensureTurnstileScript();
        renderTurnstile(turnstileEmbeddedMount.value, turnstileEmbeddedWidgetId);
    } catch {
        // Виджет опционален
    }
});

onBeforeUnmount(() => {});
</script>

<template>
  <!-- Общий фрагмент зоны файлов через inline — два места (embedded / modal) -->
  <!-- Встроенная форма на контактах -->
  <form
    v-if="embedded"
    class="request-form"
    novalidate
    enctype="multipart/form-data"
    @submit.prevent="submit"
  >
    <div class="request-form__grid">
      <label class="request-form__field">
        <span class="request-form__label">Ваше имя</span>
        <input v-model="form.name" class="request-form__input" type="text" required>
      </label>
      <label class="request-form__field">
        <span class="request-form__label">Телефон</span>
        <input v-model="form.phone" class="request-form__input" type="tel" required>
      </label>
      <label class="request-form__field request-form__field--email">
        <span class="request-form__label">Почта</span>
        <input v-model="form.email" class="request-form__input" type="email" required>
      </label>
      <label class="request-form__field request-form__field--full">
        <span class="request-form__label">Описание заявки</span>
        <textarea v-model="form.message" class="request-form__textarea" required />
      </label>

      <div class="request-form__documents request-form__field--full">
        <div class="request-form__field request-form__field--file">
          <span class="request-form__label">Материалы к заявке</span>
          <div
            class="request-form__file"
            :class="{
              'has-files': attachmentFiles.length,
              'is-dragover': attachmentDragOver,
            }"
            @dragenter.prevent="attachmentDragOver = true"
            @dragover.prevent="attachmentDragOver = true"
            @dragleave.prevent="attachmentDragOver = false"
            @drop="onZoneDrop($event, 'attachment')"
          >
            <input
              ref="attachmentInputEmbeddedRef"
              class="request-form__file-input"
              type="file"
              name="attachment[]"
              multiple
              accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z"
              @change="onFileInputChange($event, 'attachment')"
            >
            <div
              class="request-form__file-shell"
              role="button"
              tabindex="0"
              @click="onZoneClick($event, 'attachment', true)"
              @keydown="onZoneKeydown($event, 'attachment', true)"
            >
              <div class="request-form__file-main">
                <div class="request-form__file-copy">
                  <div class="request-form__file-title">Нажмите или перетащите файл</div>
                  <div class="request-form__file-value">
                    {{
                      attachmentFiles.length
                        ? `Загружено файлов: ${attachmentFiles.length}`
                        : 'Документы, PDF и архивы до 10 МБ'
                    }}
                  </div>
                </div>
              </div>
              <div v-if="attachmentFiles.length" class="request-form__file-list">
                <div
                  v-for="(file, index) in attachmentFiles"
                  :key="fileKey(file)"
                  class="request-form__file-chip"
                >
                  <span class="request-form__file-chip-state" aria-hidden="true" />
                  <span class="request-form__file-chip-copy">
                    <span class="request-form__file-chip-name">{{ file.name }}</span>
                    <span class="request-form__file-chip-size">{{ formatFileSize(file.size) }}</span>
                  </span>
                  <button
                    type="button"
                    class="request-form__file-chip-remove"
                    aria-label="Удалить файл"
                    @click.stop="removeFile('attachment', index)"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="request-form__field request-form__field--file">
          <span class="request-form__label">Карточка предприятия</span>
          <div
            class="request-form__file"
            :class="{
              'has-files': companyCardFiles.length,
              'is-dragover': companyCardDragOver,
            }"
            @dragenter.prevent="companyCardDragOver = true"
            @dragover.prevent="companyCardDragOver = true"
            @dragleave.prevent="companyCardDragOver = false"
            @drop="onZoneDrop($event, 'company_card')"
          >
            <input
              ref="companyCardInputEmbeddedRef"
              class="request-form__file-input"
              type="file"
              name="company_card[]"
              multiple
              accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z"
              @change="onFileInputChange($event, 'company_card')"
            >
            <div
              class="request-form__file-shell"
              role="button"
              tabindex="0"
              @click="onZoneClick($event, 'company_card', true)"
              @keydown="onZoneKeydown($event, 'company_card', true)"
            >
              <div class="request-form__file-main">
                <div class="request-form__file-copy">
                  <div class="request-form__file-title">Нажмите или перетащите файл</div>
                  <div class="request-form__file-value">
                    {{
                      companyCardFiles.length
                        ? `Загружено файлов: ${companyCardFiles.length}`
                        : 'Документы и архивы до 10 МБ'
                    }}
                  </div>
                </div>
              </div>
              <div v-if="companyCardFiles.length" class="request-form__file-list">
                <div
                  v-for="(file, index) in companyCardFiles"
                  :key="fileKey(file)"
                  class="request-form__file-chip"
                >
                  <span class="request-form__file-chip-state" aria-hidden="true" />
                  <span class="request-form__file-chip-copy">
                    <span class="request-form__file-chip-name">{{ file.name }}</span>
                    <span class="request-form__file-chip-size">{{ formatFileSize(file.size) }}</span>
                  </span>
                  <button
                    type="button"
                    class="request-form__file-chip-remove"
                    aria-label="Удалить файл"
                    @click.stop="removeFile('company_card', index)"
                  >
                    ×
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <label class="request-form__honeypot" aria-hidden="true">
      Сайт<input v-model="form.company_site" type="text" tabindex="-1" autocomplete="off">
    </label>
    <label class="request-form__consent request-form__field--full" data-field="privacy_accepted">
      <input
        v-model="form.privacy_accepted"
        class="request-form__consent-input"
        type="checkbox"
        name="privacy_accepted"
      >
      <span class="request-form__consent-text">
        Согласен на обработку
        <a href="/privacy" target="_blank" rel="noopener noreferrer">персональных данных</a>
        и принимаю
        <a href="/privacy" target="_blank" rel="noopener noreferrer">политику конфиденциальности</a>
      </span>
    </label>
    <span class="request-form__field-error" aria-live="polite">{{ errors.privacy_accepted?.[0] }}</span>
    <div
      v-if="turnstileSiteKey"
      ref="turnstileEmbeddedMount"
      class="request-form__turnstile"
    />
    <div class="request-form__footer">
      <p class="request-form__status">{{ statusText }}</p>
      <button class="request-form__submit btn btn--primary btn--lg" type="submit" :disabled="isSubmitting">
        {{ isSubmitting ? 'Отправка…' : 'Отправить заявку' }}
      </button>
    </div>
  </form>

  <Teleport v-else to="body">
    <button
      class="request-fab btn btn--primary"
      type="button"
      aria-haspopup="dialog"
      aria-controls="request-modal"
      @click="open"
    >
      <span class="request-fab__pulse" aria-hidden="true" />
      <span class="request-fab__label">Отправить заявку</span>
    </button>

    <div
      class="request-modal"
      id="request-modal"
      :class="{ 'is-open': isOpen }"
      :aria-hidden="!isOpen"
    >
      <div class="request-modal__backdrop" @click="close" />
      <div class="request-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="request-modal-title">
        <div class="request-modal__inner">
          <div class="request-modal__top">
            <div class="request-modal__head">
              <p class="request-modal__eyebrow">Связь с производством</p>
              <h2 class="request-modal__title" id="request-modal-title">Отправить заявку</h2>
            </div>
            <button
              class="request-modal__close btn btn--secondary btn--square"
              type="button"
              aria-label="Закрыть форму"
              @click="close"
            >
              ×
            </button>
          </div>

          <form class="request-form" novalidate enctype="multipart/form-data" @submit.prevent="submit">
            <div class="request-form__grid">
              <label class="request-form__field" data-field="name">
                <span class="request-form__label">Ваше имя</span>
                <input v-model="form.name" class="request-form__input" type="text" name="name" placeholder="Как к вам обращаться" required>
                <span class="request-form__field-error" aria-live="polite">{{ errors.name?.[0] }}</span>
              </label>
              <label class="request-form__field" data-field="phone">
                <span class="request-form__label">Телефон</span>
                <input v-model="form.phone" class="request-form__input" type="tel" name="phone" placeholder="+7 900 000-00-00" required>
                <span class="request-form__field-error" aria-live="polite">{{ errors.phone?.[0] }}</span>
              </label>
              <label class="request-form__field request-form__field--email" data-field="email">
                <span class="request-form__label">Почта</span>
                <input v-model="form.email" class="request-form__input" type="email" name="email" placeholder="info@company.ru" required>
                <span class="request-form__field-error" aria-live="polite">{{ errors.email?.[0] }}</span>
              </label>
              <label class="request-form__field request-form__field--full" data-field="message">
                <span class="request-form__label">Описание заявки</span>
                <textarea v-model="form.message" class="request-form__textarea" name="message" placeholder="Что нужно изготовить, объем, сроки, особые требования" required />
                <span class="request-form__field-error" aria-live="polite">{{ errors.message?.[0] }}</span>
              </label>

              <div class="request-form__documents request-form__field--full">
                <div class="request-form__field request-form__field--file" data-field="attachment">
                  <span class="request-form__label">Материалы к заявке</span>
                  <div
                    class="request-form__file"
                    :class="{
                      'has-files': attachmentFiles.length,
                      'is-dragover': attachmentDragOver,
                    }"
                    @dragenter.prevent="attachmentDragOver = true"
                    @dragover.prevent="attachmentDragOver = true"
                    @dragleave.prevent="attachmentDragOver = false"
                    @drop="onZoneDrop($event, 'attachment')"
                  >
                    <input
                      ref="attachmentInputRef"
                      class="request-form__file-input"
                      type="file"
                      name="attachment[]"
                      multiple
                      accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z"
                      @change="onFileInputChange($event, 'attachment')"
                    >
                    <div
                      class="request-form__file-shell"
                      role="button"
                      tabindex="0"
                      @click="onZoneClick($event, 'attachment')"
                      @keydown="onZoneKeydown($event, 'attachment')"
                    >
                      <div class="request-form__file-main">
                        <div class="request-form__file-copy">
                          <div class="request-form__file-title">Нажмите или перетащите файл</div>
                          <div class="request-form__file-value">
                            {{
                              attachmentFiles.length
                                ? `Загружено файлов: ${attachmentFiles.length}`
                                : 'Документы, PDF и архивы до 10 МБ'
                            }}
                          </div>
                        </div>
                      </div>
                      <div v-if="attachmentFiles.length" class="request-form__file-list" aria-live="polite">
                        <div
                          v-for="(file, index) in attachmentFiles"
                          :key="fileKey(file)"
                          class="request-form__file-chip"
                        >
                          <span class="request-form__file-chip-state" aria-hidden="true" />
                          <span class="request-form__file-chip-copy">
                            <span class="request-form__file-chip-name">{{ file.name }}</span>
                            <span class="request-form__file-chip-size">{{ formatFileSize(file.size) }}</span>
                          </span>
                          <button
                            type="button"
                            class="request-form__file-chip-remove"
                            aria-label="Удалить файл"
                            @click.stop="removeFile('attachment', index)"
                          >
                            ×
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <div class="request-form__field request-form__field--file" data-field="company_card">
                  <span class="request-form__label">Карточка предприятия</span>
                  <div
                    class="request-form__file"
                    :class="{
                      'has-files': companyCardFiles.length,
                      'is-dragover': companyCardDragOver,
                    }"
                    @dragenter.prevent="companyCardDragOver = true"
                    @dragover.prevent="companyCardDragOver = true"
                    @dragleave.prevent="companyCardDragOver = false"
                    @drop="onZoneDrop($event, 'company_card')"
                  >
                    <input
                      ref="companyCardInputRef"
                      class="request-form__file-input"
                      type="file"
                      name="company_card[]"
                      multiple
                      accept=".pdf,.doc,.docx,.xls,.xlsx,.txt,.rtf,.jpg,.jpeg,.png,.webp,.zip,.rar,.7z"
                      @change="onFileInputChange($event, 'company_card')"
                    >
                    <div
                      class="request-form__file-shell"
                      role="button"
                      tabindex="0"
                      @click="onZoneClick($event, 'company_card')"
                      @keydown="onZoneKeydown($event, 'company_card')"
                    >
                      <div class="request-form__file-main">
                        <div class="request-form__file-copy">
                          <div class="request-form__file-title">Нажмите или перетащите файл</div>
                          <div class="request-form__file-value">
                            {{
                              companyCardFiles.length
                                ? `Загружено файлов: ${companyCardFiles.length}`
                                : 'Документы и архивы до 10 МБ'
                            }}
                          </div>
                        </div>
                      </div>
                      <div v-if="companyCardFiles.length" class="request-form__file-list" aria-live="polite">
                        <div
                          v-for="(file, index) in companyCardFiles"
                          :key="fileKey(file)"
                          class="request-form__file-chip"
                        >
                          <span class="request-form__file-chip-state" aria-hidden="true" />
                          <span class="request-form__file-chip-copy">
                            <span class="request-form__file-chip-name">{{ file.name }}</span>
                            <span class="request-form__file-chip-size">{{ formatFileSize(file.size) }}</span>
                          </span>
                          <button
                            type="button"
                            class="request-form__file-chip-remove"
                            aria-label="Удалить файл"
                            @click.stop="removeFile('company_card', index)"
                          >
                            ×
                          </button>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <label class="request-form__honeypot" aria-hidden="true">
              Сайт<input v-model="form.company_site" type="text" name="company_site" tabindex="-1" autocomplete="off">
            </label>
            <label class="request-form__consent request-form__field--full" data-field="privacy_accepted">
              <input
                v-model="form.privacy_accepted"
                class="request-form__consent-input"
                type="checkbox"
                name="privacy_accepted"
              >
              <span class="request-form__consent-text">
                Согласен на обработку
                <a href="/privacy" target="_blank" rel="noopener noreferrer">персональных данных</a>
                и принимаю
                <a href="/privacy" target="_blank" rel="noopener noreferrer">политику конфиденциальности</a>
              </span>
            </label>
            <span class="request-form__field-error" aria-live="polite">{{ errors.privacy_accepted?.[0] }}</span>
            <div
              v-if="turnstileSiteKey"
              ref="turnstileMount"
              class="request-form__turnstile"
            />
            <div class="request-form__footer">
              <p class="request-form__status" aria-live="polite">{{ statusText }}</p>
              <button class="request-form__submit btn btn--primary btn--lg" type="submit" :disabled="isSubmitting">
                {{ isSubmitting ? 'Отправка…' : 'Отправить заявку' }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>

    <div
      class="request-modal request-modal--success"
      id="request-success"
      :class="{ 'is-open': successOpen }"
      :aria-hidden="!successOpen"
    >
      <div class="request-modal__backdrop" @click="closeSuccess" />
      <div class="request-modal__dialog request-modal__dialog--success" role="dialog" aria-modal="true">
        <div class="request-modal__inner request-modal__inner--success">
          <div class="request-success">
            <p class="request-modal__eyebrow">Заявка отправлена</p>
            <p class="request-modal__desc request-modal__desc--success">С вами свяжется менеджер.</p>
            <button class="request-success__button btn btn--primary btn--lg" type="button" @click="closeSuccess">
              Хорошо
            </button>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>
