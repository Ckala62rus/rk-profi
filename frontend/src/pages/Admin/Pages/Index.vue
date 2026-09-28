/**
 * CMS: вкладки home / about / services / contacts / privacy.
 * Без «заголовка страницы» / «активна» — только контент блоков.
 */
<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import { Plus } from '@element-plus/icons-vue';
import Swal from 'sweetalert2';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import pagesApi from '@/api/modules/admin/pages';
import toast from '@/helpers/toast';
import { DEMO_PAGE_IMAGES } from '@/helpers/demoImages';

/** Порядок вкладок */
const TAB_ORDER = ['home', 'about', 'services', 'contacts', 'privacy'];

/** Подписи вкладок */
const TAB_LABELS = {
    home: 'Главная',
    about: 'О компании',
    services: 'Услуги',
    contacts: 'Контакты',
    privacy: 'Политика конфиденциальности',
};

/** Рекомендуемые размеры медиа */
const SIZE_HINTS = {
    heroImage: 'Рекомендуемый размер: 1920×1080 px (16:9). JPG/PNG/WebP до 5 МБ.',
    heroVideo: 'Рекомендуемый размер: 1920×1080 px. MP4/WebM до 50 МБ.',
    aboutImage: 'Рекомендуемый размер: 1600×900 px (16:9). JPG/PNG/WebP до 5 МБ.',
};

/**
 * Дефолтные блоки (кнопка «К заводским настройкам»).
 *
 * @type {Record<string, object>}
 */
const DEFAULT_BLOCKS = {
    home: {
        hero: {
            title: 'Производственная компания РК ПРОФИ',
            subtitle:
                'Мы предлагаем широкий ассортимент изделий из натурального кожевенного спилка и натуральной овчины',
            background_type: 'image',
            background_image_url: DEMO_PAGE_IMAGES.hero,
            background_video_url: null,
        },
        about: {
            title: 'РК ПРОФИ',
            background_image_url: DEMO_PAGE_IMAGES.homeAbout,
            items: [
                { title: 'Более 20 лет на рынке', text: 'Информация о предприятии.' },
                {
                    title: 'Широкий ассортимент',
                    text: 'Изделия из натурального кожевенного спилка и натуральной овчины для разных задач.',
                },
                {
                    title: 'Собственное производство',
                    text: 'Производственная база позволяет выпускать продукцию стабильного качества.',
                },
                {
                    title: 'Работа с заказчиками',
                    text: 'Подбираем изделия под требования клиента и помогаем оформить заявку.',
                },
            ],
        },
    },
    about: {
        paragraphs: [
            'Мы производим и поставляем продукцию для промышленности, строительства и торговли. Компания работает с оптовыми и корпоративными клиентами, соблюдает сроки и требования к качеству.',
            'На этой странице можно разместить ваш индивидуальный текст: историю компании, преимущества, производственные мощности, сертификаты и условия сотрудничества.',
        ],
    },
    services: {
        intro:
            'Наряду с производством изделий из натурального кожевенного спилка и овчины, РК ПРОФИ выполняет заказы по индивидуальному пошиву и комплектации партий под требования заказчика.',
        items: [],
    },
    contacts: {},
    privacy: {
        paragraphs: [
            'Настоящая политика конфиденциальности определяет порядок обработки и защиты персональных данных пользователей сайта РК ПРОФИ.',
            'Оставляя заявку на сайте, вы соглашаетесь на обработку указанных персональных данных (имя, телефон, email и содержание обращения) в целях связи по заявке и исполнения договорённостей.',
            'Данные не передаются третьим лицам, за исключением случаев, предусмотренных законодательством Российской Федерации, или когда это необходимо для исполнения заявки (например, доставка корреспонденции).',
            'По вопросам обработки персональных данных вы можете связаться с нами по контактам, указанным на сайте.',
        ],
    },
};

/** Страницы с API */
const pagesBySlug = ref({});
/** Активная вкладка */
const activeTab = ref('home');
/** Загрузка списка */
const isLoading = ref(false);
/** Сохранение вкладки */
const isSaving = ref(false);
/** Ошибка */
const errorText = ref('');

