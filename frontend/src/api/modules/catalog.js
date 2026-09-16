/**
 * API-модуль публичного каталога.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Список категорий.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const categories = () => axios.get(urls.catalogCategories);

/**
 * Товары категории по слагу.
 *
 * @param {string} slug Слаг категории
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const categoryProducts = (slug) => axios.get(urls.catalogCategoryProducts(slug));

/**
 * Карточка товара по слагу.
 *
 * @param {string} slug Слаг товара
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const product = (slug) => axios.get(urls.catalogProduct(slug));

export default {
    categories,
    categoryProducts,
    product,
};
