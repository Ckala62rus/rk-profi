/**
 * API-модуль публичных настроек сайта.
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Получить контакты для шапки/футера.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const contacts = () => axios.get(urls.settingsContacts);

export default {
    contacts,
};
