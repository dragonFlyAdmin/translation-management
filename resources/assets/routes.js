import VueRouter from 'vue-router';
import Translations from './pages/translations.vue';
import Group from './pages/group.vue';
import Dashboard from './pages/dashboard.vue';

export default new VueRouter({
    mode: 'hash',
    routes: [
        {
            path: '/translations',
            name: 'translations',
            component: Translations,
            children: [
                {
                    path: '/translations/:group?',
                    name: 'group',
                    component: Group
                }
            ]
        },
        {
            path: '',
            name: 'dashboard',
            component: Dashboard
        }
    ]
})