/**
 * Точка входа админки (Inertia + Metronic).
 * CSS Metronic — в admin.blade.php, JS подключается из layout.
 */
import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import ElementPlus from 'element-plus';
import store from '@/store';
import '@/styles/admin.css';
import { guardInertiaBoundary } from '@/helpers/inertiaBoundary';
import 'element-plus/dist/index.css';
import 'sweetalert2/dist/sweetalert2.min.css';
import 'toastr/build/toastr.min.css';
import jquery from 'jquery';

// Toastr ожидает jQuery в window
window.$ = window.jQuery = jquery;

createInertiaApp({
    /**
     * @param {string} name Имя страницы (Admin/…)
     * @returns {Promise<unknown>}
     */
    resolve: (name) =>
        resolvePageComponent(
            `./pages/${name}.vue`,
            import.meta.glob('./pages/Admin/**/*.vue')
        ),

    /**
     * @param {{ el: Element, App: object, props: object, plugin: object }} ctx
     * @returns {void}
     */
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.use(plugin);
        app.use(ElementPlus);
        app.use(store);
        app.mount(el);
        guardInertiaBoundary(true);
    },

    /**
     * @param {string} title
     * @returns {string}
     */
    title: (title) => (title ? `${title} — Админка` : 'Админка — РК ПРОФИ'),
});
