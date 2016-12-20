<style>
    #translation-management-app .modal .control-label {
        font-size: 15px;
    }
</style>
<template>
    <div>
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-danger">
                    <div class="panel-body">
                        <h4 style="margin: 5px;">
                            Stats
                            <span class="pull-right">
                                <a @click.prevent="showManagerStats=!showManagerStats" style="text-decoration: none;" class="text-danger">
                                    <i class="icon-arrow-down" v-if="!showManagerStats"></i>
                                    <i class="icon-arrow-up" v-if="showManagerStats"></i>
                                </a>
                            </span>
                        </h4>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading text-center">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state.statsTotal.groups}}</span>
                                Groups
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading text-center">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state.statsTotal.keys}}</span>
                                Keys
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading text-center">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state.statsTotal.changed}}</span>
                                Unsaved changes
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="panel panel-danger">
                            <div class="panel-heading text-center">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state.statsTotal.locales}}</span>Locales
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row" v-for="manager in $managers" v-show="showManagerStats">
            <div class="col-md-3">
                <h4 class="text-right text-danger" style="margin: 5px;">{{manager.namespace}}</h4>
            </div>
            <div class="col-md-9">
                <div class="row" style="padding-bottom: 10px;">
                    <div class="col-md-3 text-center">
                        <span style="display: block; font-size: 1.7em; margin-bottom: 5px">
                            {{groupCount($store.state[manager.namespace].groups)}}
                        </span>
                        Groups
                    </div>
                    <div class="col-md-3 text-center">
                        <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state[manager.namespace].stats.records}}</span>
                        Keys
                    </div>
                    <div class="col-md-3 text-center">
                        <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state[manager.namespace].stats.changed}}</span>
                        Unsaved changes
                    </div>
                    <div class="col-md-3 text-center">
                        <span style="display: block; font-size: 1.7em; margin-bottom: 5px">{{$store.state[manager.namespace].stats.locales}}</span>Locales

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-3">
                <div class="panel panel-info">
                    <div class="panel-body">
                        <h4 style="margin: 5px;">Actions</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-9">
                <div class="row">
                    <div class="col-md-3">
                        <div @click="importAll" class="panel panel-info text-center" style="cursor: pointer;">
                            <div :class="{'panel-heading': true, 'text-muted': loading.import.loading}"
                                 :style="{'background': loading.import.background, 'transition-duration': '0.3s'}"
                                 data-container="body" data-toggle="popover" data-placement="top"
                                 data-content="Imports translations that haven't been stored yet." data-trigger="hover">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px"><i
                                        class="icon-refresh"></i></span>
                                Import all
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3"
                         v-if="$store.state.permissions.create_locales && $store.state.laravel.features['locale.create']">
                        <div @click="localeOpen" class="panel panel-info text-center" style="cursor: pointer;">
                            <div :class="{'panel-heading': true, 'text-muted': loading.locale.loading}"
                                 :style="{'background': loading.locale.background, 'transition-duration': '0.3s'}">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px"><i
                                        class="icon-plus"></i></span>
                                New locale
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div @click="clean" class="panel panel-info text-center" style="cursor: pointer;">
                            <div :class="{'panel-heading': true, 'text-muted': clean.loading}"
                                 :style="{'background': loading.clean.background, 'transition-duration': '0.3s'}"
                                 data-container="body" data-toggle="popover" data-placement="top"
                                 data-content="Removes stored translations that don't have a value assigned."
                                 data-trigger="hover">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px"><i
                                        class="icon-ghost"></i></span>
                                Clean
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div @click="truncateConfirm" class="panel panel-info text-center" style="cursor: pointer;"
                             v-if="$store.state.permissions.truncate_translations">
                            <div :class="{'panel-heading': true, 'text-muted': loading.truncate.loading}"
                                 :style="{'background': loading.truncate.background, 'transition-duration': '0.3s'}"
                                 data-container="body" data-toggle="popover" data-placement="top"
                                 data-content="Removes all stored translations." data-trigger="hover">
                                <span style="display: block; font-size: 1.7em; margin-bottom: 5px"><i
                                        class="icon-trash"></i></span>
                                Truncate
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <modal id="truncate">
            <div slot="content">
                <p>You are about to delete all the translation records in the database.</p>
                <p>
                    Afterwards you can run import to retrieve all the translations you've lost.
                </p>
            </div>
            <button slot="button" type="button" @click="truncate" class="btn btn-primary">Truncate</button>
        </modal>
        <modal id="newLocale">
            <div :class="newLocale.classes" slot="content">
                <label class="control-label col-md-3">New Locale</label>
                <div class="col-md-9">
                    <input type="text" v-model="newLocale.value" class="form-control">
                </div>
            </div>
            <button slot="button" type="button" @click="localeSave" class="btn btn-primary">Create locale</button>
        </modal>
    </div>
</template>

