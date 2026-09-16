/**
 * CRUD товаров — Metronic card/table/form.
 */
<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import productsApi from '@/api/modules/admin/products';
import categoriesApi from '@/api/modules/admin/categories';
import Swal from 'sweetalert2';
import toast from '@/helpers/toast';
import { Plus } from '@element-plus/icons-vue';
import { demoProductImage } from '@/helpers/demoImages';

/** Товары */
const items = ref([]);
/** Категории */
const categories = ref([]);
/** Загрузка */
const isLoading = ref(false);
/** Ошибка */
const errorText = ref('');
/** ID редактирования */
const editingId = ref(null);
/** Текущий URL превью */
const currentThumbUrl = ref('');
/** Файл превью */
const thumbFile = ref(null);
/** Превью локального thumb */
const thumbPreviewUrl = ref('');
/** Новые файлы галереи */
const galleryFiles = ref([]);
/** Превью новых файлов галереи { file, url } */
const galleryPending = ref([]);
/** Уже загруженная галерея (из API) */
const existingGallery = ref([]);

/**
 * Открыто ли модальное окно формы товара.
 *
 * @type {import('vue').Ref<boolean>}
 */
const isProductModalOpen = ref(false);

/**
 * Блокирует ли автогенерацию `slug` (если пользователь вручную менял поле).
 *
 * @type {import('vue').Ref<boolean>}
 */
const isSlugManuallyEdited = ref(false);

/** Форма */
const form = reactive({
    category_id: '',
    name: '',
    slug: '',
    sku: '',
    short_description: '',
    description: '',
    sort: 0,
    is_active: true,
});

/**
 * Сброс формы.
 *
 * @returns {void}
 */
const resetForm = () => {
    editingId.value = null;
    form.category_id = categories.value[0]?.id || '';
    form.name = '';
    form.slug = '';
    form.sku = '';
    form.short_description = '';
    form.description = '';
    form.sort = 0;
    form.is_active = true;
    currentThumbUrl.value = '';
    thumbFile.value = null;
    if (thumbPreviewUrl.value) {
        URL.revokeObjectURL(thumbPreviewUrl.value);
    }
    thumbPreviewUrl.value = '';
    galleryFiles.value = [];
    galleryPending.value.forEach((item) => URL.revokeObjectURL(item.url));
    galleryPending.value = [];
    existingGallery.value = [];
};

/**
 * Загрузить данные.
 *
 * @returns {Promise<void>}
 */
const load = async () => {
    isLoading.value = true;
    errorText.value = '';
    try {
        const [productsRes, categoriesRes] = await Promise.all([
            productsApi.index(),
            categoriesApi.index(),
        ]);
        const productsData = productsRes.data?.data ?? productsRes.data;
        items.value = productsData?.data ?? productsData ?? [];
        categories.value = categoriesRes.data?.data ?? categoriesRes.data ?? [];
        if (!form.category_id && categories.value[0]) {
            form.category_id = categories.value[0].id;
        }
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось загрузить';
    } finally {
        isLoading.value = false;
    }
};

/**
 * Редактировать.
 *
 * @param {object} item Товар
 * @returns {void}
 */
const startEdit = (item) => {
    // Пока заполняем форму данными из API — не перетираем slug автогенерацией.
    isSlugManuallyEdited.value = true;

    editingId.value = item.id;
    form.category_id = item.category_id;
    form.name = item.name;
    form.slug = item.slug;
    form.sku = item.sku || '';
    form.short_description = item.short_description || '';
    form.description = item.description || '';
    form.sort = item.sort ?? 0;
    form.is_active = Boolean(item.is_active);
    currentThumbUrl.value = item.thumb_url || '';
    thumbFile.value = null;
    if (thumbPreviewUrl.value) {
        URL.revokeObjectURL(thumbPreviewUrl.value);
    }
    thumbPreviewUrl.value = '';
    galleryFiles.value = [];
    galleryPending.value.forEach((item) => URL.revokeObjectURL(item.url));
    galleryPending.value = [];
    existingGallery.value = Array.isArray(item.gallery_images) ? [...item.gallery_images] : [];

    isSlugManuallyEdited.value = false;
};

/**
 * Список файлов превью для el-upload.
 *
 * @type {import('vue').ComputedRef<Array>}
 */
const thumbFileList = computed(() => {
    const url = thumbPreviewUrl.value || currentThumbUrl.value;
    if (!url) {
        return [];
    }
    return [{ name: 'thumb', url, status: 'success', uid: -1 }];
});

