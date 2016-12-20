import VueRouter from 'vue-router';
import Dashboard from './pages/dashboard.vue';
import _ from 'lodash';

let defaultRoutes = [
    {
        path: '',
        name: 'dashboard',
        component: Dashboard
    }
];

export default (routes) => {
    _.each(routes, (route) => { defaultRoutes.push(route) });

    return new VueRouter({
        mode: 'hash',
        routes: defaultRoutes
    })
}