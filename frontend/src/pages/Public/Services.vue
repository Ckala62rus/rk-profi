/**
 * Страница «Услуги» — разметка services/page-header из site-demo.
 */
<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Контент services */
    page: { type: Object, required: true },
});

/**
 * Вводный текст.
 *
 * @type {import('vue').ComputedRef<string>}
 */
const description = computed(() => {
    const blocks = props.page.blocks || {};
    return blocks.intro || blocks.text || blocks.content || '';
});

/**
 * Список услуг.
 *
 * @type {import('vue').ComputedRef<Array<{title?: string, name?: string, text?: string, description?: string}>>}
 */
const items = computed(() => {
    const list = props.page.blocks?.items;
    return Array.isArray(list) ? list : [];
});
</script>

<template>
  <div>
    <Head :title="page.title || 'Услуги'" />
    <div class="services container">
      <div class="page-header">
        <h1 class="page-title">{{ page.title || 'Наши услуги' }}</h1>
        <p v-if="description" class="page-desc">{{ description }}</p>
        <template v-if="items.length">
          <article v-for="(item, index) in items" :key="index" class="page-desc" style="margin-top: 1.5rem;">
            <h2 class="page-title" style="font-size: 1.25rem; margin-bottom: 0.5rem;">
              {{ item.title || item.name }}
            </h2>
            <p>{{ item.text || item.description }}</p>
          </article>
        </template>
      </div>
    </div>
  </div>
</template>
