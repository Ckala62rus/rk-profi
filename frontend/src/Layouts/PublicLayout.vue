/**
 * Публичный layout по разметке site-demo (grabber):
 * header + fixed header + mobile-menu + footer + FAB заявки.
 *
 * Используется как persistent layout Inertia — не перемонтируется
 * при переходах между страницами (быстрее, чем 8080-статика по ощущениям).
 */
<script setup>
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { useStore } from 'vuex';
import RequestForm from '@/components/RequestForm.vue';
import { actionTypes as settingsActions } from '@/store/modules/settings';
import { actionTypes as uiActions } from '@/store/modules/ui';

/**
 * Props layout (если заданы явно — приоритетнее авто-конфига).
 *
 * @typedef {object} PublicLayoutProps
 * @property {string} [bodyClass] Класс body
 * @property {boolean} [invertedHeader] Инвертированный header
 * @property {'l'|'s'} [headerSize] Размер шапки
 */
const props = defineProps({
    bodyClass: { type: String, default: null },
    invertedHeader: { type: Boolean, default: null },
    headerSize: {
        type: String,
        default: null,
        validator: (value) => value === null || ['l', 's'].includes(value),
    },
});

const store = useStore();
const page = usePage();

/**
 * Конфиг layout по имени Inertia-страницы.
 *
 * @type {import('vue').ComputedRef<{bodyClass: string, invertedHeader: boolean, headerSize: 'l'|'s'}>}
 */
const autoConfig = computed(() => {
    const name = String(page.component || '');
    if (name === 'Public/Home') {
        return { bodyClass: 'home-page', invertedHeader: true, headerSize: /** @type {'s'} */ ('s') };
    }
    if (name === 'Public/Contacts') {
        return { bodyClass: 'contacts-page', invertedHeader: false, headerSize: /** @type {'l'} */ ('l') };
    }
    return { bodyClass: '', invertedHeader: false, headerSize: /** @type {'l'} */ ('l') };
});

/** @type {import('vue').ComputedRef<string>} */
const resolvedBodyClass = computed(() =>
    props.bodyClass !== null ? props.bodyClass : autoConfig.value.bodyClass
);

/** @type {import('vue').ComputedRef<boolean>} */
const resolvedInverted = computed(() =>
    props.invertedHeader !== null ? props.invertedHeader : autoConfig.value.invertedHeader
);

/** @type {import('vue').ComputedRef<'l'|'s'>} */
const resolvedHeaderSize = computed(() =>
    props.headerSize !== null ? /** @type {'l'|'s'} */ (props.headerSize) : autoConfig.value.headerSize
);

/** @type {import('vue').ComputedRef<Record<string, boolean>>} */
const headerClass = computed(() => ({
    [`header--${resolvedHeaderSize.value}`]: true,
    'header--inverted': resolvedInverted.value,
}));

/** @type {import('vue').ComputedRef<string>} */
const logoClass = computed(() => `header__logo header__logo--${resolvedHeaderSize.value}`);

/** Sticky header активен (локально — без Vuex, чтобы не терять при переходах) */
const fixedActive = ref(false);

/** @type {import('vue').ComputedRef<object|null>} */
const contacts = computed(() => page.props.contacts);

/** @type {import('vue').ComputedRef<Array<{label: string, href: string}>>} */
const mainMenu = computed(() => page.props.mainMenu || []);

/** @type {import('vue').ComputedRef<boolean>} */
const mobileMenuOpen = computed(() => store.state.ui.mobileMenuOpen);

/** @type {import('vue').ComputedRef<string>} */
const phoneDisplay = computed(() => {
    const phones = contacts.value?.phones;
    if (Array.isArray(phones) && phones[0]) {
        const p = phones[0];
        return typeof p === 'string' ? p : p.display || p.value || '';
    }
    return '';
});

