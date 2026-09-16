/**
 * Страница «Контакты» — разметка contacts__* из site-demo + iframe Яндекс.Карт.
 */
<script setup>
import { computed } from 'vue';
import { Head } from '@inertiajs/vue3';
import PublicLayout from '@/Layouts/PublicLayout.vue';

defineOptions({ layout: PublicLayout });

const props = defineProps({
    /** Контент страницы */
    page: { type: Object, default: null },
    /** Детальные контакты */
    contactsDetail: { type: Object, default: null },
});

/**
 * Телефоны.
 *
 * @type {import('vue').ComputedRef<Array<{display: string, tel: string}>>}
 */
const phones = computed(() => {
    const raw = props.contactsDetail?.phones || [];
    return raw.map((p) => {
        if (typeof p === 'string') {
            return { display: p, tel: p.replace(/[^\d+]/g, '') };
        }
        return {
            display: p.display || p.value || '',
            tel: p.tel || String(p.display || '').replace(/[^\d+]/g, ''),
        };
    }).filter((p) => p.display);
});

/**
 * Emails.
 *
 * @type {import('vue').ComputedRef<string[]>}
 */
const emails = computed(() => {
    const raw = props.contactsDetail?.emails || [];
    return raw.map((e) => (typeof e === 'string' ? e : e.value || e.email)).filter(Boolean);
});

/**
 * Адреса.
 *
 * @type {import('vue').ComputedRef<string[]>}
 */
const addresses = computed(() => {
    const raw = props.contactsDetail?.addresses || [];
    return raw.map((a) => (typeof a === 'string' ? a : a.value || a.address)).filter(Boolean);
});

/**
 * URL iframe Яндекс.Карты из настроек (можно вставить полный iframe — src извлечётся на бэке).
 *
 * @type {import('vue').ComputedRef<string>}
 */
const mapEmbedUrl = computed(() => {
    const raw = props.contactsDetail?.map_embed_url || '';
    if (!raw) {
        return '';
    }
    const match = String(raw).match(/src=["']([^"']+)["']/i);
    if (match?.[1]) {
        return match[1].replace(/&amp;/g, '&');
    }
    return String(raw).trim();
});

/**
 * Ссылка «открыть на карте» (виджет или поиск по адресу).
 *
 * @type {import('vue').ComputedRef<string>}
 */
const mapOpenUrl = computed(() => {
    if (mapEmbedUrl.value) {
        return mapEmbedUrl.value;
    }
    const text = addresses.value[0] || 'Рязань';
    return `https://yandex.ru/maps/?text=${encodeURIComponent(text)}`;
});
</script>

<template>
  <div>
    <Head :title="page?.title || 'Контакты'" />

    <div class="container">
      <div class="contacts__inner">
        <div class="contacts__titles">
          <h1 class="contacts__title">{{ page?.title || 'Контакты' }}</h1>
          <div class="contacts__addresses-links">
            <a class="contacts__addresses-link _scrollTo" href="#office">Адрес</a>
          </div>
        </div>

        <div class="contacts__list">
          <div v-for="phone in phones" :key="phone.display" class="contacts__item">
            <p class="contacts__item-title">Телефон</p>
            <a class="contacts__item-value" :href="`tel:${phone.tel}`">{{ phone.display }}</a>
          </div>
          <div v-for="email in emails" :key="email" class="contacts__item">
            <p class="contacts__item-title">Email</p>
            <a class="contacts__item-value" :href="`mailto:${email}`">{{ email }}</a>
          </div>
        </div>
      </div>

      <div class="contacts__maps">
        <div class="contacts__map" id="office">
          <div class="contacts__map-title">Адрес</div>
          <div class="contacts__map-value">
            <template v-for="(address, index) in addresses" :key="address">
              <template v-if="index"> </template>{{ address }}
            </template>
          </div>

          <div v-if="mapEmbedUrl" class="contacts__map-embed">
            <iframe
              class="contacts__map-iframe"
              :src="mapEmbedUrl"
              title="Карта — офис РК ПРОФИ"
              loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"
              allowfullscreen
            />
          </div>

          <a class="contacts__ymap" id="map-office" :href="mapOpenUrl" target="_blank" rel="noopener">
            Открыть адрес на карте
          </a>
        </div>
      </div>
    </div>
  </div>
</template>
