//import _ from 'lodash';
import Vue from 'vue';
import routes from '../../../laroute';

export const loadGroup = ({commit, state}, payload) => {
    let params = {manager: state.manager, group: payload.group};

    // If the group is already registered, request for an update
    if (payload.group in state.translations) {
        params.timestamp = state[payload.group].timestamp;
    }

    // Request the translation for this group
    return Vue.http.get(routes.route('translations.groups', params)).then((response) => {
        switch (response.body.status) {
            case 'new':
                // Create the new group
                commit(state.manager + '/PLACE_GROUP', {
                    group: payload.group,
                    translations: response.body.strings,
                    lastUpdate: response.body.last_update
                });

                // Set the newly create group active
                commit(state.manager + '/SWITCH_GROUP', response.body.group);

                // Enable tooltips
                $('[data-toggle="tooltip"]').tooltip();
                break;
            case 'update':
                // Merge the results into the group
                commit(state.manager + '/MERGE_GROUP', {
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