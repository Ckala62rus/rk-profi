/**
 * API-модуль админки: товары.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Собирает FormData из полей товара (+ thumb / gallery файлы).
 *
 * @param {Record<string, unknown>} payload Поля формы
 * @returns {FormData}
 */
const toFormData = (payload) => {
    const formData = new FormData();
    Object.entries(payload).forEach(([key, value]) => {
        if (value === undefined || value === null) {
            return;
        }
        if (key === 'thumb') {
            if (value instanceof File) {
                formData.append('thumb', value);
            }
            return;
        }
        if (key === 'gallery') {
            if (Array.isArray(value)) {
                value.forEach((file) => {
                    if (file instanceof File) {
                        formData.append('gallery[]', file);
                    }
                });
            }
            return;
        }
        if (typeof value === 'boolean') {
            formData.append(key, value ? '1' : '0');
            return;
        }
        formData.append(key, String(value));
    });
    return formData;
};

/**
 * Нужен ли multipart (есть файлы).
 *
 * @param {Record<string, unknown>} payload
 * @returns {boolean}
 */
const hasFiles = (payload) =>
    payload.thumb instanceof File
    || (Array.isArray(payload.gallery) && payload.gallery.some((f) => f instanceof File));

/**
 * Список товаров.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const index = () => axios.get(urls.adminProducts);

/**
 * Создать товар.
 *
 * @param {Record<string, unknown>} payload Поля товара
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const store = (payload) => {
    if (hasFiles(payload)) {
        return axios.post(urls.adminProducts, toFormData(payload));
    }
    return axios.post(urls.adminProducts, payload);
};

/**
 * Обновить товар.
 *
 * @param {number|string} id ID товара
 * @param {Record<string, unknown>} payload Поля товара
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const update = (id, payload) => {
    if (hasFiles(payload)) {
        const formData = toFormData(payload);
        formData.append('_method', 'PUT');
        return axios.post(urls.adminProduct(id), formData);
    }
    return axios.put(urls.adminProduct(id), payload);
};

/**
 * Удалить товар.
 *
 * @param {number|string} id ID товара
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const destroy = (id) => axios.delete(urls.adminProduct(id));

/**
 * Удалить файл галереи товара.
 *
 * @param {number|string} productId ID товара
 * @param {number|string} mediaId ID media
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const destroyMedia = (productId, mediaId) =>
    axios.delete(urls.adminProductMedia(productId, mediaId));

export default {
    index,
    store,
    update,
    destroy,
    destroyMedia,
};
