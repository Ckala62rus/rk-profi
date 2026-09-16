/**
 * CRUD категорий — card + table Metronic (как customers/list).
 */
<script setup>
import { computed, onMounted, reactive, ref, watch } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import categoriesApi from '@/api/modules/admin/categories';
import Swal from 'sweetalert2';
import toast from '@/helpers/toast';
import { Plus } from '@element-plus/icons-vue';
import { demoCategoryImage } from '@/helpers/demoImages';

/** Список */
const items = ref([]);
/** Загрузка */
const isLoading = ref(false);
/** Ошибка */
const errorText = ref('');
/** ID редактирования */
const editingId = ref(null);
/** Текущий URL картинки (из API) */
const currentImageUrl = ref('');
/** Выбранный файл изображения */
const imageFile = ref(null);
/** Превью локального файла */
const imagePreviewUrl = ref('');

/**
 * Открыто ли модальное окно формы категории.
 *
 * @type {import('vue').Ref<boolean>}
 */
const isCategoryModalOpen = ref(false);

/**
 * Блокирует ли автогенерацию `slug` (если пользователь вручную менял поле).
 *
 * @type {import('vue').Ref<boolean>}
 */
const isSlugManuallyEdited = ref(false);

/** Форма */
const form = reactive({
    name: '',
    slug: '',
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
    form.name = '';
    form.slug = '';
    form.description = '';
    form.sort = 0;
    form.is_active = true;
    currentImageUrl.value = '';
    imageFile.value = null;
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }
    imagePreviewUrl.value = '';
};

/**
 * Загрузить категории.
 *
 * @returns {Promise<void>}
 */
const load = async () => {
    isLoading.value = true;
    errorText.value = '';
    try {
        const response = await categoriesApi.index();
        items.value = response.data?.data ?? response.data ?? [];
    } catch (error) {
        errorText.value = error.response?.data?.message || 'Не удалось загрузить';
    } finally {
        isLoading.value = false;
    }
};

/**
 * Начать редактирование.
 *
 * @param {object} item Категория
 * @returns {void}
 */
const startEdit = (item) => {
    isSlugManuallyEdited.value = true;
    editingId.value = item.id;
    form.name = item.name;
    form.slug = item.slug;
    form.description = item.description || '';
    form.sort = item.sort ?? 0;
    form.is_active = Boolean(item.is_active);
    currentImageUrl.value = item.image_url || '';
    imageFile.value = null;
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }
    imagePreviewUrl.value = '';
    isSlugManuallyEdited.value = false;
};

/**
 * Выбор файла изображения категории.
 *
 * @param {object} uploadFile Element Plus upload file
 * @returns {void}
 */
const onImageChange = (uploadFile) => {
    const file = uploadFile?.raw;
    if (!(file instanceof File)) {
        return;
    }
    imageFile.value = file;
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }
    imagePreviewUrl.value = URL.createObjectURL(file);
};

/**
 * Удаление превью изображения.
 *
 * @returns {void}
 */
const onImageRemove = () => {
    imageFile.value = null;
    if (imagePreviewUrl.value) {
        URL.revokeObjectURL(imagePreviewUrl.value);
    }
    imagePreviewUrl.value = '';
    currentImageUrl.value = '';
};

/**
 * Список для el-upload picture-card.
 *
 * @type {import('vue').ComputedRef<Array>}
 */
const imageFileList = computed(() => {
    const url = imagePreviewUrl.value || currentImageUrl.value;
    if (!url) {
        return [];
    }
    return [{ name: 'image', url, status: 'success', uid: -1 }];
});

/**
 * Запрет автозагрузки.
 *
 * @returns {boolean}
 */
const blockAutoUpload = () => false;

/**
 * Сохранить.
 *
 * @returns {Promise<void>}
 */
