/**
 * Vuex-модуль UI: мобильное меню, sticky header и модалка заявки.
 */

/**
 * @typedef {object} UiState
 * @property {boolean} mobileMenuOpen Открыто ли мобильное меню
 * @property {boolean} requestModalOpen Открыта ли модалка заявки
 * @property {boolean} fixedHeaderActive Активен ли fixed header
 */

/** @type {UiState} */
const state = {
    /** Открыто ли мобильное меню */
    mobileMenuOpen: false,
    /** Открыта ли модалка заявки */
    requestModalOpen: false,
    /** Показан ли sticky header после скролла */
    fixedHeaderActive: false,
};

export const mutationTypes = {
    setMobileMenu: '[ui] setMobileMenu',
    setRequestModal: '[ui] setRequestModal',
    setFixedHeader: '[ui] setFixedHeader',
};

export const actionTypes = {
    openMobileMenu: '[ui] openMobileMenu',
    closeMobileMenu: '[ui] closeMobileMenu',
    openRequestModal: '[ui] openRequestModal',
    closeRequestModal: '[ui] closeRequestModal',
};

const mutations = {
    /**
     * Установить состояние мобильного меню.
     *
     * @param {UiState} state Состояние
     * @param {boolean} open Открыто ли
     * @returns {void}
     */
    [mutationTypes.setMobileMenu](state, open) {
        state.mobileMenuOpen = open;
    },

    /**
     * Установить состояние модалки заявки.
     *
     * @param {UiState} state Состояние
     * @param {boolean} open Открыта ли
     * @returns {void}
     */
    [mutationTypes.setRequestModal](state, open) {
        state.requestModalOpen = open;
    },

    /**
     * Установить активность sticky header.
     *
     * @param {UiState} state Состояние
     * @param {boolean} active Активен ли
     * @returns {void}
     */
    [mutationTypes.setFixedHeader](state, active) {
        state.fixedHeaderActive = active;
    },
};

const actions = {
    /**
     * Открыть мобильное меню.
     *
     * @param {{ commit: Function }} ctx Контекст
     * @returns {void}
     */
    [actionTypes.openMobileMenu]({ commit }) {
        commit(mutationTypes.setMobileMenu, true);
    },

    /**
     * Закрыть мобильное меню.
     *
     * @param {{ commit: Function }} ctx Контекст
     * @returns {void}
     */
    [actionTypes.closeMobileMenu]({ commit }) {
        commit(mutationTypes.setMobileMenu, false);
    },

    /**
     * Открыть модалку заявки.
     *
     * @param {{ commit: Function }} ctx Контекст
     * @returns {void}
     */
    [actionTypes.openRequestModal]({ commit }) {
        commit(mutationTypes.setRequestModal, true);
        document.body.classList.add('popup-open', 'popup-blur-active');
    },

    /**
     * Закрыть модалку заявки.
     *
     * @param {{ commit: Function }} ctx Контекст
     * @returns {void}
     */
    [actionTypes.closeRequestModal]({ commit }) {
        commit(mutationTypes.setRequestModal, false);
        document.body.classList.remove('popup-open', 'popup-blur-active');
    },
};

export default {
    state,
    mutations,
    actions,
};
