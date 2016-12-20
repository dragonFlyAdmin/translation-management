import _ from 'lodash';
import Vue from 'vue';

export const mergeTranslations = (state, payload) => {
    // Find the group in the translations
    let index = _.findIndex(state.translations, ['group', payload.group]);

    // merge the translations
    _.merge(state.translations[index].data, payload.translations);

    // Update the last update timestamp
    state.translations[index].timestamp = payload.lastUpdate;
};

export const switchLocale = (state, payload) => {
    // Only switch if the locale is registered
    if (state.locales.indexOf(payload) >= 0) {
        state.defaultLocale = payload;
    }
};

export const addLocale = (state, payload) => {
    // Add the locale if we don't have it already
    if (state.locales.indexOf(payload) < 0) {
        state.locales.push(payload);
        state.stats.locales++;
    }
};

export const setLocales = (state, payload) => {
    state.locales = payload;
};

export const showMenuItem = (state, payload) => {
    state.menu.show = payload;
};

export const updateStat = (state, payload) => {
    state.stats[payload.type] = payload.value;
};

export const updateStats = (state, payload) => {

    let stats = state.stats;

    payload.forEach(function (stat) {
        stats[stat.type] = stat.value;
    });

    state.stats = stats;
};

export const markGroupSaved = (state, payload) => {
    let groupIndex = _.findIndex(state.translations, ['group', payload]);

    if (groupIndex >= 0) {
        // Loop over the group's strings
        _.each(
            state.translations[groupIndex].data,
            (string, index) => {
                // Loop over the string locales to change their status.
                _.each(
                    string.locales,
                    (l, key) => {
                        // Only if the locale has valid value
                        if (l.value != '' && l.value != null) {
                            state.translations[groupIndex].data[index].locales[key].status = 0;
                        }
                    }
                );
            }
        );
    }
};

export const placeGroup = (state, payload) => {
    let index = _.findIndex(state.translations, ['group', payload.group]);

    if (index == -1) {
        // Register the new group
        state.translations.push({
            group: payload.group,
            data: payload.translations,
            timestamp: payload.lastUpdate
        });
    }
    else {
        // Update existing group
        state.translations[index].data = payload.translations;
        state.translations[index].timestamp = payload.lastUpdate;
    }
};


export const setGroups = (state, payload) => {
    state.groups = payload;
};

export const switchGroup = (state, payload) => {
    // On the next tick we'll change the active group
    Vue.nextTick(function () {
        state.currentGroup = _.findIndex(state.translations, ['group', payload]);
    });
};

export const updateString = (state, payload) => {
    let groupIndex = _.findIndex(state.translations, ['group', payload.group]);

    _.each(state.translations[groupIndex].data[payload.key].locales, (s, locale) => {
        if (s.value == '') {
            state.translations[groupIndex].data[payload.key].locales[locale].value = null;
        }
        else {
            state.translations[groupIndex].data[payload.key].locales[locale].status = 1;
        }
    });
};