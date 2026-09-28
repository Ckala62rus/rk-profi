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

/**
 * Повторно поставить в очередь уведомление о заявке с ошибкой доставки.
 *
 * @param {number|string} id ID заявки
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const retryEmail = (id) => axios.post(urls.adminLeadRetryEmail(id));

export default {
    index,
    update,
    retryEmail,
};