const save = async () => {
    errorText.value = '';
    const wasEditing = Boolean(editingId.value);
    const payload = { ...form, image: imageFile.value };
    try {
        if (editingId.value) {
            await categoriesApi.update(editingId.value, payload);
        } else {
            await categoriesApi.store(payload);
        }
        resetForm();
        isCategoryModalOpen.value = false;
        await load();
        toast.success(wasEditing ? 'Категория обновлена' : 'Категория создана');
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
        title: 'Удалить категорию?',
        text: 'Категория будет удалена без возможности восстановления.',
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
        await categoriesApi.destroy(id);
        await load();
        toast.success('Категория удалена');
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
 * Открыть форму “Новая категория”.
 *
 * @returns {void}
 */
const openCreate = () => {
    resetForm();
    isSlugManuallyEdited.value = false;
    isCategoryModalOpen.value = true;
};

/**
 * Открыть форму “Редактирование категории”.
 *
 * @param {object} item Категория
 * @returns {void}
 */
const openEdit = (item) => {
    startEdit(item);
    isCategoryModalOpen.value = true;
};

/**
 * Закрыть модалку категории.
 *
 * @returns {void}
 */
const closeModal = () => {
    isCategoryModalOpen.value = false;
    resetForm();
    isSlugManuallyEdited.value = false;
};

onMounted(load);
</script>

<template>
  <AdminLayout title="Категории" breadcrumb="Категории">
    <Head title="Категории" />

    <div v-if="errorText" class="alert alert-danger mb-5">{{ errorText }}</div>

    <!-- Модалка в стиле Metronic (customers/list → Add a Customer) -->
    <Teleport to="body">
      <div
        v-if="isCategoryModalOpen"
        class="modal fade show d-block"
        tabindex="-1"
        aria-modal="true"
        role="dialog"
      >
        <div class="modal-dialog modal-dialog-centered admin-modal">
          <div class="modal-content">
            <form class="form" @submit.prevent="save">
              <div class="modal-header">
                <h2 class="fw-bolder">
                  {{ editingId ? 'Редактирование категории' : 'Новая категория' }}
                </h2>
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
                    <label class="required fs-6 fw-bold mb-2">Название</label>
                    <input
                      v-model="form.name"
                      type="text"
                      class="form-control form-control-solid"
                      placeholder="Название категории"
                      required
                    >
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Слаг</label>
                    <input
                      v-model="form.slug"
                      type="text"
                      class="form-control form-control-solid"
                      placeholder="авто из названия"
                      @input="isSlugManuallyEdited = true"
                    >
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Описание</label>
                    <textarea
                      v-model="form.description"
                      class="form-control form-control-solid"
                      rows="2"
                      placeholder="Описание категории"
                    />
                  </div>

                  <div class="fv-row mb-7">
                    <label class="fs-6 fw-bold mb-2">Изображение</label>
                    <el-upload
                      :file-list="imageFileList"
                      list-type="picture-card"
                      :auto-upload="false"
                      :limit="1"
                      accept="image/*"
                      :before-upload="blockAutoUpload"
                      :on-change="onImageChange"
                      :on-remove="onImageRemove"
                    >
                      <el-icon><Plus /></el-icon>
                    </el-upload>
                    <div class="form-text">
                      Рекомендуемый размер: 1200×800 px. JPG/PNG/WebP до 5 МБ.
                      Если не загружено — на сайте демо-картинка категории.
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
                      <label class="fs-6 fw-bold mb-2">Активна</label>
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
      <div v-if="isCategoryModalOpen" class="modal-backdrop fade show" />
    </Teleport>

    <div class="card">
      <div class="card-header border-0 pt-6">
        <div class="d-flex align-items-center justify-content-between w-100">
          <div class="card-title">
            <h3 class="fw-bolder m-0">Список категорий</h3>
          </div>
          <button type="button" class="btn btn-sm btn-primary" @click="openCreate">
            Новая категория
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
                  :src="row.image_url || demoCategoryImage(row.id)"
                  alt=""
                  style="width: 48px; height: 36px; object-fit: cover; border-radius: 4px;"
                >
              </template>
            </el-table-column>
            <el-table-column prop="name" label="Название" />
            <el-table-column prop="slug" label="Слаг" />

            <el-table-column label="Активна" width="120">
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
