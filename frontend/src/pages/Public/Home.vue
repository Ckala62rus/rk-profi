/**
 * Главная страница — разметка site-demo (main-screen, categories, main-about).
 */
<script setup>
import { computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';
import { categoryImageOrDemo, DEMO_PAGE_IMAGES } from '@/helpers/demoImages';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Модель страницы home */
    page: { type: Object, default: null },
    /** Категории каталога */
    categories: { type: Array, default: () => [] },
});

/**
 * Блоки страницы.
 *
 * @type {import('vue').ComputedRef<object>}
 */
const blocks = computed(() => props.page?.blocks || {});

/**
 * Hero.
 *
 * @type {import('vue').ComputedRef<object>}
 */
const hero = computed(() => {
    const raw = blocks.value.hero || {};
    const type = raw.background_type === 'image' ? 'image' : 'video';
    const imageUrl = raw.background_image_url || DEMO_PAGE_IMAGES.hero;
    return {
        title: raw.title || 'Производственная компания РК ПРОФИ',
        subtitle: raw.subtitle || 'Мы предлагаем широкий ассортимент изделий из натурального кожевенного спилка и натуральной овчины',
        background_type: type,
        background_image_url: imageUrl,
        background_video_url: raw.background_video_url || '/template/video.mp4',
    };
});

/**
 * Превью «О компании».
 *
 * @type {import('vue').ComputedRef<object>}
 */
const aboutPreview = computed(() => {
    const raw = blocks.value.about_preview || blocks.value.about || {};
    return {
        title: raw.title || 'РК ПРОФИ',
        background_image_url: raw.background_image_url || DEMO_PAGE_IMAGES.homeAbout,
        items: raw.items || raw.list || [
            { title: 'Более 20 лет на рынке', text: 'Информация о предприятии.' },
            { title: 'Широкий ассортимент', text: 'Изделия из натурального кожевенного спилка и натуральной овчины для разных задач.' },
            { title: 'Собственное производство', text: 'Производственная база позволяет выпускать продукцию стабильного качества.' },
            { title: 'Работа с заказчиками', text: 'Подбираем изделия под требования клиента и помогаем оформить заявку.' },
        ],
    };
});

/**
 * Список преимуществ для main-about.
 *
 * @type {import('vue').ComputedRef<Array>}
 */
const aboutItems = computed(() => aboutPreview.value.items || aboutPreview.value.list || []);

/**
 * URL фона hero (видео или изображение).
 *
 * @type {import('vue').ComputedRef<string>}
 */
const heroVideo = computed(() => hero.value.background_video_url || '/template/video.mp4');

/**
 * Стиль фона блока about.
 *
 * @type {import('vue').ComputedRef<string>}
 */
const aboutBg = computed(() => {
    const url = aboutPreview.value.background_image_url;
    if (!url) {
        return 'linear-gradient(rgba(0,0,0,.5), rgba(0,0,0,.5))';
    }
    return `linear-gradient(rgba(0, 0, 0, 50%), rgba(0, 0, 0, 50%)), url('${url}')`;
});
</script>

<template>
  <div>
    <Head title="Главная" />

    <section class="main-screen" id="top">
      <div class="main-screen__content container">
        <h1 class="main-screen__title">{{ hero.title }}</h1>
        <div class="main-screen__footer">
          <div class="main-screen__footer-item">
            <p class="main-screen__footer-text">{{ hero.subtitle }}</p>
          </div>
        </div>
      </div>
      <video
        v-if="hero.background_type !== 'image'"
        class="main-screen__bg _bgVideo"
        autoplay
        muted
        loop
        playsinline
        preload="auto"
      >
        <source :src="heroVideo" type="video/mp4" media="(max-width: 639px)">
        <source :src="heroVideo" type="video/mp4">
      </video>
      <div
        v-else
        class="main-screen__bg"
        :style="{
          backgroundImage: `url('${hero.background_image_url}')`,
          backgroundSize: 'cover',
          backgroundPosition: 'center',
          backgroundRepeat: 'no-repeat',
        }"
      />
    </section>

    <section class="categories container categories--home" id="catalog">
      <h2 class="categories__title">Каталог продукции</h2>
      <div class="categories__list">
        <article
          v-for="(category, index) in categories"
          :key="category.id"
          class="categories__item"
        >
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

    <section class="main-about" id="about">
      <div class="main-about__inner" :style="{ backgroundImage: aboutBg }">
        <div class="main-about__inner-content container">
          <h3 class="block-title">{{ aboutPreview.title || 'РК ПРОФИ' }}</h3>
          <ul class="main-about__inner-list">
            <li v-for="(item, index) in aboutItems" :key="index" class="main-about__inner-item">
              <b>{{ item.title || item.name }}</b>
              <p>{{ item.text || item.description }}</p>
            </li>
          </ul>
        </div>
      </div>
    </section>
  </div>
</template>
