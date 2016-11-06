import VueRouter from 'vue-router';
import Translations from './pages/translations.vue';
import Group from './pages/group.vue';
import Dashboard from './pages/dashboard.vue';

let routes = [
    {
        path: '/strings',
        name: 'translations',
        component: Translations,
        meta: { manager: 'laravel' },
        children: [
            {
                path: '/strings/:group?',
                name: 'group',
                component: Group,
                meta: { manager: 'laravel' }
            }
        ]
    },
    {
        path: '',
        name: 'dashboard',
        component: Dashboard
    }
];

export default new VueRouter({
    mode: 'hash',
    routes: routes
})