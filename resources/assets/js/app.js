import Vue from 'vue';
import VueRouter from 'vue-router';
import VueResource from 'vue-resource';
import Vuex from 'vuex';
import _ from 'lodash';

Vue.use(VueRouter);
Vue.use(VueResource);
Vue.use(Vuex);

Vue.http.options.emulateJSON = true;
Vue.http.interceptors.push((request, next) => {
    request.headers.set('X-CSRF-TOKEN', window.csrfToken);

    next();
});

import router from './routes';

import store from './store';

const TranslationManager = new Vue({
    router,
    store,
    data: {
        links: [
            {
                route: {name: 'dashboard'},
                name: 'Dashboard',
                actives: ['dashboard'],
                show: true
            },
            {
                route: {name: 'translations'},
                name: 'Translations',
                actives: ['translations', 'group'],
                show: (_.size(window.groups) > 1)
            }
        ]
    },
    methods: {
        isActiveGroup(activeRouteReference){
            return _.findIndex(this.$route.matched, (o) => { return o.name == activeRouteReference}) >= 0;
        }
    },
    mounted(){
        this.$store.commit('registerGroups', window.groups);
    },
    template: '<div class="col-md-12"><ul class="nav nav-tabs">'+
        '<li v-for="link in links" v-show="link.show" role="presentation" v-bind:class="{active: isActiveGroup(link.route.name)}"><router-link :to="link.route">{{link.name}}</router-link></li>'+
    '</ul>'+
    '<div class="tab-content" style="padding-top: 20px;">'+
        '<div role="tabpanel" class="tab-pane active"><router-view></router-view></div>'+
    '</div></div>'
}).$mount('#translation-app');