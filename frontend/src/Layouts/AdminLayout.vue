/**
 * AdminLayout на разметке Metronic demo1 (aside-dark + header + toolbar + content).
 */
<script setup>
import { computed, onMounted, onUnmounted, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import { actionTypes as authActions, gettersTypes as authGetters } from '@/store/modules/auth';
import { getItem } from '@/helpers/persistenceStorage';
import {
    applyMetronicBody,
    clearMetronicBody,
    ensureMetronicJs,
    METRONIC_BODY_CLASS,
    reinitMetronic,
} from '@/composables/useMetronic';

/**
 * Props layout.
 *
 * @typedef {object} AdminLayoutProps
 * @property {string} title Заголовок toolbar
 * @property {string} [breadcrumb] Хлебная крошка
 */
const props = defineProps({
    /** Заголовок страницы в toolbar */
    title: { type: String, default: 'Админка' },
    /** Подпись в breadcrumb */
    breadcrumb: { type: String, default: '' },
});

const store = useStore();
const page = usePage();

/**
 * Пункты меню aside.
 *
 * @type {Array<{title: string, href: string}>}
 */
const menu = [
    { title: 'Дашборд', href: '/admin' },
    { title: 'Категории', href: '/admin/categories' },
    { title: 'Товары', href: '/admin/products' },
    { title: 'Заявки', href: '/admin/leads' },
    { title: 'Страницы', href: '/admin/pages' },
    { title: 'Настройки', href: '/admin/settings' },
];

/**
 * Текущий пользователь.
 *
 * @type {import('vue').ComputedRef<object|null>}
 */
const user = computed(() => store.getters[authGetters.user]);

/**
 * Текущий путь.
 *
 * @type {import('vue').ComputedRef<string>}
 */
const currentPath = computed(() => page.url.split('?')[0]);

/**
 * Активен ли пункт меню.
 *
 * @param {string} href URL
 * @returns {boolean}
 */
const isActive = (href) => {
    if (href === '/admin') {
        return currentPath.value === '/admin' || currentPath.value === '/admin/';
    }
    return currentPath.value.startsWith(href);
};

onMounted(async () => {
    applyMetronicBody(METRONIC_BODY_CLASS);
    await ensureMetronicJs();
    reinitMetronic();

    if (getItem('access_token') && !user.value) {
        store.dispatch(authActions.me).catch(() => {
            router.visit('/admin/login');
        });
    } else if (!getItem('access_token')) {
        router.visit('/admin/login');
    }
});

watch(
    () => page.url,
    async () => {
        applyMetronicBody(METRONIC_BODY_CLASS);
        await ensureMetronicJs();
        reinitMetronic();
    }
);

onUnmounted(() => {
    clearMetronicBody();
    document.body.removeAttribute('id');
    document.body.className = '';
    document.body.style.removeProperty('--kt-toolbar-height');
    document.body.style.removeProperty('--kt-toolbar-height-tablet-and-mobile');
});

/**
 * Выход.
 *
 * @returns {Promise<void>}
 */
const logout = async () => {
    await store.dispatch(authActions.logout);
    router.visit('/admin/login');
};
</script>

<template>
  <div class="d-flex flex-column flex-root">
    <div class="page d-flex flex-row flex-column-fluid">
      <!--begin::Aside-->
      <div
        id="kt_aside"
        class="aside aside-dark aside-hoverable"
        data-kt-drawer="true"
        data-kt-drawer-name="aside"
        data-kt-drawer-activate="{default: true, lg: false}"
        data-kt-drawer-overlay="true"
        data-kt-drawer-width="{default:'200px', '300px': '250px'}"
        data-kt-drawer-direction="start"
        data-kt-drawer-toggle="#kt_aside_mobile_toggle"
      >
        <div class="aside-logo flex-column-auto" id="kt_aside_logo">
          <Link href="/admin" class="text-white text-hover-primary fs-4 fw-bolder">
            РК ПРОФИ
          </Link>
          <div
            id="kt_aside_toggle"
            class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle"
            data-kt-toggle="true"
            data-kt-toggle-state="active"
            data-kt-toggle-target="body"
            data-kt-toggle-name="aside-minimize"
          >
            <span class="svg-icon svg-icon-1 rotate-180">
              <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                <path fill="currentColor" d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" />
              </svg>
            </span>
          </div>
        </div>

        <div class="aside-menu flex-column-fluid">
          <div
            class="hover-scroll-overlay-y my-5 my-lg-5"
            id="kt_aside_menu_wrapper"
            data-kt-scroll="true"
            data-kt-scroll-activate="{default: false, lg: true}"
            data-kt-scroll-height="auto"
            data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer"
            data-kt-scroll-wrappers="#kt_aside_menu"
            data-kt-scroll-offset="0"
          >
            <div
              class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500"
              id="kt_aside_menu"
              data-kt-menu="true"
            >
              <div class="menu-item">
                <div class="menu-content pt-8 pb-2">
                  <span class="menu-section text-muted text-uppercase fs-8 ls-1">Сайт</span>
                </div>
              </div>

              <div
                v-for="item in menu"
                :key="item.href"
                class="menu-item"
              >
                <Link
                  class="menu-link"
                  :class="{ active: isActive(item.href) }"
                  :href="item.href"
                >
                  <span class="menu-bullet">
                    <span class="bullet bullet-dot" />
                  </span>
                  <span class="menu-title">{{ item.title }}</span>
                </Link>
              </div>
            </div>
          </div>
        </div>

        <div class="aside-footer flex-column-auto pt-5 pb-7 px-5" id="kt_aside_footer">
          <button type="button" class="btn btn-custom btn-primary w-100" @click="logout">
            Выйти
            <span class="d-block fs-8 opacity-75 mt-1">{{ user?.email || 'Админ' }}</span>
          </button>
        </div>
      </div>
      <!--end::Aside-->

      <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">
        <div id="kt_header" class="header align-items-stretch">
          <div class="container-fluid d-flex align-items-stretch justify-content-between">
            <div class="d-flex align-items-center d-lg-none ms-n3 me-1">
              <div class="btn btn-icon btn-active-light-primary" id="kt_aside_mobile_toggle">
                <span class="svg-icon svg-icon-2x">
                  <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M3 6h18v2H3V6zm0 5h18v2H3v-2zm0 5h18v2H3v-2z" />
                  </svg>
                </span>
              </div>
            </div>
            <div class="d-flex align-items-center flex-grow-1">
              <span class="text-dark fw-bolder fs-5">Панель РК ПРОФИ</span>
            </div>
            <div class="d-flex align-items-center">
              <a href="/" target="_blank" class="btn btn-sm btn-light-primary">Открыть сайт</a>
            </div>
          </div>
        </div>

        <div class="content d-flex flex-column flex-column-fluid" id="kt_content">
          <div class="toolbar" id="kt_toolbar">
            <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
              <div class="d-flex align-items-center me-3">
                <h1 class="d-flex align-items-center text-dark fw-bolder my-1 fs-3">{{ title }}</h1>
                <template v-if="breadcrumb">
                  <span class="h-20px border-gray-200 border-start mx-4" />
                  <ul class="breadcrumb breadcrumb-separatorless fw-bold fs-7 my-1">
                    <li class="breadcrumb-item text-muted">
                      <Link href="/admin" class="text-muted text-hover-primary">Админка</Link>
                    </li>
                    <li class="breadcrumb-item">
                      <span class="bullet bg-gray-200 w-5px h-2px" />
                    </li>
                    <li class="breadcrumb-item text-dark">{{ breadcrumb }}</li>
                  </ul>
                </template>
              </div>
            </div>
          </div>

          <div class="post d-flex flex-column-fluid" id="kt_post">
            <div id="kt_content_container" class="container">
              <slot />
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
