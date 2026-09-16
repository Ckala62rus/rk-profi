import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
/** Корень Vue-приложения (отдельная папка frontend/) */
const frontendSrc = path.resolve(__dirname, '../../frontend/src');
/** node_modules Laravel-проекта (сборка идёт отсюда) */
const nm = path.resolve(__dirname, 'node_modules');

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/js/public.js', 'resources/js/admin.js'],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': frontendSrc,
            vue: path.join(nm, 'vue'),
            vuex: path.join(nm, 'vuex'),
            axios: path.join(nm, 'axios'),
            '@inertiajs/vue3': path.join(nm, '@inertiajs/vue3'),
            'element-plus': path.join(nm, 'element-plus'),
            sweetalert2: path.join(nm, 'sweetalert2'),
            toastr: path.join(nm, 'toastr'),
            jquery: path.join(nm, 'jquery'),
            '@element-plus/icons-vue': path.join(nm, '@element-plus/icons-vue'),
            'laravel-vite-plugin/inertia-helpers': path.join(
                nm,
                'laravel-vite-plugin/inertia-helpers'
            ),
        },
        dedupe: ['vue', '@inertiajs/vue3', 'vuex', 'axios'],
    },
    server: {
        host: '0.0.0.0',
        port: 5173,
        fs: {
            allow: [
                __dirname,
                path.resolve(__dirname, '../../frontend'),
            ],
        },
        hmr: {
            host: 'localhost',
        },
    },
});
