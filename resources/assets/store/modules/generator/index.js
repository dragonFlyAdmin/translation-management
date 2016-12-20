import TypeGenerator from './types';
import * as MutationsList from './mutations';
import * as GettersList from './getters';
import * as ActionsList from './actions';

import Translations from '../../../pages/translations.vue';
import Group from '../../../pages/group.vue';

export default class Generator {
    constructor(namespace) {
        this.namespace = namespace;

        this.state = {
            manager: false,
            currentGroup: -1,
            translations: [],
            locales: [],
            menu: {
                route: {name: this.namespace+'.translations'},
                name: this.namespace.charAt(0).toUpperCase() + this.namespace.slice(1) + ' translations',
                actives: [this.namespace+'.translations', this.namespace+'.group'],
                show: false
            }
        };

        this.mutations = {};
        this.actions = {};
        this.getters = {};

        this.types = false;
    }

    setup(hydrate) {
        // Generate namespaced types
        this.types = this.retrieveTypes();

        // Initialise and hydrate the state
        this.hydrateState(hydrate);

        // Register all the methods
        this.registerMutations(hydrate.features);
        this.registerGetters(hydrate.features);
        this.registerActions(hydrate.features);
    }

    getDefinition(){
        // Return module definition
        return {
            'state': this.state,
            'mutations': this.mutations,
            'actions': this.actions,
            'getters': this.getters
        };
    }

    retrieveTypes() {
        return TypeGenerator.retrieve(this.namespace);
    }

    registerActions(features) {
        this.actions[this.types.actions.loadGroup] = ActionsList.loadGroup;
    }

    registerGetters(features) {
        this.getters[this.types.getters.groupCount] = GettersList.groupCount;
    }

    registerMutations(features) {
        this.mutations[this.types.mutations.addLocale] = MutationsList.addLocale;
        this.mutations[this.types.mutations.setLocales] = MutationsList.setLocales;

        // Default mutations that should be registered
        this.mutations[this.types.mutations.setLocales] = MutationsList.setLocales;
        this.mutations[this.types.mutations.mergeGroup] = MutationsList.mergeTranslations;
        this.mutations[this.types.mutations.changeStat] = MutationsList.updateStat;
        this.mutations[this.types.mutations.changeStats] = MutationsList.updateStats;
        this.mutations[this.types.mutations.updateString] = MutationsList.updateString;
        this.mutations[this.types.mutations.markGroupSaved] = MutationsList.markGroupSaved;
        this.mutations[this.types.mutations.registerGroups] = MutationsList.setGroups;
        this.mutations[this.types.mutations.setGroups] = MutationsList.setGroups;
        this.mutations[this.types.mutations.placeGroup] = MutationsList.placeGroup;
        this.mutations[this.types.mutations.switchGroup] = MutationsList.switchGroup;

        this.mutations[this.types.mutations.showMenuItem] = MutationsList.showMenuItem;
    }

    hydrateState (hydrate) {
        this.state.manager = this.namespace;

        // If this manager is able to create locales
        if (hydrate.features['locale.create']) {
            this.state.defaultLocale = window.defaultLocale;
        }

        // Hydrate other attributes
        this.state.groups = hydrate.groups;
        this.state.stats = hydrate.stats;
        this.state.features = hydrate.features;
        this.state.locales = hydrate.locales;

        this.state.menu.show = (_.size(this.state.groups) > 1);
    }

    mutation(key)
    {
        return this.types.mutations[key];
    }

    action(key)
    {
        return this.types.actions[key];
    }

    getter(key)
    {
        return this.types.getters[key];
    }

    routes(){
        return {
            path: '/'+this.namespace,
            name: this.namespace+'.translations',
            component: Translations,
            meta: {
                manager: this.namespace,
                type: (this.namespace == 'laravel') ? 'local' : 'external'
            },
            children: [
                {
                    path: `/${this.namespace}/:group?`,
                    name: this.namespace+'.group',
                    component: Group,
                    meta: {
                        manager: this.namespace,
                        type: (this.namespace == 'laravel') ? 'local' : 'external'
                    }
                }
            ]
        }
    }
}