/** @type {import('vue').ComputedRef<string>} */
const phoneTel = computed(() => {
    const phones = contacts.value?.phones;
    if (Array.isArray(phones) && phones[0] && typeof phones[0] === 'object') {
        return phones[0].tel || phoneDisplay.value.replace(/[^\d+]/g, '');
    }
    return phoneDisplay.value.replace(/[^\d+]/g, '');
});

/** @type {import('vue').ComputedRef<string>} */
const email = computed(() => {
    const emails = contacts.value?.emails;
    if (Array.isArray(emails) && emails[0]) {
        const e = emails[0];
        return typeof e === 'string' ? e : e.value || e.email || '';
    }
    return '';
});

/** @type {import('vue').ComputedRef<string>} */
const address = computed(() => {
    const addresses = contacts.value?.addresses;
    if (Array.isArray(addresses) && addresses[0]) {
        const a = addresses[0];
        return typeof a === 'string' ? a : a.value || a.address || '';
    }
    return '';
});

/** @type {import('vue').ComputedRef<string>} */
const copyright = computed(() => contacts.value?.copyright || '© РК ПРОФИ, 2026');

watch(
    contacts,
    (value) => {
        store.dispatch(settingsActions.hydrateFromInertia, value);
    },
    { immediate: true }
);

watch(
    [resolvedBodyClass, mobileMenuOpen],
    ([bodyClass, menuOpen]) => {
        document.body.className = [bodyClass, menuOpen ? 'popup-open' : '']
            .filter(Boolean)
            .join(' ');
    },
    { immediate: true }
);

/**
 * Обновить sticky header по скроллу.
 *
 * @returns {void}
 */
const onScroll = () => {
    fixedActive.value = window.pageYOffset > 80;
};

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    window.setTimeout(() => {
        document.querySelector('.preloader')?.classList.add('is-hidden');
    }, 400);
});

onUnmounted(() => {
    window.removeEventListener('scroll', onScroll);
    document.body.classList.remove('popup-open');
});

/** @returns {void} */
const openMenu = () => store.dispatch(uiActions.openMobileMenu);

/** @returns {void} */
const closeMenu = () => store.dispatch(uiActions.closeMobileMenu);
</script>

