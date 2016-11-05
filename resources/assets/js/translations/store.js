import _ from 'lodash';
import Vue from 'vue';
import VueResource from 'vue-resource';
import Vuex from 'vuex';
import routes from './laroute';

Vue.use(VueResource);
Vue.use(Vuex);

const state = {
    current: -1,
    translations: [],
    locale: window.defaultLocale,
    locales: window.locales,
    stats: window.stats,
    features: window.features,
    groups: {},
};

const mutations = {
    placeGroup(state, payload) {
        let index = _.findIndex(state.translations, ['group', payload.group]);

        if(index == -1)
        {
            // Register the new group
            state.translations.push({
                group: payload.group,
                data: payload.translations,
                timestamp: payload.lastUpdate
            });
        }
        else
        {
            state.translations[index].group = payload.group;
            state.translations[index].data = payload.translations;
            state.translations[index].timestamp = payload.lastUpdate;
        }

    },
    mergeGroup(state, payload) {
        // Find the group in the translations
        let index = _.findIndex(state.translations, ['group', payload.group]);

        // merge the translations
        _.merge(state.translations[index].data, payload.translations);

        // Update the last update timestamp
        state.translations[index].timestamp = payload.lastUpdate;
    },
    current(state, payload) {
        // On the next tick we'll change the active group
        Vue.nextTick(function () {
            state.current = _.findIndex(state.translations, ['group', payload]);
        });
    },
    switchLocale(state, payload)
    {
        // Only switch if the locale is registered
        if (state.locales.indexOf(payload) >= 0) {
            state.locale = payload;
        }
    },
    addLocale(state, payload)
    {
        // Add the locale if we don't have it already
        if (state.locales.indexOf(payload) < 0) {
            state.locales.push(payload);
        }
    },
    changeStat(state, payload)
    {
        state.stats[payload.type] = payload.value;
    },
    registerGroups(state, payload)
    {
        state.groups = payload;
    },
    markGroupSaved(state, payload)
    {
        let groupIndex = _.findIndex(state.translations, ['group', payload]);

        if(groupIndex >= 0)
        {
            // Loop over the group's strings
            _.each(
                state.translations[payload].data,
                (string, index) => {
                    // Loop over the string locales to change their status.
                    _.each(
                        string.locales,
                        (l, key) => {
                            // Only if the locale has valid value
                            if(l.value != '' && l.value != null)
                            {
                                state.translations[payload].data[index].locales[key].status = 0;
                            }
                        }
                    );
                }
            );
        }
    }
};

const getters = {
    groupCount(state) {
        return _.keys(state.groups).length - 1;
    }
};

const actions = {
    updateActiveGroup({commit}, payload){
        commit('current', payload)
    },
    switchLocale({commit}, payload){
        commit('switchLocale', payload)
    },
    loadGroup({dispatch, commit, state}, payload)
    {
        let params = {group: payload.group};

        // If the group is already registered, request for an update
        if (payload.group in state.translations) {
            params.timestamp = state[payload.group].timestamp;
        }

        // Request the translation for this group
        Vue.http.get(routes.route('translations.groups', params)).then((response) => {
            switch (response.body.status) {
                case 'new':
                    // Create the new group
                    commit('placeGroup', {
                        group: payload.group,
                        translations: response.body.strings,
                        lastUpdate: response.body.last_update
                    });

                    // Set the newly create group active
                    dispatch('updateActiveGroup', response.body.group);

                    // Enable tooltips
                    $('[data-toggle="tooltip"]').tooltip();
                    break;
                case 'update':
                    // Merge the results into the group
                    commit('mergeGroup', {
                        group: payload.group,
                        translations: response.body.strings,
                        lastUpdate: response.body.last_update
                    });

                    // Enable tooltips
                    $('[data-toggle="tooltip"]').tooltip();
                    break;
                case 'empty':
                    // No (new) data for the requested group
                    break;
            }
        })
    }
};

export default new Vuex.Store({
    state,
    actions,
    mutations,
    getters
});