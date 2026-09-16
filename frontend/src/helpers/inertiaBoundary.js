/**
 * Жёсткая граница между публичным и admin Inertia-приложениями.
 * Переход через границу — полная перезагрузка документа (другой Blade + CSS).
 */
import { router } from '@inertiajs/vue3';

/**
 * Путь относится к админке.
 *
 * @param {string} pathname Путь URL
 * @returns {boolean}
 */
export function isAdminPath(pathname) {
    return String(pathname || '').startsWith('/admin');
}

/**
 * Достаёт pathname из visit.url Inertia.
 *
 * @param {string|URL} url URL визита
 * @returns {string}
 */
function visitPathname(url) {
    if (typeof url === 'string') {
        try {
            return new URL(url, window.location.origin).pathname;
        } catch {
            return url.split('?')[0] || '';
        }
    }
    if (url && typeof url === 'object' && 'pathname' in url) {
        return String(url.pathname || '');
    }
    return window.location.pathname;
}

/**
 * Блокирует Inertia-визиты «чужой» зоны и делает location.assign.
 *
 * @param {boolean} isAdminApp true = текущее приложение — админка
 * @returns {void}
 */
export function guardInertiaBoundary(isAdminApp) {
    router.on('before', (event) => {
        const visit = event.detail?.visit;
        if (!visit) {
            return;
        }
        const pathname = visitPathname(visit.url);
        const targetIsAdmin = isAdminPath(pathname);
        if (targetIsAdmin === isAdminApp) {
            return;
        }
        event.preventDefault();
        const href =
            typeof visit.url === 'string'
                ? visit.url
                : visit.url?.href || pathname;
        window.location.assign(href);
    });
}