/**
 * Список галереи: существующие + новые превью.
 *
 * @type {import('vue').ComputedRef<Array>}
 */
const galleryFileList = computed(() => {
    const existing = existingGallery.value.map((img) => ({
        name: img.alt || `gallery-${img.id}`,
        url: img.url,
        status: 'success',
        uid: img.id,
        mediaId: img.id,
    }));
    const pending = galleryPending.value.map((item, index) => ({
        name: item.file.name,
        url: item.url,
        status: 'ready',
        uid: `new-${index}-${item.file.name}`,
        raw: item.file,
        isNew: true,
    }));
    return [...existing, ...pending];
});

/**
 * Выбор превью через Element Plus.
 *
 * @param {object} uploadFile
 * @returns {void}
 */
const onThumbChange = (uploadFile) => {
    const file = uploadFile?.raw;
    if (!(file instanceof File)) {
        return;
    }
    thumbFile.value = file;
    if (thumbPreviewUrl.value) {
        URL.revokeObjectURL(thumbPreviewUrl.value);
    }
    thumbPreviewUrl.value = URL.createObjectURL(file);
};

/**
 * Удаление превью из upload (локально).
 *
 * @returns {void}
 */
const onThumbRemove = () => {
    thumbFile.value = null;
    if (thumbPreviewUrl.value) {
        URL.revokeObjectURL(thumbPreviewUrl.value);
    }
    thumbPreviewUrl.value = '';
    currentThumbUrl.value = '';
};

/**
 * Добавление файлов в галерею.
 *
 * @param {object} uploadFile
 * @returns {void}
 */
const onGalleryChange = (uploadFile) => {
    const file = uploadFile?.raw;
    if (!(file instanceof File)) {
        return;
    }
    galleryFiles.value = [...galleryFiles.value, file];
    galleryPending.value = [
        ...galleryPending.value,
        { file, url: URL.createObjectURL(file) },
    ];
};

/**
 * Удаление из списка галереи (существующее — через API).
 *
 * @param {object} uploadFile
 * @returns {Promise<boolean|void>}
 */
const onGalleryRemove = async (uploadFile) => {
    if (uploadFile?.mediaId) {
        await removeGalleryMedia(uploadFile.mediaId);
        return;
    }
    if (uploadFile?.raw instanceof File) {
        galleryFiles.value = galleryFiles.value.filter((f) => f !== uploadFile.raw);
        const pending = galleryPending.value.find((p) => p.file === uploadFile.raw);
        if (pending) {
            URL.revokeObjectURL(pending.url);
        }
        galleryPending.value = galleryPending.value.filter((p) => p.file !== uploadFile.raw);
    }
};

/**
 * Запрет автозагрузки.
 *
 * @returns {boolean}
 */
const blockAutoUpload = () => false;

/**
 * Удалить уже сохранённый файл галереи.
 *
 * @param {number} mediaId ID media Spatie
 * @returns {Promise<void>}
 */
