/**
 * Страница списка категорий каталога (разметка site-demo).
 */
<script setup>
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { categoryImageOrDemo } from '@/helpers/demoImages';

defineOptions({ layout: PublicLayout });

defineProps({
    /** Список категорий */
    categories: { type: Array, default: () => [] },
});
</script>

<template>
  <div>
    <Head title="Каталог" />

    <div class="page-header page-header--filters container">
      <ul class="breadcrumbs" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumbs__item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <Link class="breadcrumbs__link" href="/" prefetch itemprop="item">
            <span itemprop="name">Главная</span>
          </Link>
          <meta itemprop="position" content="1">
        </li>
        <li class="breadcrumbs__item breadcrumbs__item--last" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
          <span class="breadcrumbs__link" itemprop="item"><span itemprop="name">Каталог</span></span>
          <meta itemprop="position" content="2">
        </li>
      </ul>
    </div>

    <section class="categories container categories--page" id="catalog-categories">
      <h2 class="categories__title">Каталог продукции</h2>
      <div class="categories__list">
        <article v-for="(category, index) in categories" :key="category.id" class="categories__item">
          <Link class="categories__item-content" :href="`/catalog/${category.slug}`" prefetch>
            <h3 class="categories__item-title">{{ category.name }}</h3>
            <p class="categories__item-desc">{{ category.description }}</p>
          </Link>
          <img
            class="categories__item-pic lozad--viewed lozad--loaded"
            :src="categoryImageOrDemo(category, index)"
            :alt="category.name"
            data-loaded="true"
          >
        </article>
      </div>
    </section>
  </div>
</template>