/** Формы по slug (только контент) */
const forms = reactive({
    home: {
        hero_title: '',
        hero_subtitle: '',
        hero_background_type: 'image',
        hero_video_url: null,
        hero_image_url: DEMO_PAGE_IMAGES.hero,
        about_title: 'РК ПРОФИ',
        about_image_url: DEMO_PAGE_IMAGES.homeAbout,
        about_items: [],
    },
    about: {
        paragraphs: [''],
    },
    services: {
        intro: '',
        items: [],
    },
    contacts: {},
    privacy: {
        paragraphs: [''],
    },
});

/** Локальные файлы до сохранения */
const pendingFiles = reactive({
    home_hero: null,
    home_about: null,
    home_video: null,
});

/**
 * @type {import('vue').ComputedRef<string[]>}
 */
const tabs = computed(() => TAB_ORDER);

/**
 * Имя файла видео для подписи.
 *
 * @type {import('vue').ComputedRef<string>}
 */
const heroVideoLabel = computed(() => {
    if (pendingFiles.home_video instanceof File) {
        return pendingFiles.home_video.name;
    }
    const url = forms.home.hero_video_url || '';
    const parts = url.split('/');
    return parts[parts.length - 1] || url || 'видео не выбрано';
});

/**
 * Заполнить форму из модели страницы.
 *
 * @param {string} slug
 * @param {object|null} page
 * @returns {void}
 */
const fillForm = (slug, page) => {
    const blocks = page?.blocks || DEFAULT_BLOCKS[slug] || {};
    const defaults = DEFAULT_BLOCKS[slug] || {};

    if (slug === 'home') {
        const hero = { ...(defaults.hero || {}), ...(blocks.hero || {}) };
        const about = { ...(defaults.about || {}), ...(blocks.about || blocks.about_preview || {}) };
        forms.home.hero_title = hero.title || '';
        forms.home.hero_subtitle = hero.subtitle || '';
        forms.home.hero_background_type = hero.background_type || 'image';
        forms.home.hero_video_url = hero.background_video_url || '/template/video.mp4';
        forms.home.hero_image_url = hero.background_image_url || DEMO_PAGE_IMAGES.hero;
        forms.home.about_title = about.title || 'РК ПРОФИ';
        forms.home.about_image_url = about.background_image_url || DEMO_PAGE_IMAGES.homeAbout;
        forms.home.about_items = (about.items || []).map((item) => ({
            title: item.title || '',
            text: item.text || item.description || '',
        }));
        if (!forms.home.about_items.length) {
            forms.home.about_items = defaults.about.items.map((i) => ({ ...i }));
        }
        return;
    }

    if (slug === 'about') {
        const paragraphs = blocks.paragraphs?.length
            ? blocks.paragraphs
            : defaults.paragraphs || [''];
        forms.about.paragraphs = [...paragraphs];
        return;
    }

    if (slug === 'privacy') {
        const paragraphs = blocks.paragraphs?.length
            ? blocks.paragraphs
            : defaults.paragraphs || [''];
        forms.privacy.paragraphs = [...paragraphs];
        return;
    }

    if (slug === 'services') {
        forms.services.intro = blocks.intro || defaults.intro || '';
        forms.services.items = (blocks.items || []).map((item) => ({
            title: item.title || '',
            text: item.text || item.description || '',
        }));
    }
};

/**
 * Загрузить страницы с API.
 *
 * @returns {Promise<void>}
 */
const load = async () => {
    isLoading.value = true;
    errorText.value = '';
    try {
        const response = await pagesApi.index();
        const list = response.data?.data ?? response.data ?? [];
        const map = {};
        list.forEach((page) => {
            map[page.slug] = page;
        });
        pagesBySlug.value = map;
        TAB_ORDER.forEach((slug) => fillForm(slug, map[slug] || null));
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось загрузить страницы';
        toast.error(errorText.value);
    } finally {
        isLoading.value = false;
    }
};

/**
 * Собрать blocks для сохранения.
 *
 * @param {string} slug
 * @returns {object}
 */
