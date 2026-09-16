/**
 * API-модуль публичных заявок.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Отправить заявку (multipart: поля + файлы).
 *
 * Важно: не задавать Content-Type вручную — иначе пропадёт boundary
 * и PHP не распарсит файлы.
 *
 * @param {FormData} formData Данные формы заявки
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const create = (formData) => axios.post(urls.leads, formData);

export default {
    create,
};
