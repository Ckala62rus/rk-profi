/**
 * Демо-плейсхолдеры каталога (если админ не загрузил фото).
 */

/** SVG перчаток для товаров */
export const DEMO_PRODUCT_IMAGES = [
    '/template/demo/gloves/glove-01.svg',
    '/template/demo/gloves/glove-02.svg',
    '/template/demo/gloves/glove-03.svg',
    '/template/demo/gloves/glove-04.svg',
    '/template/demo/gloves/glove-05.svg',
    '/template/demo/gloves/glove-06.svg',
    '/template/demo/gloves/glove-07.svg',
    '/template/demo/gloves/glove-08.svg',
];

/** JPG/SVG категорий */
export const DEMO_CATEGORY_IMAGES = [
    '/template/demo/categories/cat-01.svg',
    '/template/demo/categories/cat-02.svg',
    '/template/demo/categories/cat-03.svg',
    '/template/demo/categories/cat-04.svg',
    '/template/demo/categories/cat-05.svg',
    '/template/demo/categories/cat-06.svg',
];

/** Дефолты CMS-страниц */
export const DEMO_PAGE_IMAGES = {
    hero: '/template/demo/pages/hero.svg',
    homeAbout: '/template/demo/pages/home-about.svg',
};

/**
 * Демо-картинка товара по индексу/id.
 *
 * @param {number} [index=0]
 * @returns {string}
 */
export const demoProductImage = (index = 0) =>
    DEMO_PRODUCT_IMAGES[Math.abs(Number(index) || 0) % DEMO_PRODUCT_IMAGES.length];

/**
 * Демо-картинка категории по индексу/id.
 *
 * @param {number} [index=0]
 * @returns {string}
 */
export const demoCategoryImage = (index = 0) =>
    DEMO_CATEGORY_IMAGES[Math.abs(Number(index) || 0) % DEMO_CATEGORY_IMAGES.length];

/**
 * URL превью товара с fallback на демо.
 *
 * @param {object} product
 * @param {number} [index=0]
 * @returns {string}
 */
export const productThumbOrDemo = (product, index = 0) =>
    product?.thumb_url || demoProductImage(product?.id || index);

/**
 * URL категории с fallback на демо.
 *
 * @param {object} category
 * @param {number} [index=0]
 * @returns {string}
 */
export const categoryImageOrDemo = (category, index = 0) =>
    category?.image_url || demoCategoryImage(category?.id || index);
