/**
 * Константы URL для API-запросов РК ПРОФИ.
 * Все пути централизованы здесь — не дублировать строки в компонентах.
 */
const urls = {
    // Публичное API
    settingsContacts: '/v1/settings/contacts',
    page: (slug) => `/v1/pages/${slug}`,
    catalogCategories: '/v1/catalog/categories',
    catalogCategoryProducts: (slug) => `/v1/catalog/categories/${slug}/products`,
    catalogProduct: (slug) => `/v1/catalog/products/${slug}`,
    leads: '/v1/leads',

    // Админ: авторизация
    adminLogin: '/v1/admin/login',
    adminMe: '/v1/admin/me',
    adminLogout: '/v1/admin/logout',

    // Админ: CRUD
    adminLeads: '/v1/admin/leads',
    adminLead: (id) => `/v1/admin/leads/${id}`,
    adminCategories: '/v1/admin/categories',
    adminCategory: (id) => `/v1/admin/categories/${id}`,
    adminProducts: '/v1/admin/products',
    adminProduct: (id) => `/v1/admin/products/${id}`,
    adminProductMedia: (productId, mediaId) => `/v1/admin/products/${productId}/media/${mediaId}`,
    adminPages: '/v1/admin/pages',
    adminPage: (id) => `/v1/admin/pages/${id}`,
    adminPageImages: (id) => `/v1/admin/pages/${id}/images`,
    adminSettingsContacts: '/v1/admin/settings/contacts',
};

export default urls;
