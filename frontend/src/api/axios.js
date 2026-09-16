/**
 * Настроенный экземпляр axios для REST API `/api/v1`.
 * Добавляет Bearer-токен из localStorage и обрабатывает 401.
 */
import axios from 'axios';
import { getItem, removeItem } from '@/helpers/persistenceStorage';

const env = import.meta.env;

/** Базовый URL API (например http://localhost:8090/api) */
axios.defaults.baseURL = env.VITE_BACKEND_API || '/api';

/**
 * Перехватчик запросов: подставляет Authorization Bearer.
 *
 * @param {import('axios').InternalAxiosRequestConfig} config Конфиг запроса
 * @returns {import('axios').InternalAxiosRequestConfig}
 */
axios.interceptors.request.use((config) => {
    const token = getItem('access_token');
    if (token) {
        config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
});

/**
 * Перехватчик ответов: при 401 очищает токен и уводит на логин админки.
 *
 * @param {import('axios').AxiosError} error Ошибка ответа
 * @returns {Promise<never>}
 */
axios.interceptors.response.use(undefined, (error) => {
    const location = window.location.pathname;

    if (error.response?.status === 401) {
        if (location !== '/admin/login') {
            removeItem('access_token');
            delete axios.defaults.headers.common.Authorization;
            window.location.href = '/admin/login';
        }
    }

    return Promise.reject(error);
});

export default axios;