<template>
  <div>
    <header class="header" :class="headerClass">
      <div class="header__inner container">
        <Link :class="logoClass" href="/" prefetch aria-label="РК ПРОФИ">РК ПРОФИ</Link>
        <nav class="header__nav">
          <ul class="header__nav-list">
            <li v-for="item in mainMenu" :key="item.href" class="header__nav-item">
              <Link class="header__link" :href="item.href" prefetch>{{ item.label }}</Link>
            </li>
          </ul>
        </nav>
        <div class="header__right">
          <a v-if="email" class="header__link" :href="`mailto:${email}`">{{ email }}</a>
          <a v-if="phoneDisplay" class="header__link" :href="`tel:${phoneTel}`">{{ phoneDisplay }}</a>
          <button
            class="header__menu-btn _menuBtn"
            type="button"
            aria-label="Открыть меню"
            @click="openMenu"
          >
            <span class="header__menu-bars" aria-hidden="true">
              <span />
              <span />
              <span />
            </span>
          </button>
        </div>
      </div>
    </header>

    <!-- sticky header на body: иначе overflow/#app ломает position:fixed -->
    <Teleport to="body">
      <div
        class="header header--s header--fixed"
        :class="{ 'is-active': fixedActive }"
      >
        <div class="header__inner container">
          <Link class="header__logo header__logo--s" href="/" prefetch aria-label="РК ПРОФИ">РК ПРОФИ</Link>
          <nav class="header__nav">
            <ul class="header__nav-list">
              <li v-for="item in mainMenu" :key="`fixed-${item.href}`" class="header__nav-item">
                <Link class="header__link" :href="item.href" prefetch>{{ item.label }}</Link>
              </li>
            </ul>
          </nav>
          <div class="header__right">
            <a v-if="email" class="header__link" :href="`mailto:${email}`">{{ email }}</a>
            <a v-if="phoneDisplay" class="header__link" :href="`tel:${phoneTel}`">{{ phoneDisplay }}</a>
            <button
              class="header__menu-btn _menuBtn"
              type="button"
              aria-label="Открыть меню"
              @click="openMenu"
            >
              <span class="header__menu-bars" aria-hidden="true">
                <span />
                <span />
                <span />
              </span>
            </button>
          </div>
        </div>
      </div>
    </Teleport>

    <Teleport to="body">
      <div class="mobile-menu _menu" :class="{ 'mobile-menu--open': mobileMenuOpen }">
        <div class="mobile-menu__top container">
          <Link class="mobile-menu__top-logo" href="/" prefetch @click="closeMenu">РК ПРОФИ</Link>
          <div class="mobile-menu__top-links">
            <a v-if="email" class="mobile-menu__top-link" :href="`mailto:${email}`">{{ email }}</a>
            <a v-if="phoneDisplay" class="mobile-menu__top-link" :href="`tel:${phoneTel}`">{{ phoneDisplay }}</a>
          </div>
          <button
            class="mobile-menu__close _menuClose"
            type="button"
            aria-label="Закрыть меню"
            @click="closeMenu"
          >
            <span class="mobile-menu__close-x" aria-hidden="true" />
          </button>
        </div>
        <div class="mobile-menu__nav">
          <div class="container">
            <p class="mobile-menu__title">Навигация</p>
            <div class="mobile-menu__list">
              <Link
                v-for="item in mainMenu"
                :key="`m-${item.href}`"
                class="mobile-menu__item"
                :href="item.href"
                prefetch
                @click="closeMenu"
              >
                {{ item.label }}
              </Link>
            </div>
          </div>
        </div>
        <div class="mobile-menu__nav">
          <div class="container">
            <p class="mobile-menu__title">Для связи</p>
            <div class="mobile-menu__list">
              <a v-if="email" class="mobile-menu__item" :href="`mailto:${email}`">{{ email }}</a>
              <a v-if="phoneDisplay" class="mobile-menu__item" :href="`tel:${phoneTel}`">{{ phoneDisplay }}</a>
            </div>
          </div>
        </div>
      </div>
    </Teleport>

    <div class="wrapper">
      <main class="main">
        <slot />
      </main>

      <footer class="footer" id="contacts">
        <div class="container">
          <div class="footer__top">
            <div class="footer__contacts">
              <a v-if="phoneDisplay" class="footer__contact footer__link" :href="`tel:${phoneTel}`">{{ phoneDisplay }}</a>
              <a v-if="email" class="footer__contact footer__link" :href="`mailto:${email}`">{{ email }}</a>
            </div>
            <div class="footer__addresses">
              <div class="footer__address footer__section">
                <p class="footer__section-title">Адрес</p>
                <p class="footer__section-item">{{ address }}</p>
              </div>
            </div>
            <div class="footer__nav footer__section">
              <div class="footer__section-title">Навигация</div>
              <ul class="footer__section-list">
                <li v-for="item in mainMenu" :key="`f-${item.href}`" class="footer__section-item">
                  <Link class="footer__section-link footer__link" :href="item.href" prefetch>{{ item.label }}</Link>
                </li>
              </ul>
            </div>
          </div>
          <div class="footer__bottom">
            <div class="footer__copy">{{ copyright }}</div>
            <a
              class="footer__policy footer__link"
              href="/privacy"
              target="_blank"
              rel="noopener noreferrer"
            >Политика конфиденциальности</a>
          </div>
        </div>
      </footer>
    </div>

    <Teleport to="body">
      <div class="preloader" style="transition: opacity 0.5s ease-in-out;">
        <div class="preloader__inner">
          <div class="preloader__logo">РК ПРОФИ</div>
          <div class="preloader__progress">
            <div class="preloader__bar" style="width: 100%;" />
          </div>
        </div>
      </div>
    </Teleport>

    <RequestForm />
  </div>
</template>