const removeGalleryMedia = async (mediaId) => {
    if (!editingId.value || !mediaId) {
        return;
    }

    const result = await Swal.fire({
        title: 'Удалить фото?',
        text: 'Изображение будет удалено из галереи.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Да, удалить',
        cancelButtonText: 'Отмена',
        customClass: {
            popup: 'swal2-rkprofi',
            icon: 'swal2-rkprofi-icon',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await productsApi.destroyMedia(editingId.value, mediaId);
        existingGallery.value = existingGallery.value.filter((img) => img.id !== mediaId);
        toast.success('Фото удалено');
        await load();
    } catch (error) {
        toast.error(error.response?.data?.message || 'Не удалось удалить фото');
    }
};

/**
 * Сохранить.
 *
 * @returns {Promise<void>}
 */
const save = async () => {
    errorText.value = '';
    const wasEditing = Boolean(editingId.value);
    const payload = {
        ...form,
        category_id: Number(form.category_id),
        thumb: thumbFile.value,
        gallery: galleryFiles.value,
    };
    try {
        if (editingId.value) {
            await productsApi.update(editingId.value, payload);
        } else {
            await productsApi.store(payload);
        }
        resetForm();
        isProductModalOpen.value = false;
        await load();
        toast.success(wasEditing ? 'Товар обновлён' : 'Товар создан');
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Ошибка сохранения';
        toast.error(errorText.value);
    }
};

/**
 * Удалить.
 *
 * @param {number} id ID
 * @returns {Promise<void>}
 */
const remove = async (id) => {
    const result = await Swal.fire({
        title: 'Удалить товар?',
        text: 'Товар будет удалён без возможности восстановления.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Да, удалить',
        cancelButtonText: 'Отмена',
        customClass: {
            popup: 'swal2-rkprofi',
            icon: 'swal2-rkprofi-icon',
        },
    });

    if (!result.isConfirmed) {
        return;
    }

    try {
        await productsApi.destroy(id);
        await load();
        toast.success('Товар удалён');
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Ошибка удаления';
        toast.error(errorText.value);
    }
};

/**
 * Преобразовать строку в slug (для auto-заполнения).
 *
 * @param {string} value
 * @returns {string}
 */
const toSlug = (value) =>
    String(value)
        .toLowerCase()
        .trim()
        .replace(/[^a-z0-9а-яё]+/gi, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');

watch(
    () => form.name,
    (newName) => {
        if (isSlugManuallyEdited.value) {
            return;
        }

        form.slug = toSlug(newName);
    },
);

/**
 * Открыть форму “Новый товар”.
 *
 * @returns {void}
 */
const openCreate = () => {
    resetForm();
    isSlugManuallyEdited.value = false;
    isProductModalOpen.value = true;
};

/**
 * Открыть форму “Редактирование товара”.
 *
 * @param {object} item Товар
 * @returns {void}
 */
const openEdit = (item) => {
    startEdit(item);
    isProductModalOpen.value = true;
};

/**
 * Закрыть модалку товара.
 *
 * @returns {void}
 */
const closeModal = () => {
    isProductModalOpen.value = false;
    resetForm();
    isSlugManuallyEdited.value = false;
};

onMounted(load);
</script>

<template>
  <AdminLayout title="Товары" breadcrumb="Товары">
    <Head title="Товары" />

    <div v-if="errorText" class="alert alert-danger mb-5">{{ errorText }}</div>

    <!-- Модалка в стиле Metronic (customers/list → Add a Customer) -->
    <Teleport to="body">
      <div
        v-if="isProductModalOpen"
        class="modal fade show d-block"
        tabindex="-1"
        aria-modal="true"
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered admin-modal admin-modal--lg">
          <div class="modal-content">
            <form class="form" @submit.prevent="save">
              <div class="modal-header">
                <h2 class="fw-bolder">{{ editingId ? 'Редактирование товара' : 'Новый товар' }}</h2>
                <div
                  class="btn btn-icon btn-sm btn-active-icon-primary"
                  @click="closeModal"
                >
                  <span class="svg-icon svg-icon-1">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                      <path
                        fill="currentColor"
                        d="M6.7 5.3a1 1 0 0 0-1.4 1.4L10.6 12l-5.3 5.3a1 1 0 1 0 1.4 1.4L12 13.4l5.3 5.3a1 1 0 0 0 1.4-1.4L13.4 12l5.3-5.3a1 1 0 0 0-1.4-1.4L12 10.6 6.7 5.3Z"
                      />
                    </svg>
                  </span>
                </div>
              </div>

              <div class="modal-body">
                <div class="admin-modal__scroll">
                  <div class="fv-row mb-7">
                    <label class="required fs-6 fw-bold mb-2">Категория</label>
                    <select
                      v-model="form.category_id"
                      class="form-select form-select-solid"
                      required
                    >
                      <option
                        v-for="cat in categories"
                        :key="cat.id"
                        :value="cat.id"
                      >
                        {{ cat.name }}
                      </option>
                    </select>
                  </div>

                  <div class="fv-row mb-7">
                    <label class="required fs-6 fw-bold mb-2">Название</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="form-control form-control-solid"
                      placeholder="Название товара"
                      required
                    >
                  </div>

                  <div class="row g-9 mb-7">
                    <div class="col-md-6 fv-row">
                      <label class="fs-6 fw-bold mb-2">Слаг</label>
                      <input
                        v-model="form.slug"
                        type="text"
                        class="form-control form-control-solid"
                        placeholder="авто из названия"
                        @input="isSlugManuallyEdited = true"
                      >
                    </div>
                    <div class="col-md-6 fv-row">
                      <label class="fs-6 fw-bold mb-2">Артикул</label>
                      <input
                        v-model="form.sku"
                        type="text"
                        class="form-control form-control-solid"
                        placeholder="SKU"
                      >
                    </div>
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Краткое описание</label>
                    <textarea
                      v-model="form.short_description"
                      class="form-control form-control-solid"
                      rows="2"
                      placeholder="Кратко о товаре"
                    />
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Описание</label>
                    <textarea
                      v-model="form.description"
                      class="form-control form-control-solid"
                      rows="2"
                      placeholder="Полное описание"
                    />
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Превью</label>
                    <el-upload
                      :file-list="thumbFileList"
                      list-type="picture-card"
                      :auto-upload="false"
                      :limit="1"
                      accept="image/*"
                      :before-upload="blockAutoUpload"
                      :on-change="onThumbChange"
                      :on-remove="onThumbRemove"
                    >
                      <el-icon><Plus /></el-icon>
                    </el-upload>
                    <div class="form-text">
                      Рекомендуемый размер: 900×675 px (4:3). JPG/PNG/WebP до 5 МБ.
                      Если не загружено — на сайте демо-картинка.
                    </div>
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Галерея</label>
                    <el-upload
                      :file-list="galleryFileList"
                      list-type="picture-card"
                      :auto-upload="false"
                      accept="image/*"
                      multiple
                      :before-upload="blockAutoUpload"
                      :on-change="onGalleryChange"
                      :on-remove="onGalleryRemove"
                    >
                      <el-icon><Plus /></el-icon>
                    </el-upload>
                    <div class="form-text">
                      Рекомендуемый размер: 1200×900 px. JPG/PNG/WebP до 5 МБ на файл.
                    </div>
                  </div>

                  <div class="row g-9 mb-7">
                    <div class="col-md-6 fv-row">
                      <label class="fs-6 fw-bold mb-2">Сортировка</label>
                      <input
                        v-model.number="form.sort"
                        type="number"
                        min="0"
                        class="form-control form-control-solid"
                      >
                    </div>
                    <div class="col-md-6 fv-row">
                      <label class="fs-6 fw-bold mb-2">Активен</label>
                      <div class="d-flex flex-stack">
                        <div class="fw-bold text-gray-400">Показывать на сайте</div>
                        <label class="form-check form-switch form-check-custom form-check-solid">
                          <input
                            v-model="form.is_active"
                            class="form-check-input"
                            type="checkbox"
                          >
                          <span class="form-check-label fw-bold text-gray-400">
                            {{ form.is_active ? 'Да' : 'Нет' }}
                          </span>
                        </label>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="modal-footer flex-center">
                <button type="button" class="btn btn-light me-3" @click="closeModal">
                  Отмена
                </button>
                <button type="submit" class="btn btn-primary">
                  <span class="indicator-label">Сохранить</span>
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
      <div v-if="isProductModalOpen" class="modal-backdrop fade show" />
    </Teleport>

    <div class="card">
      <div class="card-header border-0 pt-6">
        <div class="d-flex align-items-center justify-content-between w-100">
          <div class="card-title">
            <h3 class="fw-bolder m-0">Список товаров</h3>
          </div>
          <button type="button" class="btn btn-sm btn-primary" @click="openCreate">
            Новый товар
          </button>
        </div>
      </div>
      <div class="card-body pt-0">
        <div v-if="isLoading" class="text-muted py-10">Загрузка…</div>
        <div v-else class="table-responsive">
          <el-table :data="items" style="width: 100%">
            <el-table-column prop="id" label="ID" width="80" />
            <el-table-column label="Фото" width="90">
              <template #default="{ row }">
                <img
                  :src="row.thumb_url || demoProductImage(row.id)"
                  alt=""
                  style="width: 48px; height: 36px; object-fit: cover; border-radius: 4px;"
                >
              </template>
            </el-table-column>
            <el-table-column prop="name" label="Название" />

            <el-table-column label="Категория">
              <template #default="{ row }">
                {{ row.category?.name || row.category_id }}
              </template>
            </el-table-column>

            <el-table-column label="Активен" width="120">
              <template #default="{ row }">
                <span class="badge" :class="row.is_active ? 'badge-light-success' : 'badge-light-danger'">
                  {{ row.is_active ? 'да' : 'нет' }}
                </span>
              </template>
            </el-table-column>

            <el-table-column label="Действия" width="200" align="right">
              <template #default="{ row }">
                <div class="text-end">
                  <button
                    type="button"
                    class="btn btn-sm btn-light btn-active-light-primary me-2"
                    @click="openEdit(row)"
                  >
                    Изменить
                  </button>
                  <button
                    type="button"
                    class="btn btn-sm btn-light-danger"
                    @click="remove(row.id)"
                  >
                    Удалить
                  </button>
                </div>
              </template>
            </el-table-column>
          </el-table>
        </div>
      </div>
    </div>
  </AdminLayout>
</template>
