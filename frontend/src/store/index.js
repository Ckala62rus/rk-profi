/**
 * Корневой Vuex store приложения РК ПРОФИ.
 */
import { createStore } from 'vuex';
import auth from '@/store/modules/auth';
import settings from '@/store/modules/settings';
import ui from '@/store/modules/ui';

const store = createStore({
    modules: {
        auth,
        settings,
        ui,
    },
});

export default store;
