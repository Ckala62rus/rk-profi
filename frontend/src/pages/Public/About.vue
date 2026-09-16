/**
 * Страница «О компании» — разметка services/page-header из site-demo.
 */
<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Контент about */
    page: { type: Object, required: true },
});

/**
 * Абзацы описания.
 *
 * @type {import('vue').ComputedRef<string[]>}
 */
const paragraphs = computed(() => {
    const blocks = props.page.blocks || {};
    if (Array.isArray(blocks.paragraphs)) {
        return blocks.paragraphs.filter(Boolean);
    }
    if (blocks.text) {
        return [blocks.text];
    }
    if (blocks.content) {
        return [blocks.content];
    }
    return [
        'Мы производим и поставляем продукцию для промышленности, строительства и торговли. Компания работает с оптовыми и корпоративными клиентами, соблюдает сроки и требования к качеству.',
        'На этой странице можно разместить ваш индивидуальный текст: историю компании, преимущества, производственные мощности, сертификаты и условия сотрудничества.',
    ];
});
</script>

<template>
  <div>
    <Head :title="page.title || 'О компании'" />
    <div class="services container">
      <div class="page-header">
        <h1 class="page-title">{{ page.title || 'О компании' }}</h1>
        <p v-for="(text, index) in paragraphs" :key="index" class="page-desc">{{ text }}</p>
      </div>
    </div>
  </div>
</template>
