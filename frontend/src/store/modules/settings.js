/**
 * Vuex-модуль настроек сайта (контакты для шапки/футера).
 */
import settingsApi from '@/api/modules/settings';

/**
 * @typedef {object} SettingsState
 * @property {object|null} contacts Контакты сайта
 * @property {boolean} isLoaded Загружены ли контакты через API
 */

/** @type {SettingsState} */
const state = {
    /** Контакты (телефоны, emails, адреса) */
    contacts: null,
    /** Признак успешной загрузки через API */
    isLoaded: false,
};

export const mutationTypes = {
    setContacts: '[settings] setContacts',
};

export const actionTypes = {
    fetchContacts: '[settings] fetchContacts',
    hydrateFromInertia: '[settings] hydrateFromInertia',
};

export const gettersTypes = {
    contacts: '[settings] contacts',
    phones: '[settings] phones',
    emails: '[settings] emails',
};

const getters = {
    /**
     * Полный объект контактов.
     *
     * @param {SettingsState} state Состояние
     * @returns {object|null}
     */
    [gettersTypes.contacts]: (state) => state.contacts,

    /**
     * Список телефонов.
     *
     * @param {SettingsState} state Состояние
     * @returns {string[]}
     */
    [gettersTypes.phones]: (state) => state.contacts?.phones ?? [],

    /**
     * Список email.
     *
     * @param {SettingsState} state Состояние
     * @returns {string[]}
     */
    [gettersTypes.emails]: (state) => state.contacts?.emails ?? [],
};

const mutations = {
    /**
     * Записать контакты в store.
     *
     * @param {SettingsState} state Состояние
     * @param {object|null} contacts Контакты
     * @returns {void}
     */
    [mutationTypes.setContacts](state, contacts) {
        state.contacts = contacts;
        state.isLoaded = Boolean(contacts);
    },
};

const actions = {
    /**
     * Загрузить контакты через REST API.
     *
     * @param {{ commit: Function }} ctx Контекст Vuex
     * @returns {Promise<object>}
     */
    [actionTypes.fetchContacts]({ commit }) {
        return settingsApi.contacts().then((response) => {
            const data = response.data?.data ?? response.data;
            commit(mutationTypes.setContacts, data);
            return data;
        });
    },

    /**
     * Подставить контакты из Inertia shared props.
     *
     * @param {{ commit: Function }} ctx Контекст Vuex
     * @param {object|null} contacts Контакты из share()
     * @returns {void}
     */
    [actionTypes.hydrateFromInertia]({ commit }, contacts) {
        if (contacts) {
            commit(mutationTypes.setContacts, contacts);
        }
    },
};

export default {
    state,
    getters,
    mutations,
    actions,
};
