/**
 * Страница «Политика конфиденциальности» (ПДн).
 */
<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Контент страницы privacy */
    page: { type: Object, required: true },
});

/**
 * Абзацы политики.
 *
 * @type {import('vue').ComputedRef<string[]>}
 */
const paragraphs = computed(() => {
    const blocks = props.page.blocks || {};
    if (Array.isArray(blocks.paragraphs)) {
        return blocks.paragraphs.filter(Boolean);
    }
    return [];
});
</script>

<template>
  <div>
    <Head :title="page.title || 'Политика конфиденциальности'" />
    <div class="services container">
      <div class="page-header">
        <h1 class="page-title">{{ page.title || 'Политика конфиденциальности' }}</h1>
        <p v-for="(text, index) in paragraphs" :key="index" class="page-desc">{{ text }}</p>
      </div>
    </div>
  </div>
</template>