const buildBlocks = (slug) => {
    if (slug === 'home') {
        // blob: нельзя сохранять в БД — только после upload или демо/storage
        const heroImage = String(forms.home.hero_image_url || '').startsWith('blob:')
            ? DEMO_PAGE_IMAGES.hero
            : (forms.home.hero_image_url || DEMO_PAGE_IMAGES.hero);
        const aboutImage = String(forms.home.about_image_url || '').startsWith('blob:')
            ? DEMO_PAGE_IMAGES.homeAbout
            : (forms.home.about_image_url || DEMO_PAGE_IMAGES.homeAbout);
        const heroVideo = String(forms.home.hero_video_url || '').startsWith('blob:')
            ? '/template/video.mp4'
            : (forms.home.hero_video_url || '/template/video.mp4');

        return {
            hero: {
                title: forms.home.hero_title,
                subtitle: forms.home.hero_subtitle,
                background_type: forms.home.hero_background_type,
                background_image_url: heroImage,
                background_video_url: heroVideo,
            },
            about: {
                title: forms.home.about_title,
                background_image_url: aboutImage,
                items: forms.home.about_items.map((item) => ({
                    title: item.title,
                    text: item.text,
                })),
            },
        };
    }
    if (slug === 'about') {
        return {
            paragraphs: forms.about.paragraphs.filter((p) => String(p).trim() !== ''),
        };
    }
    if (slug === 'privacy') {
        return {
            paragraphs: forms.privacy.paragraphs.filter((p) => String(p).trim() !== ''),
        };
    }
    if (slug === 'services') {
        return {
            intro: forms.services.intro,
            items: forms.services.items.map((item) => ({
                title: item.title,
                text: item.text,
            })),
        };
    }
    return {};
};

/**
 * Загрузить pending-файл на сервер.
 *
 * @param {number} pageId
 * @param {File|null} file
 * @param {'image'|'video'} kind
 * @param {string} field
 * @returns {Promise<string|null>}
 */
const uploadIfNeeded = async (pageId, file, kind, field) => {
    if (!(file instanceof File)) {
        return null;
    }
    const response = await pagesApi.uploadMedia(pageId, file, kind, field);
    return response.data?.data?.url || null;
};

/**
 * Сохранить активную вкладку (только blocks).
 *
 * @returns {Promise<void>}
 */
const saveTab = async () => {
    const slug = activeTab.value;
    const page = pagesBySlug.value[slug];
    if (!page?.id) {
        toast.error('Страница не найдена в БД. Выполните сидер.');
        return;
    }

    isSaving.value = true;
    errorText.value = '';
    try {
        if (slug === 'home') {
            // Сначала заливаем файлы — иначе в БД попадут blob: URL и фон «сломается»
            if (pendingFiles.home_hero instanceof File) {
                const heroUrl = await uploadIfNeeded(page.id, pendingFiles.home_hero, 'image', 'hero');
                if (!heroUrl) {
                    throw new Error('Не удалось загрузить изображение hero.');
                }
                forms.home.hero_image_url = heroUrl;
                pendingFiles.home_hero = null;
            }
            if (pendingFiles.home_about instanceof File) {
                const aboutUrl = await uploadIfNeeded(page.id, pendingFiles.home_about, 'image', 'about');
                if (!aboutUrl) {
                    throw new Error('Не удалось загрузить изображение блока «О компании».');
                }
                forms.home.about_image_url = aboutUrl;
                pendingFiles.home_about = null;
            }
            if (pendingFiles.home_video instanceof File) {
                const videoUrl = await uploadIfNeeded(page.id, pendingFiles.home_video, 'video', 'hero-video');
                if (!videoUrl) {
                    throw new Error('Не удалось загрузить видео.');
                }
                forms.home.hero_video_url = videoUrl;
                pendingFiles.home_video = null;
            }
        }

        const response = await pagesApi.update(page.id, {
            blocks: buildBlocks(slug),
        });
        const updated = response.data?.data ?? response.data;
        pagesBySlug.value = { ...pagesBySlug.value, [slug]: updated };
        fillForm(slug, updated);
        toast.success(`«${TAB_LABELS[slug]}» сохранена`);
    } catch (error) {
        errorText.value = error.response?.data?.message
            || error.message
            || 'Ошибка сохранения';
        toast.error(errorText.value);
    } finally {
        isSaving.value = false;
    }
};

