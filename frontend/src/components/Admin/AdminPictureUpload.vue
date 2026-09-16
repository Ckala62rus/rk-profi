/**
 * Карточка загрузки изображения (Element Plus picture-card / drag).
 */
<script setup>
import { computed } from 'vue';
import { Plus } from '@element-plus/icons-vue';

const props = defineProps({
    /** Текущий URL (уже сохранённый) */
    modelValue: { type: String, default: '' },
    /** Подпись */
    label: { type: String, default: 'Изображение' },
    /** Drag-зона вместо picture-card */
    drag: { type: Boolean, default: false },
    /** Подсказка */
    tip: {
        type: String,
        default: 'Рекомендуемый размер: 1200×800 px. JPG/PNG/WebP до 5 МБ',
    },
});

const emit = defineEmits(['update:modelValue', 'file']);

/**
 * Список файлов для el-upload (превью).
 *
 * @type {import('vue').ComputedRef<Array>}
 */
const fileList = computed(() => {
    if (!props.modelValue) {
        return [];
    }
    return [
        {
            name: 'image',
            url: props.modelValue,
            status: 'success',
            uid: -1,
        },
    ];
});

/**
 * Выбор нового файла (без автозагрузки).
 *
 * @param {object} uploadFile Файл Element Plus
 * @returns {void}
 */
const onChange = (uploadFile) => {
    const raw = uploadFile?.raw;
    if (!(raw instanceof File)) {
        return;
    }
    const preview = URL.createObjectURL(raw);
    emit('update:modelValue', preview);
    emit('file', raw);
};

/**
 * Удаление превью — сброс к пустому (на сайте останется демо-плейсхолдер).
 *
 * @returns {void}
 */
const onRemove = () => {
    emit('update:modelValue', '');
    emit('file', null);
};

/**
 * Запрет автозагрузки.
 *
 * @returns {boolean}
 */
const beforeUpload = () => false;
</script>

<template>
  <div class="admin-picture-upload">
    <label v-if="label" class="fs-6 fw-bold mb-2 d-block">{{ label }}</label>
    <el-upload
      :file-list="fileList"
      :list-type="drag ? 'text' : 'picture-card'"
      :drag="drag"
      :auto-upload="false"
      :limit="1"
      accept="image/*"
      :before-upload="beforeUpload"
      :on-change="onChange"
      :on-remove="onRemove"
    >
      <template v-if="drag">
        <el-icon class="el-icon--upload"><Plus /></el-icon>
        <div class="el-upload__text">
          Перетащите файл сюда или <em>нажмите для выбора</em>
        </div>
      </template>
      <template v-else>
        <el-icon><Plus /></el-icon>
      </template>
      <template #tip>
        <div class="el-upload__tip">{{ tip }}</div>
      </template>
    </el-upload>
  </div>
</template>
