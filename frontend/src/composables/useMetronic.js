/**
 * JS Metronic (demo1) для админки.
 * CSS подключается статически в admin.blade.php — сюда не трогаем.
 */

const METRONIC_JS = [
    '/metronic/plugins/global/plugins.bundle.js',
    '/metronic/js/scripts.bundle.js',
];

/** Маркер на script */
const METRONIC_ATTR = 'data-rk-metronic';

/** JS уже загружен */
let jsReady = false;
/** Промис загрузки JS */
let jsPromise = null;

/**
 * Загрузить скрипт Metronic один раз.
 *
 * @param {string} src URL скрипта
 * @returns {Promise<void>}
 */
function loadScript(src) {
    return new Promise((resolve, reject) => {
        const existing = document.querySelector(`script[src="${src}"]`);
        if (existing) {
            existing.setAttribute(METRONIC_ATTR, '1');
            resolve();
            return;
        }
        const script = document.createElement('script');
        script.src = src;
        script.async = false;
        script.setAttribute(METRONIC_ATTR, '1');
        script.onload = () => resolve();
        script.onerror = () => reject(new Error(`Не удалось загрузить ${src}`));
        document.body.appendChild(script);
    });
}

/**
 * Подключить JS Metronic и дождаться загрузки.
 *
 * @returns {Promise<void>}
 */
export function ensureMetronicJs() {
    if (jsReady) {
        return Promise.resolve();
    }
    if (!jsPromise) {
        jsPromise = METRONIC_JS.reduce(
            (chain, src) => chain.then(() => loadScript(src)),
            Promise.resolve()
        ).then(() => {
            jsReady = true;
        });
    }
    return jsPromise;
}

/**
 * Классы body для layout с aside (как в demo1).
 *
 * @type {string}
 */
export const METRONIC_BODY_CLASS =
    'header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed';

/**
 * Классы body для страницы логина.
 *
 * @type {string}
 */
export const METRONIC_AUTH_BODY_CLASS =
    'bg-white header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed';

/**
 * Применить body-классы Metronic.
 *
 * @param {string} className Классы
 * @returns {void}
 */
export function applyMetronicBody(className) {
    document.body.id = 'kt_body';
    document.body.className = className;
    document.body.style.setProperty('--kt-toolbar-height', '55px');
    document.body.style.setProperty('--kt-toolbar-height-tablet-and-mobile', '55px');
}

/**
 * Сбросить body-классы Metronic.
 *
 * @returns {void}
 */
export function clearMetronicBody() {
    if (document.body.id === 'kt_body') {
        document.body.removeAttribute('id');
    }
    document.body.classList.remove(
        'bg-white',
        'header-fixed',
        'header-tablet-and-mobile-fixed',
        'toolbar-enabled',
        'toolbar-fixed',
        'toolbar-tablet-and-mobile-fixed',
        'aside-enabled',
        'aside-fixed'
    );
    document.body.style.removeProperty('--kt-toolbar-height');
    document.body.style.removeProperty('--kt-toolbar-height-tablet-and-mobile');
    document.body.style.removeProperty('padding-right');
}

/**
 * Переинициализировать виджеты Metronic после смены Inertia-страницы.
 *
 * @returns {void}
 */
export function reinitMetronic() {
    const win = /** @type {any} */ (window);
    try {
        if (win.KTMenu?.createInstances) {
            win.KTMenu.createInstances();
        }
        if (win.KTToggle?.createInstances) {
            win.KTToggle.createInstances();
        }
        if (win.KTDrawer?.createInstances) {
            win.KTDrawer.createInstances();
        }
        if (win.KTScroll?.createInstances) {
            win.KTScroll.createInstances();
        }
    } catch (e) {
        console.warn('Metronic reinit', e);
    }
}
