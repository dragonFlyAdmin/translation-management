import _ from 'lodash';

const getMissingTranslations = (state) => {
    return _.filter(state.allLocales, (locale) => {
        return state.locales.indexOf(locale) < 0 || state.string.locales[locale].value == ''
    });
}

export default {
    state: {
        title: '',
        error: false,
        string: false,
        locales: [],
        allLocales: [],
        type: 'local'
    },
    mutations: {
        'MODAL_EDIT/updateTitle': (state, payload) => {
            state.title = payload
        },
        'MODAL_EDIT/updateError': (state, payload) => {
            state.error = payload
        },
        'MODAL_EDIT/updateString': (state, payload) => {
            state.string = payload;
            state.error = false;
        },
        'MODAL_EDIT/reset': (state, payload) => {
            state.title = '';
            state.error = false;
            state.string = false;
        },
        'MODAL_EDIT/update': (state, payload) => {
            state.title = `Editing "${payload.title}"`;
            state.string = payload.string;
            state.error = false;
            state.locales = _.keys(state.string.locales);
            state.allLocales = payload.locales;
            state.type = payload.type;

            // Prepare the missing locales
            let missingLocales = getMissingTranslations(state);

            if(missingLocales.length > 0)
            {
                missingLocales.forEach((locale) => {
                    switch(payload.type) {
                        case 'local':
                            state.string.locales[locale] = {
                                locale: locale,
                                string: {value: ''},
                                error: ''
                            }
                            break;
                        case 'external':
                            // Add missing keys
                            state.string.locales[locale] = {locale: locale, string : {}};
                            _.keys(state.string.locales[payload.defaultLocale].string).forEach((localKey) => {
                                state.string.locales[locale].string[localKey] = {value: null};
                            });
                            break;
                    }

                })
            }
        }
    },
    getters: {
        'MODAL_EDIT/missingLocales': (state)  => {
            return getMissingTranslations(state);
        }
    }
}