<script>
    import laroute from '../laroute';
    import {mapGetters} from 'vuex';

    import Modal from '../components/modal.vue';

    import ManagerMixin from '../mixins/managers';
    export default {
        mixins: [ManagerMixin],
        components: {
            modal: Modal
        },
        data(){
            return {
                showManagerStats: false,
                newLocale: {
                    value: '',
                    classes: {
                        'form-group': true,
                        'has-error': false,
                    }
                },
                loading: {
                    locale: {
                        loading: false,
                        background: '', //#F0F8FF
                    },
                    import: {
                        loading: false,
                        background: '', //#F0F8FF
                    },
                    clean: {
                        loading: false,
                        background: '', //#F0F8FF
                    },
                    truncate: {
                        loading: false,
                        background: '', //#F0F8FF
                    }
                }
            }
        },
        methods: {
            groupCount(groups){
                return _.keys(groups).length - 1;
            },
            importAll(){
                this._doRequest('import', '#d1f9e0', laroute.route('translations.import'), (response) => {
                    _.keys(response.body.managers).forEach((managerName) => {
                        const manager = this.$managers[managerName];
                        const stats = response.body.managers[managerName];

                        // Update locales and groups
                        this.$store.commit(manager.mutation('setLocales'), stats.locales);
                        this.$store.commit(manager.mutation('setGroups'), stats.groups);

                        if (_.keys(stats.groups).length > 1) {
                            // Show the menu items for each manager
                            this.$store.commit(manager.mutation('showMenuItem'), true);
                        }

                        // Update manager stats
                        this.$store.commit(manager.mutation('changeStats'), [
                            {type: 'keys', value: stats.records},
                            {type: 'locales', value: stats.locales.length},
                            {type: 'groups', value: _.keys(stats.groups).length}
                        ]);
                    }, this);

                    // Recalculate global stats
                    this.$store.commit('recalculateStats');
                }, 'ImportErrorNotification');
            },
            truncate(){
                $('#truncate').modal('hide');
                const managers = this.$managers;
                this._doRequest('truncate', '#F0F8FF', laroute.route('translations.truncate', {manager: 'laravel'}), (response) => {
                    // All managers should have been reset
                    _.keys(managers).forEach((managerName) => {
                        const manager = managers[managerName];

                        // Empty out groups and locales
                        this.$store.commit(manager.mutation('setLocales'), []);
                        this.$store.commit(manager.mutation('setGroups'), {'': 'Choose a group'});

                        // Update manager's stats
                        this.$store.commit(manager.mutation('changeStats'), [
                            {type: 'keys', value: 0},
                            {type: 'records', value: 0},
                            {type: 'changed', value: 0},
                            {type: 'locales', value: []}
                        ]);

                        // Hide the menu item
                        this.$store.commit(manager.mutation('showMenuItem'), false)
                    });

                    // Recalculate global stats
                    this.$store.commit('recalculateStats');
                }, 'truncateErrorNotification');
            },
            truncateConfirm() {
                $('#truncate').modal('show');
            },
            clean(){
                this._doRequest('clean', '#F0F8FF', laroute.route('translations.clean', {manager: 'laravel'}), (response) => {
                    // Update stats and groups for each manager
                    _.keys(response.body.meta).forEach(managerName => {
                        const stats = response.body.meta[managerName];
                        const manager = this.$managers[managerName];

                        // Sync groups and locales
                        this.$store.commit(manager.mutation('setLocales'), stats.locales);
                        this.$store.commit(manager.mutation('setGroups'), stats.groups);

                        // Hide the nav menu item if needed
                        if (_.keys(stats.groups).length < 2) {
                            this.$store.commit(manager.mutation('showMenuItem'), false)
                        }

                        // Update stats accordingly
                        this.$store.commit(manager.mutation('changeStats'), [
                            {type: 'keys', value: stats.records},
                            {type: 'changed', value: stats.changed},
                            {type: 'locales', value: stats.locales.length}
                        ]);
                    }, this);

                    // Recalculate global stats
                    this.$store.commit('recalculateStats');
                }, 'cleanErrorNotification');
            },
            localeOpen() {
                this.newLocale.value = '';
                this.newLocale.classes['has-error'] = false;
                $('#newLocale').modal('show');
            },
            localeSave(){
                // Check if we already have the locale on record
                if (this.$store.state.laravel.locales.indexOf(this.newLocale.value) >= 0) {
                    this.newLocale.classes['has-error'] = true;
                    return;
                }

                // Remove any errors, hide the modal and start loading
                this.newLocale.classes['has-error'] = false;
                $('#newLocale').modal('hide');
                this.loading.locale.loading = true;
                this.loading.locale.background = '#F0F8FF';

                this.$http
                        .post(laroute.route('translations.locale', {manager: 'laravel'}), {locale: this.newLocale.value})
                        .then((response) => {
                            if (response.body.status == 'success') {
                                let newAmountChangedKeys = response.body.added + this.$store.state.laravel.stats.changed;
                                this.$store.commit(this.$managers.laravel.mutation('addLocale'), this.newLocale.value);
                                this.$store.commit(this.$managers.laravel.mutation('changeStat'), {
                                    type: 'changed',
                                    value: newAmountChangedKeys
                                });

                                // Register the locale globally
                                this.$store.commit('registerLocale', this.newLocale.value);

                                // Recalculate global stats
                                this.$store.commit('recalculateStats');
                            }
                        })
                        .catch((response) => {
                            // Request error
                        })
                        .finally(() => {
                            // Finish loading
                            this.loading.locale.loading = false;
                            this.loading.locale.background = '#5BC0DE';

                            // Empty local translations to prevent stale data
                        });
            },
            _doRequest(key, newColor, route, success, errorNotification) {
                // Start loading
                this.loading[key].loading = true;
                this.loading[key].background = newColor;

                this.$http
                        .get(route)
                        .then(success)
                        .catch((response) => {
                            // Request error
                            console.log('error', response);

                            if(errorNotification)
                            {
                                this[errorNotification]();
                            }
                        })
                        .finally(() => {
                            // Finish loading
                            this.loading[key].loading = false;
                            this.loading[key].background = '#5BC0DE';

                            // Empty local translations to prevent stale data
                        });
            }
        },
        notifications: {
            cleanErrorNotification: {
                message: 'Unable to clean stored translations',
                type: 'error'
            },
            truncateErrorNotification: {
                message: 'Unable to truncate stored translations',
                type: 'error'
            },
            ImportErrorNotification: {
                message: 'Unable to import translations',
                type: 'error'
            },
        },
        mounted(){
            $('[data-toggle="popover"]').popover();
        }
    }
</script>
