/**
 * Обёртка над Toastr для уведомлений в админке.
 */
import toastr from 'toastr';

toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: 'toast-top-right',
    timeOut: 3500,
    extendedTimeOut: 1500,
};

/**
 * Показать успешное уведомление.
 *
 * @param {string} message Текст
 * @returns {void}
 */
const success = (message) => {
    toastr.success(message);
};

/**
 * Показать ошибку.
 *
 * @param {string} message Текст
 * @returns {void}
 */
const error = (message) => {
    toastr.error(message);
};

/**
 * Информационное уведомление.
 *
 * @param {string} message Текст
 * @returns {void}
 */
const info = (message) => {
    toastr.info(message);
};

/**
 * Предупреждение.
 *
 * @param {string} message Текст
 * @returns {void}
 */
const warning = (message) => {
    toastr.warning(message);
};

export default {
    success,
    error,
    info,
    warning,
};
