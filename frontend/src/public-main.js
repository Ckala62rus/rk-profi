/**
 * Точка входа публичного сайта (Inertia).
 * Metronic сюда не подключается — только CSS шаблона из public.blade.php.
 */
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import store from '@/store';
import '@/styles/public.css';
import { guardInertiaBoundary } from '@/helpers/inertiaBoundary';

/** Страницы сразу в бандле — без доп. запросов чанков при переходах */
const pages = import.meta.glob('./pages/Public/**/*.vue', { eager: true });

createInertiaApp({
    /**
     * @param {string} name Имя страницы (Public/…)
     * @returns {object}
     */
    resolve: (name) => {
        const page = pages[`./pages/${name}.vue`];
        if (!page) {
            throw new Error(`Inertia page not found: ${name}`);
        }
        return page.default ?? page;
    },

    /**
     * @param {{ el: Element, App: object, props: object, plugin: object }} ctx
     * @returns {void}
     */
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(store);
        app.mount(el);
        guardInertiaBoundary(false);
    },

    /**
     * @param {string} title
     * @returns {string}
     */
    title: (title) => (title ? `${title} — РК ПРОФИ` : 'РК ПРОФИ'),

    progress: {
        delay: 80,
        color: '#1a1a1a',
        includeCSS: true,
        showSpinner: false,
    },
});
