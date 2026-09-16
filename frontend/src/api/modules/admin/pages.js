/**
 * API-модуль админки: контентные страницы (CMS).
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Список страниц.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const index = () => axios.get(urls.adminPages);

/**
 * Обновить страницу.
 *
 * @param {number|string} id ID страницы
 * @param {Record<string, unknown>} payload title / blocks / is_active
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const update = (id, payload) => axios.put(urls.adminPage(id), payload);

/**
 * Загрузить медиа страницы (image|video), получить URL.
 *
 * @param {number|string} id ID страницы
 * @param {File} file Файл
 * @param {'image'|'video'} kind Тип
 * @param {string} [field='image'] Ключ поля
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const uploadMedia = (id, file, kind = 'image', field = 'image') => {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('kind', kind);
    formData.append('field', field);
    return axios.post(urls.adminPageImages(id), formData);
};

/**
 * @deprecated Используйте uploadMedia
 * @param {number|string} id
 * @param {File} file
 * @param {string} [field]
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const uploadImage = (id, file, field = 'image') => uploadMedia(id, file, 'image', field);

export default {
    index,
    update,
    uploadMedia,
    uploadImage,
};
