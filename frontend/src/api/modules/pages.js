/**
 * API-модуль публичных страниц (блоки home/about/…).
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Контент страницы по слагу.
 *
 * @param {string} slug Слаг страницы
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const get = (slug) => axios.get(urls.page(slug));

export default {
    get,
};
