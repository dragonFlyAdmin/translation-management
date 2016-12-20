import Vue from 'vue';
import VueRouter from 'vue-router';
import VueResource from 'vue-resource';
import _ from 'lodash';


// Setup vue resource
Vue.use(VueResource);

Vue.http.options.emulateJSON = true;
Vue.http.interceptors.push((request, next) => {
    request.headers.set('X-CSRF-TOKEN', window.csrfToken);

    next();
});

// Setup the managers, vuex and the router
Vue.use(VueRouter);

import setupRouter from './routes';

import storeHelper from './store/index';
import StoreModuleGenerator from './store/modules/generator';
import ModalEditString from './store/modules/modalEditString';

// Initialise managers and register routes
let managerRoutes = [];

_.keys(window.managers).forEach(function(managerName){
    // Initialise a store module generator
    let manager = new StoreModuleGenerator(managerName);

    // Generate the store module
    manager.setup(window.managers[managerName]);

    // Replace manager definition with setup instance
    window.managers[managerName] = manager;

    // Register routes
    managerRoutes.push(manager.routes());
});

let router = setupRouter(managerRoutes);
let store = storeHelper;

// Setup notifications
import notification from './notifications';

Vue.use(notification.VueNotifications, notification.notificationOptions)

new Vue({
    router,
    store,
    data: {
        links: [
            {
                route: {name: 'dashboard'},
                name: 'Dashboard',
                actives: ['dashboard'],
                show: true
            }
        ]
    },
    methods: {
        isActiveGroup(activeRouteReference){
            return _.findIndex(this.$route.matched, (o) => { return o.name == activeRouteReference}) >= 0;
        }
    },
    beforeCreate(){
        // Register stores
        _.keys(window.managers).forEach(function(managerName){
            const manager = window.managers[managerName];

            // Register it with Vuex
            this.$store.registerModule(managerName, manager.getDefinition());
            this.$store.commit('registerManager', manager);
        }, this);

        // Calculate global stats
        this.$store.commit('recalculateStats');

        // Register the edit modal store
        this.$store.registerModule('ModalEditString', ModalEditString);
    },
    mounted(){
        // Build the menu
        _.keys(window.managers).forEach(function(managerName){
            this.links.push(this.$store.state[managerName].menu)
        }, this);
    },
    template: `
    <div class="col-md-12" id="translation-management-app">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/simple-line-icons/2.4.1/css/simple-line-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/3.5.2/animate.css" />
        <ul class="nav nav-tabs">
            <li v-for="link in links" v-show="link.show" role="presentation" v-bind:class="{active: isActiveGroup(link.route.name)}">
                <router-link :to="link.route">{{link.name}}</router-link>
            </li>
        </ul>
        <div class="tab-content" style="padding-top: 20px;">
            <div role="tabpanel" class="tab-pane active">
                <router-view></router-view>
            </div>
        </div>
    </div>`
}).$mount('#translation-app');