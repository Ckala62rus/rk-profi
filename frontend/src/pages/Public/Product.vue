/**
 * Карточка товара с галереей (классы product-gallery из site-demo).
 */
<script setup>
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { demoProductImage } from '@/helpers/demoImages';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Категория */
    category: { type: Object, required: true },
    /** Товар */
    product: { type: Object, required: true },
});

/** Индекс активного слайда */
const activeIndex = ref(0);

/**
 * Изображения галереи (с демо-fallback).
 *
 * @type {import('vue').ComputedRef<Array<{url: string, alt?: string}>>}
 */
const images = computed(() => {
    if (props.product.images?.length) {
        return props.product.images;
    }
    if (props.product.thumb_url) {
        return [{ url: props.product.thumb_url, alt: props.product.name }];
    }
    return [{ url: demoProductImage(props.product.id), alt: props.product.name }];
});

/**
 * Предыдущий слайд.
 *
 * @returns {void}
 */
const prev = () => {
    if (!images.value.length) return;
    activeIndex.value = (activeIndex.value - 1 + images.value.length) % images.value.length;
};

/**
 * Следующий слайд.
 *
 * @returns {void}
 */
const next = () => {
    if (!images.value.length) return;
    activeIndex.value = (activeIndex.value + 1) % images.value.length;
};

</script>

<template>
  <div>
    <Head :title="product.name" />

    <div class="page-header page-header--filters container">
      <ul class="breadcrumbs">
        <li class="breadcrumbs__item">
          <Link class="breadcrumbs__link" href="/" prefetch><span>Главная</span></Link>
        </li>
        <li class="breadcrumbs__item">
          <Link class="breadcrumbs__link" href="/catalog" prefetch><span>Каталог</span></Link>
        </li>
        <li class="breadcrumbs__item">
          <Link class="breadcrumbs__link" :href="`/catalog/${category.slug}`" prefetch><span>{{ category.name }}</span></Link>
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--last">
          <span class="breadcrumbs__link"><span>{{ product.name }}</span></span>
        </li>
      </ul>
    </div>

    <section class="product-card-demo container">
      <h1 class="product-card-demo__title">{{ product.name }}</h1>
      <p v-if="product.sku" class="product-card-demo__desc">Артикул: {{ product.sku }}</p>
      <p class="product-card-demo__desc">{{ product.short_description }}</p>

      <div v-if="images.length" class="product-gallery" data-product-gallery>
        <div class="product-gallery__viewport">
          <img
            v-for="(image, index) in images"
            :key="index"
            class="product-gallery__slide"
            :class="{ 'is-active': index === activeIndex }"
            :src="image.url"
            :alt="image.alt || product.name"
          >
        </div>
        <button
          v-if="images.length > 1"
          type="button"
          class="product-gallery__btn product-gallery__btn--prev"
          aria-label="Предыдущее фото"
          @click="prev"
        >
          ‹
        </button>
        <button
          v-if="images.length > 1"
          type="button"
          class="product-gallery__btn product-gallery__btn--next"
          aria-label="Следующее фото"
          @click="next"
        >
          ›
        </button>
        <div v-if="images.length > 1" class="product-gallery__dots">
          <button
            v-for="(image, index) in images"
            :key="`dot-${index}`"
            type="button"
            class="product-gallery__dot"
            :class="{ 'is-active': index === activeIndex }"
            :aria-label="`Слайд ${index + 1}`"
            @click="activeIndex = index"
          />
        </div>
      </div>

      <div v-if="product.description" class="product-card-demo__desc" style="margin-top: 1.5rem;" v-html="product.description" />
    </section>
  </div>
</template>
