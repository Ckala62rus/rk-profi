/**
 * Товары категории — сетка catalog__list из site-demo.
 * При нескольких изображениях — лёгкая карусель на карточке.
 */
<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { demoProductImage } from '@/helpers/demoImages';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Категория */
    category: { type: Object, required: true },
    /** Товары */
    products: { type: Array, default: () => [] },
});

/**
 * Список URL картинок для карточки (thumb + галерея без дублей + демо).
 *
 * @param {object} product Товар
 * @param {number} index Индекс в сетке
 * @returns {string[]}
 */
const productImageUrls = (product, index) => {
    const urls = [];
    if (product.thumb_url) {
        urls.push(product.thumb_url);
    }
    (product.gallery_images || []).forEach((img) => {
        if (img?.url && !urls.includes(img.url)) {
            urls.push(img.url);
        }
    });
    if (!urls.length) {
        urls.push(demoProductImage(product.id || index));
    }
    return urls;
};

/**
 * Товары с подготовленными слайдами.
 *
 * @type {import('vue').ComputedRef<Array<object>>}
 */
const productsWithSlides = computed(() =>
    props.products.map((product, index) => ({
        ...product,
        slides: productImageUrls(product, index),
    }))
);

/** Индекс активного слайда по id товара */
const activeSlideById = ref({});
/** Таймер карусели */
let carouselTimer = null;

/**
 * Текущий индекс слайда товара.
 *
 * @param {number|string} productId
 * @returns {number}
 */
const activeIndex = (productId) => activeSlideById.value[productId] || 0;

onMounted(() => {
    carouselTimer = window.setInterval(() => {
        const next = { ...activeSlideById.value };
        productsWithSlides.value.forEach((product) => {
            if (product.slides.length < 2) {
                return;
            }
            const current = next[product.id] || 0;
            next[product.id] = (current + 1) % product.slides.length;
        });
        activeSlideById.value = next;
    }, 3200);
});

onBeforeUnmount(() => {
    if (carouselTimer) {
        window.clearInterval(carouselTimer);
    }
});
</script>

<template>
  <div>
    <Head :title="category.name" />

    <div class="page-header page-header--filters container">
      <ul class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <Link class="breadcrumbs__link" href="/" prefetch itemprop="item">
            <span itemprop="name">Главная</span>
          </Link>
          <meta itemprop="position" content="1">
        </li>
        <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <Link class="breadcrumbs__link" href="/catalog" prefetch itemprop="item">
            <span itemprop="name">Каталог</span>
          </Link>
          <meta itemprop="position" content="2">
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--last" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <span class="breadcrumbs__link" itemprop="item"><span itemprop="name">{{ category.name }}</span></span>
          <meta itemprop="position" content="3">
        </li>
      </ul>
      <h1 class="page-title">{{ category.name }}</h1>
      <p v-if="category.description" class="page-desc">{{ category.description }}</p>
    </div>

    <div class="catalog__list container">
      <article
        v-for="(product, index) in productsWithSlides"
        :key="product.id"
        class="catalog__item catalog__item--secondary"
        :class="{ 'catalog__item--carousel': product.slides.length > 1 }"
        :id="`demo-glove-${index + 1}`"
        :style="product.slides[0]
          ? {
              backgroundImage: `url(${product.slides[activeIndex(product.id)] || product.slides[0]})`,
              backgroundSize: 'cover',
              backgroundPosition: 'center',
            }
          : undefined"
      >
        <div
          v-if="product.slides.length > 1"
          class="catalog__item-carousel"
          aria-hidden="true"
        >
          <span
            v-for="(url, slideIndex) in product.slides"
            :key="`${product.id}-${slideIndex}`"
            class="catalog__item-slide"
            :class="{ 'is-active': activeIndex(product.id) === slideIndex }"
            :style="{ backgroundImage: `url(${url})` }"
          />
        </div>
        <Link class="catalog__item-info" :href="`/catalog/${category.slug}/${product.slug}`" prefetch>
          <h2 class="catalog__item-title">{{ product.name }}</h2>
          <p class="catalog__item-desc">{{ product.short_description }}</p>
        </Link>
        <img
          v-if="product.slides[0] && product.slides.length === 1"
          class="catalog__item-pic"
          :src="product.slides[0]"
          :alt="product.name"
          width="900"
          height="675"
          decoding="async"
        >
      </article>
    </div>
    <p v-if="!products.length" class="container page-desc" style="padding-bottom: 3rem;">
      В этой категории пока нет товаров.
    </p>
  </div>
</template>