/**
 * Восстановить заводские дефолты активной вкладки.
 * Требует ввода «да» — защита от случайного сброса.
 *
 * @returns {Promise<void>}
 */
const restoreDefaults = async () => {
    const slug = activeTab.value;
    const tabLabel = TAB_LABELS[slug] || slug;

    const result = await Swal.fire({
        title: 'Вернуть заводские настройки?',
        html:
            `Будут сброшены поля вкладки «<strong>${tabLabel}</strong>» `
            + 'к значениям по умолчанию.<br><br>'
            + 'Чтобы подтвердить, введите слово <strong>да</strong> '
            + '(без кавычек). После сброса нажмите «Сохранить вкладку».',
        icon: 'warning',
        input: 'text',
        inputPlaceholder: 'да',
        inputAttributes: {
            autocomplete: 'off',
            autocapitalize: 'off',
        },
        showCancelButton: true,
        confirmButtonText: 'Сбросить',
        cancelButtonText: 'Отмена',
        focusConfirm: false,
        customClass: {
            popup: 'swal2-rkprofi',
            icon: 'swal2-rkprofi-icon',
            confirmButton: 'btn btn-danger',
            cancelButton: 'btn btn-light',
        },
        buttonsStyling: false,
        preConfirm: (value) => {
            const typed = String(value || '').trim().toLowerCase();
            if (typed !== 'да') {
                Swal.showValidationMessage('Введите «да», чтобы подтвердить сброс.');
                return false;
            }
            return true;
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    fillForm(slug, {
        ...(pagesBySlug.value[slug] || {}),
        blocks: DEFAULT_BLOCKS[slug],
    });
    if (slug === 'home') {
        pendingFiles.home_hero = null;
        pendingFiles.home_about = null;
        pendingFiles.home_video = null;
    }
    toast.info('Заводские настройки подставлены — нажмите «Сохранить вкладку», чтобы записать в БД');
};

/**
 * Подтверждение удаления через SweetAlert2.
 *
 * @param {string} title
 * @param {string} text
 * @returns {Promise<boolean>}
 */
const confirmDelete = async (title, text) => {
    const result = await Swal.fire({
        title,
        text,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Да, удалить',
        cancelButtonText: 'Отмена',
        customClass: {
            popup: 'swal2-rkprofi',
            icon: 'swal2-rkprofi-icon',
        },
    });
    return result.isConfirmed;
};

/**
 * Удалить пункт преимуществ на главной.
 *
 * @param {number} index
 * @returns {Promise<void>}
 */
const removeAboutItem = async (index) => {
    if (!(await confirmDelete('Удалить пункт?', 'Пункт будет убран из списка преимуществ.'))) {
        return;
    }
    forms.home.about_items.splice(index, 1);
};

/**
 * Удалить абзац «О компании».
 *
 * @param {number} index
 * @returns {Promise<void>}
 */
const removeParagraph = async (index) => {
    if (!(await confirmDelete('Удалить абзац?', 'Текст абзаца будет удалён из формы.'))) {
        return;
    }
    forms.about.paragraphs.splice(index, 1);
};

/**
 * Удалить абзац политики конфиденциальности.
 *
 * @param {number} index
 * @returns {Promise<void>}
 */
const removePrivacyParagraph = async (index) => {
    if (!(await confirmDelete('Удалить абзац?', 'Текст абзаца будет удалён из формы.'))) {
        return;
    }
    forms.privacy.paragraphs.splice(index, 1);
};

/**
 * Удалить пункт услуг.
 *
 * @param {number} index
 * @returns {Promise<void>}
 */
const removeServiceItem = async (index) => {
    if (!(await confirmDelete('Удалить пункт?', 'Пункт услуги будет убран из списка.'))) {
        return;
    }
    forms.services.items.splice(index, 1);
};

/**
 * Список для el-upload: только кастомные файлы (не демо).
 * Иначе limit=1 блокирует загрузку поверх демо-превью.
 *
 * @param {string} url
 * @param {string} demoUrl
 * @returns {Array}
 */
const customImageFileList = (url, demoUrl) => {
    if (!url || url === demoUrl || String(url).startsWith('/template/demo/')) {
        return [];
    }
    return [
        {
            name: 'image',
            url,
            status: String(url).startsWith('blob:') ? 'ready' : 'success',
            uid: String(url),
        },
    ];
};

/**
 * @param {object} uploadFile
 * @returns {void}
 */
const onHeroImageChange = (uploadFile) => {
    const raw = uploadFile?.raw;
    if (!(raw instanceof File)) {
        return;
    }
    if (forms.home.hero_image_url?.startsWith('blob:')) {
        URL.revokeObjectURL(forms.home.hero_image_url);
    }
    pendingFiles.home_hero = raw;
    forms.home.hero_image_url = URL.createObjectURL(raw);
};

/**
 * Сброс hero-картинки к демо.
 *
 * @returns {Promise<boolean>}
 */
const onHeroImageRemove = async () => {
    if (!(await confirmDelete('Сбросить фон?', 'Вернётся изображение по умолчанию.'))) {
        return false;
    }
    if (forms.home.hero_image_url?.startsWith('blob:')) {
        URL.revokeObjectURL(forms.home.hero_image_url);
    }
    pendingFiles.home_hero = null;
    forms.home.hero_image_url = DEMO_PAGE_IMAGES.hero;
    return true;
};

/**
 * @param {object} uploadFile
 * @returns {void}
 */
const onAboutImageChange = (uploadFile) => {
    const raw = uploadFile?.raw;
    if (!(raw instanceof File)) {
        return;
    }
    if (forms.home.about_image_url?.startsWith('blob:')) {
        URL.revokeObjectURL(forms.home.about_image_url);
    }
    pendingFiles.home_about = raw;
    forms.home.about_image_url = URL.createObjectURL(raw);
};

/**
 * Сброс about-фона к демо.
 *
 * @returns {Promise<boolean>}
 */
const onAboutImageRemove = async () => {
    if (!(await confirmDelete('Сбросить фон блока?', 'Вернётся изображение по умолчанию.'))) {
        return false;
    }
    if (forms.home.about_image_url?.startsWith('blob:')) {
        URL.revokeObjectURL(forms.home.about_image_url);
    }
    pendingFiles.home_about = null;
    forms.home.about_image_url = DEMO_PAGE_IMAGES.homeAbout;
    return true;
};

/**
 * Выбор видео hero.
 *
 * @param {object} uploadFile
 * @returns {void}
 */
const onHeroVideoChange = (uploadFile) => {
    const raw = uploadFile?.raw;
    if (!(raw instanceof File)) {
        return;
    }
    pendingFiles.home_video = raw;
    forms.home.hero_video_url = URL.createObjectURL(raw);
};

/**
 * Сброс видео к демо.
 *
 * @returns {Promise<boolean>}
 */
const onHeroVideoRemove = async () => {
    if (!(await confirmDelete('Сбросить видео?', 'Вернётся видео по умолчанию.'))) {
        return false;
    }
    if (forms.home.hero_video_url?.startsWith('blob:')) {
        URL.revokeObjectURL(forms.home.hero_video_url);
    }
    pendingFiles.home_video = null;
    forms.home.hero_video_url = '/template/video.mp4';
    return true;
};

/**
 * При limit=1 заменить файл вместо блокировки загрузки.
 *
 * @param {File[]} files
 * @param {'hero'|'about'|'video'} slot
 * @returns {void}
 */
const onMediaExceed = (files, slot) => {
    const file = files?.[0];
    if (!(file instanceof File)) {
        return;
    }
    if (slot === 'hero') {
        onHeroImageChange({ raw: file });
        return;
    }
    if (slot === 'about') {
        onAboutImageChange({ raw: file });
        return;
    }
    onHeroVideoChange({ raw: file });
};

/**
 * @type {import('vue').ComputedRef<Array>}
 */
const heroVideoFileList = computed(() => {
    if (pendingFiles.home_video instanceof File
        || String(forms.home.hero_video_url || '').startsWith('blob:')) {
        return [
            {
                name: heroVideoLabel.value,
                status: 'ready',
                uid: 'video-pending',
            },
        ];
    }
    if (forms.home.hero_video_url && forms.home.hero_video_url !== '/template/video.mp4') {
        return [
            {
                name: heroVideoLabel.value,
                status: 'success',
                uid: 'video-custom',
            },
        ];
    }
    return [];
});

/**
 * @type {import('vue').ComputedRef<Array>}
 */
const heroImageFileList = computed(() =>
    customImageFileList(forms.home.hero_image_url, DEMO_PAGE_IMAGES.hero)
);

/**
 * @type {import('vue').ComputedRef<Array>}
 */
const aboutImageFileList = computed(() =>
    customImageFileList(forms.home.about_image_url, DEMO_PAGE_IMAGES.homeAbout)
);

/**
 * @returns {boolean}
 */
const blockAutoUpload = () => false;

onMounted(load);
</script>

<template>
  <AdminLayout title="Страницы" breadcrumb="Страницы">
    <Head title="Страницы" />

    <div v-if="errorText" class="alert alert-danger mb-5">{{ errorText }}</div>

    <div class="card admin-pages">
      <div class="card-header border-0 pt-6">
        <div class="d-flex align-items-center justify-content-between w-100 flex-wrap gap-3">
          <div class="card-title m-0">
            <h3 class="fw-bolder m-0">Контентные страницы</h3>
          </div>
          <div class="d-flex gap-2">
            <button
              type="button"
              class="btn btn-sm btn-light-danger"
              :disabled="isSaving || isLoading"
              title="Опасная операция: сброс полей вкладки к заводским значениям"
              @click="restoreDefaults"
            >
              К заводским настройкам
            </button>
            <button
              type="button"
              class="btn btn-sm btn-primary"
              :disabled="isSaving || isLoading"
              @click="saveTab"
            >
              {{ isSaving ? 'Сохранение…' : 'Сохранить вкладку' }}
            </button>
          </div>
        </div>
      </div>

      <div class="card-body pt-0">
        <div v-if="isLoading" class="text-muted py-10">Загрузка…</div>

        <el-tabs v-else v-model="activeTab" type="border-card">
          <el-tab-pane
            v-for="slug in tabs"
            :key="slug"
            :label="TAB_LABELS[slug]"
            :name="slug"
          >
            <div class="page-tab-panel">
              <!-- HOME -->
              <template v-if="slug === 'home'">
                <div class="page-field-group">
                  <h4 class="fw-bolder mb-4">Hero (первый экран)</h4>
                  <div class="fv-row mb-5">
                    <label class="fs-6 fw-bold mb-2">Заголовок</label>
                    <input
                      v-model="forms.home.hero_title"
                      type="text"
                      class="form-control form-control-solid"
                    >
                  </div>
                  <div class="fv-row mb-5">
                    <label class="fs-6 fw-bold mb-2">Подзаголовок</label>
                    <textarea
                      v-model="forms.home.hero_subtitle"
                      class="form-control form-control-solid"
                      rows="2"
                    />
                  </div>
                  <div class="fv-row mb-5">
                    <label class="fs-6 fw-bold mb-2">Тип фона</label>
                    <select
                      v-model="forms.home.hero_background_type"
                      class="form-select form-select-solid"
                      style="max-width: 280px;"
                    >
                      <option value="video">Видео</option>
                      <option value="image">Изображение</option>
                    </select>
                  </div>

                  <div v-if="forms.home.hero_background_type === 'video'" class="mb-5">
                    <label class="fs-6 fw-bold mb-2">Видео фона</label>
                    <el-upload
                      :file-list="heroVideoFileList"
                      drag
                      :auto-upload="false"
                      :limit="1"
                      accept="video/mp4,video/webm,video/quicktime,.mp4,.webm,.mov"
                      :before-upload="blockAutoUpload"
                      :on-change="onHeroVideoChange"
                      :on-remove="onHeroVideoRemove"
                      :on-exceed="(files) => onMediaExceed(files, 'video')"
                    >
                      <el-icon class="el-icon--upload"><Plus /></el-icon>
                      <div class="el-upload__text">
                        Перетащите видео или <em>выберите файл</em>
                      </div>
                      <template #tip>
                        <div class="el-upload__tip">{{ SIZE_HINTS.heroVideo }}</div>
                      </template>
                    </el-upload>
                    <div class="form-text mt-2">
                      Сейчас: {{ heroVideoLabel }}. Удаление в зоне загрузки вернёт видео по умолчанию.
                    </div>
                  </div>

                  <div v-else class="mb-5">
                    <label class="fs-6 fw-bold mb-2">Фоновое изображение hero</label>
                    <div
                      v-if="forms.home.hero_image_url"
                      class="admin-media-preview mb-4"
                    >
                      <div class="admin-media-preview__meta text-muted fs-7 mb-2">
                        {{
                          heroImageFileList.length
                            ? 'Превью выбранного фона'
                            : 'Сейчас установлен демо-фон'
                        }}
                      </div>
                      <img
                        :src="forms.home.hero_image_url"
                        alt="Превью фона hero"
                        class="admin-media-preview__img"
                      >
                    </div>
                    <el-upload
                      :file-list="heroImageFileList"
                      drag
                      list-type="picture"
                      :auto-upload="false"
                      :limit="1"
                      accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                      :before-upload="blockAutoUpload"
                      :on-change="onHeroImageChange"
                      :on-remove="onHeroImageRemove"
                      :on-exceed="(files) => onMediaExceed(files, 'hero')"
                    >
                      <el-icon class="el-icon--upload"><Plus /></el-icon>
                      <div class="el-upload__text">
                        Перетащите фото или <em>выберите файл</em>
                      </div>
                      <template #tip>
                        <div class="el-upload__tip">{{ SIZE_HINTS.heroImage }}</div>
                      </template>
                    </el-upload>
                    <div class="form-text mt-2">
                      После выбора нажмите «Сохранить вкладку».
                      Удаление файла вернёт фон по умолчанию.
                    </div>
                  </div>
                </div>

                <div class="page-field-group">
                  <h4 class="fw-bolder mb-4">Блок «О компании» на главной</h4>
                  <div class="fv-row mb-5">
                    <label class="fs-6 fw-bold mb-2">Заголовок блока</label>
                    <input
                      v-model="forms.home.about_title"
                      type="text"
                      class="form-control form-control-solid"
                    >
                  </div>
                  <label class="fs-6 fw-bold mb-2">Фоновое изображение</label>
                  <div
                    v-if="forms.home.about_image_url"
                    class="admin-media-preview mb-4"
                  >
                    <div class="admin-media-preview__meta text-muted fs-7 mb-2">
                      {{
                        aboutImageFileList.length
                          ? 'Превью выбранного фона блока'
                          : 'Сейчас установлен демо-фон блока'
                      }}
                    </div>
                    <img
                      :src="forms.home.about_image_url"
                      alt="Превью фона о компании"
                      class="admin-media-preview__img"
                    >
                  </div>
                  <el-upload
                    :file-list="aboutImageFileList"
                    drag
                    list-type="picture"
                    :auto-upload="false"
                    :limit="1"
                    accept="image/jpeg,image/png,image/webp,image/gif,.jpg,.jpeg,.png,.webp,.gif"
                    :before-upload="blockAutoUpload"
                    :on-change="onAboutImageChange"
                    :on-remove="onAboutImageRemove"
                    :on-exceed="(files) => onMediaExceed(files, 'about')"
                  >
                    <el-icon class="el-icon--upload"><Plus /></el-icon>
                    <div class="el-upload__text">
                      Перетащите фото или <em>выберите файл</em>
                    </div>
                    <template #tip>
                      <div class="el-upload__tip">{{ SIZE_HINTS.aboutImage }}</div>
                    </template>
                  </el-upload>
                  <div class="form-text mt-2">
                    Удаление файла вернёт фон блока по умолчанию.
                  </div>

                  <div class="mt-6">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                      <h5 class="fw-bold m-0">Преимущества</h5>
                      <button
                        type="button"
                        class="btn btn-sm btn-light-primary"
                        @click="forms.home.about_items.push({ title: '', text: '' })"
                      >
                        + пункт
                      </button>
                    </div>
                    <div
                      v-for="(item, index) in forms.home.about_items"
                      :key="index"
                      class="page-items-row"
                    >
                      <input
                        v-model="item.title"
                        type="text"
                        class="form-control form-control-solid"
                        placeholder="Заголовок"
                      >
                      <input
                        v-model="item.text"
                        type="text"
                        class="form-control form-control-solid"
                        placeholder="Текст"
                      >
                      <button
                        type="button"
                        class="btn btn-sm btn-light-danger"
                        @click="removeAboutItem(index)"
                      >
                        ×
                      </button>
                    </div>
                  </div>
                </div>
              </template>

              <!-- ABOUT -->
              <template v-else-if="slug === 'about'">
                <div class="page-field-group">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bolder m-0">Абзацы</h4>
                    <button
                      type="button"
                      class="btn btn-sm btn-light-primary"
                      @click="forms.about.paragraphs.push('')"
                    >
                      + абзац
                    </button>
                  </div>
                  <div
                    v-for="(_p, index) in forms.about.paragraphs"
                    :key="index"
                    class="mb-4"
                  >
                    <textarea
                      v-model="forms.about.paragraphs[index]"
                      class="form-control form-control-solid"
                      rows="3"
                      placeholder="Текст абзаца"
                    />
                    <button
                      type="button"
                      class="btn btn-sm btn-light-danger mt-2"
                      @click="removeParagraph(index)"
                    >
                      Удалить абзац
                    </button>
                  </div>
                </div>
              </template>

              <!-- SERVICES -->
              <template v-else-if="slug === 'services'">
                <div class="page-field-group">
                  <label class="fs-6 fw-bold mb-2">Вступление</label>
                  <textarea
                    v-model="forms.services.intro"
                    class="form-control form-control-solid"
                    rows="3"
                  />
                </div>
                <div class="page-field-group">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bolder m-0">Пункты услуг</h4>
                    <button
                      type="button"
                      class="btn btn-sm btn-light-primary"
                      @click="forms.services.items.push({ title: '', text: '' })"
                    >
                      + пункт
                    </button>
                  </div>
                  <div
                    v-for="(item, index) in forms.services.items"
                    :key="index"
                    class="page-items-row"
                  >
                    <input
                      v-model="item.title"
                      type="text"
                      class="form-control form-control-solid"
                      placeholder="Название"
                    >
                    <input
                      v-model="item.text"
                      type="text"
                      class="form-control form-control-solid"
                      placeholder="Описание"
                    >
                    <button
                      type="button"
                      class="btn btn-sm btn-light-danger"
                      @click="removeServiceItem(index)"
                    >
                      ×
                    </button>
                  </div>
                </div>
              </template>

              <!-- CONTACTS -->
              <template v-else-if="slug === 'contacts'">
                <div class="alert alert-light-primary mb-0">
                  Телефон, email и адрес редактируются в разделе
                  <a href="/admin/settings">Настройки</a>.
                </div>
              </template>

              <!-- PRIVACY -->
              <template v-else-if="slug === 'privacy'">
                <div class="page-field-group">
                  <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="fw-bolder m-0">Текст политики</h4>
                    <button
                      type="button"
                      class="btn btn-sm btn-light-primary"
                      @click="forms.privacy.paragraphs.push('')"
                    >
                      + абзац
                    </button>
                  </div>
                  <p class="text-muted mb-4">
                    Страница открывается по адресу
                    <a href="/privacy" target="_blank" rel="noopener noreferrer">/privacy</a>
                    (новое окно). Ссылка также в футере и в форме заявки.
                  </p>
                  <div
                    v-for="(_p, index) in forms.privacy.paragraphs"
                    :key="index"
                    class="mb-4"
                  >
                    <textarea
                      v-model="forms.privacy.paragraphs[index]"
                      class="form-control form-control-solid"
                      rows="4"
                      placeholder="Текст абзаца политики"
                    />
                    <button
                      type="button"
                      class="btn btn-sm btn-light-danger mt-2"
                      @click="removePrivacyParagraph(index)"
                    >
                      Удалить абзац
                    </button>
                  </div>
                </div>
              </template>
            </div>
          </el-tab-pane>
        </el-tabs>
      </div>
    </div>
  </AdminLayout>
</template>
