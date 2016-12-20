import Vue from 'vue';
import Vuex from 'vuex';
import _ from 'lodash';

Vue.use(Vuex);

export default new Vuex.Store({
    state: {
        permissions: window.permissions,
        managers: {},
        statsTotal: {
            groups: 0,
            keys: 0,
            changed: 0,
            locales: 0
        },
        allLocales : []
    },
    getters: {},
    mutations: {
        registerManager(state, payload)
        {
            let managerName = payload.namespace;
            let manager = {};
            manager[managerName] = payload;

            state.managers = _.assign({}, state.managers, manager);
        },
        updateStat(state, payload) {
            state.statsTotal[payload.type] = payload.value;
        },
        recalculateStats(state, payload) {
            _.keys(state.statsTotal).forEach(function (stat) {
                let count = 0;
                _.keys(state.managers).forEach(function (manager) {

                    switch (stat) {
                        case 'groups':
                            let groupCount = _.keys(state[manager].groups).length;
                            count += (groupCount > 1) ? groupCount - 1 : 0;
                            break;
                        case 'keys':
                            count += (state[manager].stats['records']) ? Number(state[manager].stats['records']) : state[manager].stats[stat] || 0;
                            break;
                        default:
                            count += Number(state[manager].stats[stat]) || 0;
                    }
                });

                state.statsTotal[stat] = count;
            });

            // Count unique locales
            let locales = [];
            _.keys(state.managers).forEach(function (manager) {
                state[manager].locales.forEach(l => {
                    if(locales.indexOf(l) < 0)
                    {
                        locales.push(l);
                    }
                })
            });
            state.allLocales = locales;
            state.statsTotal.locales = locales.length;
        },
        registerLocale(state, payload) {
            if(state.allLocales.indexOf(payload) < 0)
            {
                state.allLocales.push(payload);
            }
        }
    },
    actions: {},
    modules: {}
});