/**
 * API-модуль админки: категории каталога.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Собирает FormData из полей категории (+ файл image).
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
        if (key === 'image') {
            if (value instanceof File) {
                formData.append('image', value);
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
 * Список категорий (включая неактивные).
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const index = () => axios.get(urls.adminCategories);

/**
 * Создать категорию (multipart при наличии файла).
 *
 * @param {Record<string, unknown>} payload Поля категории
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const store = (payload) => {
    if (payload.image instanceof File) {
        return axios.post(urls.adminCategories, toFormData(payload));
    }
    return axios.post(urls.adminCategories, payload);
};

/**
 * Обновить категорию.
 *
 * @param {number|string} id ID категории
 * @param {Record<string, unknown>} payload Поля категории
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const update = (id, payload) => {
    if (payload.image instanceof File) {
        // POST + _method=PUT — multipart через method spoofing Laravel
        const formData = toFormData(payload);
        formData.append('_method', 'PUT');
        return axios.post(urls.adminCategory(id), formData);
    }
    return axios.put(urls.adminCategory(id), payload);
};

/**
 * Удалить категорию.
 *
 * @param {number|string} id ID категории
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const destroy = (id) => axios.delete(urls.adminCategory(id));

export default {
    index,
    store,
    update,
    destroy,
};
