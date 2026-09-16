/**
 * API-модуль админки: заявки.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Список заявок.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const index = () => axios.get(urls.adminLeads);

/**
 * Обновить статус/поля заявки.
 *
 * @param {number|string} id ID заявки
 * @param {Record<string, unknown>} payload Поля для PATCH
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const update = (id, payload) => axios.patch(urls.adminLead(id), payload);

export default {
    index,
    update,
};
