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

/** Фотографии категорий по умолчанию */
export const DEMO_CATEGORY_IMAGES = [
    '/images/defaults/category-ppe.jpg',
    '/images/defaults/category-workwear.jpg',
    '/images/defaults/category-haberdashery.jpg',
    '/images/defaults/category-bath.jpg',
    '/images/defaults/category-shoes.jpg',
    '/images/defaults/category-sheepskin.jpg',
];

/** Дефолты CMS-страниц */
export const DEMO_PAGE_IMAGES = {
    hero: '/images/defaults/hero.jpg',
    homeAbout: '/images/defaults/home-about.jpg',
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
