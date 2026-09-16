/**
 * API-модуль авторизации администратора (Sanctum Bearer).
 */
import axios from '@/api/axios';
import urls from '@/api/urls';

/**
 * Вход администратора.
 *
 * @param {{ email: string, password: string }} credentials Учётные данные
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const login = (credentials) => axios.post(urls.adminLogin, credentials);

/**
 * Текущий пользователь по токену.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const me = () => axios.get(urls.adminMe);

/**
 * Выход: отзыв токена на сервере.
 *
 * @returns {Promise<import('axios').AxiosResponse>}
 */
const logout = () => axios.post(urls.adminLogout);

export default {
    login,
    me,
    logout,
};
