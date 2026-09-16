/**
 * Vuex-модуль авторизации администратора.
 * Хранит пользователя и access_token (токен — в localStorage).
 */
import authApi from '@/api/modules/auth';
import { setItem, removeItem, getItem } from '@/helpers/persistenceStorage';

/**
 * Состояние модуля auth.
 *
 * @typedef {object} AuthState
 * @property {object|null} user Текущий администратор
 * @property {boolean} isSubmitting Идёт ли запрос login/me
 * @property {boolean} isAuth Авторизован ли пользователь
 * @property {string[]} authError Ошибки авторизации
 */

/** @type {AuthState} */
const state = {
    /** Текущий администратор */
    user: null,
    /** Флаг отправки формы входа */
    isSubmitting: false,
    /** Признак успешной авторизации */
    isAuth: Boolean(getItem('access_token')),
    /** Тексты ошибок входа */
    authError: [],
};

export const mutationTypes = {
    loginStart: '[auth] loginStart',
    loginSuccess: '[auth] loginSuccess',
    loginFail: '[auth] loginFail',
    meSuccess: '[auth] meSuccess',
    logout: '[auth] logout',
};

export const actionTypes = {
    login: '[auth] login',
    me: '[auth] me',
    logout: '[auth] logout',
};

export const gettersTypes = {
    isAuth: '[auth] isAuth',
    user: '[auth] user',
};

const getters = {
    /**
     * Признак авторизации.
     *
     * @param {AuthState} state Состояние модуля
     * @returns {boolean}
     */
    [gettersTypes.isAuth]: (state) => state.isAuth,

    /**
     * Текущий пользователь.
     *
     * @param {AuthState} state Состояние модуля
     * @returns {object|null}
     */
    [gettersTypes.user]: (state) => state.user,
};

const mutations = {
    /**
     * Старт запроса входа.
     *
     * @param {AuthState} state Состояние модуля
     * @returns {void}
     */
    [mutationTypes.loginStart](state) {
        state.isSubmitting = true;
        state.authError = [];
    },

    /**
     * Успешный вход: сохранить пользователя.
     *
     * @param {AuthState} state Состояние модуля
     * @param {object} user Данные пользователя
     * @returns {void}
     */
    [mutationTypes.loginSuccess](state, user) {
        state.isSubmitting = false;
        state.isAuth = true;
        state.user = user;
    },

    /**
     * Ошибка входа.
     *
     * @param {AuthState} state Состояние модуля
     * @param {string[]} errors Список ошибок
     * @returns {void}
     */
    [mutationTypes.loginFail](state, errors) {
        state.isSubmitting = false;
        state.isAuth = false;
        state.authError = errors;
    },

    /**
     * Успешный ответ /me.
     *
     * @param {AuthState} state Состояние модуля
     * @param {object} user Данные пользователя
     * @returns {void}
     */
    [mutationTypes.meSuccess](state, user) {
        state.user = user;
        state.isAuth = true;
    },

    /**
     * Выход из системы.
     *
     * @param {AuthState} state Состояние модуля
     * @returns {void}
     */
    [mutationTypes.logout](state) {
        state.user = null;
        state.isAuth = false;
    },
};

const actions = {
    /**
     * Выполнить вход и сохранить access_token.
     *
     * @param {{ commit: Function }} ctx Контекст Vuex
     * @param {{ email: string, password: string }} payload Учётные данные
     * @returns {Promise<object>}
     */
    [actionTypes.login]({ commit }, payload) {
        return new Promise((resolve, reject) => {
            commit(mutationTypes.loginStart);
            authApi
                .login(payload)
                .then((response) => {
                    const data = response.data?.data ?? response.data;
                    setItem('access_token', data.access_token);
                    commit(mutationTypes.loginSuccess, data.user);
                    resolve(data);
                })
                .catch((error) => {
                    const errors =
                        error.response?.data?.errors?.email ||
                        [error.response?.data?.message || 'Ошибка входа'];
                    commit(mutationTypes.loginFail, errors);
                    reject(error);
                });
        });
    },

    /**
     * Загрузить текущего пользователя по токену.
     *
     * @param {{ commit: Function }} ctx Контекст Vuex
     * @returns {Promise<object>}
     */
    [actionTypes.me]({ commit }) {
        return authApi.me().then((response) => {
            const user = response.data?.data ?? response.data;
            commit(mutationTypes.meSuccess, user);
            return user;
        });
    },

    /**
     * Выйти: отозвать токен и очистить localStorage.
     *
     * @param {{ commit: Function }} ctx Контекст Vuex
     * @returns {Promise<void>}
     */
    [actionTypes.logout]({ commit }) {
        return authApi
            .logout()
            .catch(() => null)
            .finally(() => {
                removeItem('access_token');
                commit(mutationTypes.logout);
            });
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
