/**
 * API-модуль админки: настройки контактов.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Получить контакты для редактирования.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const showContacts = () => axios.get(urls.adminSettingsContacts);

/**
 * Сохранить контакты.
 *
 * @param {Record<string, unknown>} payload Контакты и связанные поля
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const updateContacts = (payload) => axios.put(urls.adminSettingsContacts, payload);

export default {
    showContacts,
    updateContacts,
};
