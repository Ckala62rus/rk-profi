/**
 * Вспомогательные функции для работы с localStorage.
 * Обеспечивают безопасное сохранение и получение данных.
 */

/**
 * Сохранить значение в localStorage.
 *
 * @param {string} key Ключ
 * @param {unknown} value Значение (будет сериализовано в JSON)
 * @returns {void}
 */
export const setItem = (key, value) => {
    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (e) {
        console.error('Ошибка при сохранении в localStorage', e);
    }
};

/**
 * Получить значение из localStorage.
 *
 * @param {string} key Ключ
 * @returns {any|null} Значение или null
 */
export const getItem = (key) => {
    try {
        const item = localStorage.getItem(key);
        return item ? JSON.parse(item) : null;
    } catch (e) {
        console.error('Ошибка при чтении из localStorage', e);
        return null;
    }
};

/**
 * Удалить значение из localStorage.
 *
 * @param {string} key Ключ
 * @returns {void}
 */
export const removeItem = (key) => {
    try {
        localStorage.removeItem(key);
    } catch (e) {
        console.error('Ошибка при удалении из localStorage', e);
    }
};

/**
 * Очистить весь localStorage.
 *
 * @returns {void}
 */
export const clear = () => {
    try {
        localStorage.clear();
    } catch (e) {
        console.error('Ошибка при очистке localStorage', e);
    }
};
