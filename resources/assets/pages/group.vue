<style>
    #translation-management-app .text-truncate {
        display: block !important;
        text-overflow: ellipsis;
        white-space: nowrap;
        overflow: hidden;
        height: 45px;
        padding-right: 10px;
    }
</style>
<template>
    <div class="row">
        <div class="col-md-4">
            <div class="input-group" style="width: 100%" >
                <input type="text" class="form-control"placeholder="Search for..." v-model="filter">
                <span class="input-group-btn" style="width: 41px;">
                <button v-show="filter != ''" @click="filter = ''" class="btn btn-default" type="button"><i
                        class="icon-action-undo"></i></button>
              </span>
            </div>
        </div>
        <div class="col-md-8 text-right">
            <div class="btn-group" role="group" aria-label="Translation group actions">
                <button v-if="canCreate" @click="openCreate" type="button" :class="loading.create" data-toggle="tooltip"
                        data-placement="top"
                        title="Create new keys for the current group">
                    Create
                </button>
                <button @click="importAppend" type="button" :class="loading.importAppend" data-toggle="tooltip"
                        data-placement="top"
                        title="Append existing (local) translations to the ones you see.">
                    Append
                </button>
                <button @click="importReplace" type="button" :class="loading.importReplace" data-toggle="tooltip"
                        data-placement="top"
                        title="Replace all translation keys with the local ones.">
                    Replace
                </button>
                <button @click="exportGroup" type="button" :class="loading.exportGroup" data-toggle="tooltip"
                        data-placement="top"
                        title="Export this group.">
                    Export
                </button>
            </div>
        </div>
        <div class="col-md-12">
            <table class="table table-hover table-striped">
                <thead>
                <tr>
                    <th class="col-md-4">Key</th>
                    <th class="col-md-4">String</th>
                    <th class="col-md-2">Locales</th>
                    <th class="col-md-2">Action</th>
                </tr>
                </thead>
                <tbody v-if="translations">
                <key-translation v-for="(definition, translationKey) in filteredTranslations" :definition="definition"
                                 :key="translationKey"></key-translation>
                </tbody>
            </table>
            <modal id="edit-string" :title="$store.state.ModalEditString.title">
                <div slot="content">
                    <div class="form" v-if="$store.state.ModalEditString.string">
                        <div class="alert alert-warning" v-if="$store.state.ModalEditString.formError">
                            {{$store.ModalEditString.formError}}
                        </div>
                        <div class="form" v-if="$route.meta.type == 'local'">
                            <div class="form-group" v-for="locale in $store.state['ModalEditString'].allLocales">
                                <label :for="locale" class="col-md-1 control-label">{{locale}}</label>
                                <div class="col-md-11">
                                        <textarea style="margin-bottom: 10px;" v-model="$store.state.ModalEditString.string.locales[locale].string.value"
                                                  :name="locale" class="form-control">
                                        </textarea>
                                </div>
                            </div>
                        </div>
                        <div class="form" v-if="$route.meta.type == 'external'">
                            <div class="row" v-for="locale in $store.state.ModalEditString.allLocales">
                                <div class="col-md-12">
                                    <strong>{{locale.toUpperCase()}}</strong>

                                    <div class="form-group"
                                         v-for="(keyValue, key) in editModalString[locale].string"
                                    >
                                        <label :for="locale+key" class="col-md-2 control-label">{{key}}</label>
                                        <div class="col-md-10">
                                        <textarea style="margin-bottom: 10px;" v-model="keyValue.value"
                                                  :name="locale+key" class="form-control">
                                        </textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button @click="saveKey" slot="button" type="button" class="btn btn-primary">Save changes</button>
            </modal>
            <modal id="new-strings" title="Create new keys">
                <div slot="content">
                    <div class="form">
                        <div class="alert alert-warning" v-if="createError">
                            {{createError}}
                        </div>
                        <div class="row" style="padding-bottom: 10px;">
                            <div class="col-md-12 text-right">
                                <button @click="addNewEmptyKey" class="btn btn-sm btn-default">
                                    <i class="glyphicon glyphicon-plus"></i>
                                </button>
                            </div>
                        </div>
                        <div class="row" v-for="(key, index) in newKeys" style="padding-bottom: 5px;">
                            <div class="col-md-12">
                                <div class="input-group">
                                    <input type="text" v-model="key.value" class="form-control input-sm"/>
                                    <span class="input-group-btn">
                                        <button @click="newKeys.splice(index, 1)" class="btn btn-danger btn-sm"><i
                                                class="glyphicon glyphicon-remove"></i></button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <button @click="createKeys" slot="button" type="button" class="btn btn-primary">Save new keys</button>
            </modal>
        </div>
    </div>
</template>

<script>
    import _ from 'lodash';
    import laroute from '../laroute';
    import Modal from '../components/modal.vue';
    import StringRow from '../components/string.vue';
    import {mapActions} from 'vuex';

    import ManagerMixin from '../mixins/managers';
    export default {
        mixins: [ManagerMixin],
        components: {
            modal: Modal,
            keyTranslation: StringRow
        },
        data(){
            return {
                string: '',
                filter: '',
                loading: {
                    create: {
                        btn: true, 'btn-primary': true, disabled: false
                    },
                    importAppend: {
                        btn: true, 'btn-default': true, disabled: false
                    },
                    importReplace: {
                        btn: true, 'btn-default': true, disabled: false
                    },
                    exportGroup: {
                        btn: true, 'btn-success': true, disabled: false
                    }
                },
                newKeys: [],
                createError: false,
            }
        },
        computed: {
            defaultLocale() {
                return this.$store.state.laravel.defaultLocale;
            },
            translations(){
                return this.$store.state[this.manager.namespace].currentGroup < 0 ?
                        [] :
                        this.$store.state[this.manager.namespace].translations[this.$store.state[this.manager.namespace].currentGroup].data;
            },
            filteredTranslations() {
                if (this.filter == '')
                    return this.translations;

                var self = this;

                if(this.$route.meta.type == 'local')
                {
                    // If the key contains the filter string
                    // Or the shown value contains the filter string
                    return _.pickBy(
                            this.translations,
                            ((string) => {
                                return (
                                        string.key.indexOf(self.filter) >= 0 ||
                                        (string.locales[self.defaultLocale].string.value && string.locales[self.defaultLocale].string.value.indexOf(self.filter) >= 0)
                                );
                            })
                    );
                }

                // If the key contains the filter string
                // If the identifier contains the filter string
                // Or the shown value contains the filter string
                return _.pickBy(
                        this.translations,
                        ((string) => {
                            return (
                                    string.key.indexOf(self.filter) >= 0 ||
                                    string.meta.identifier.indexOf(self.filter) >= 0 ||
                                    (
                                        string.locales[self.defaultLocale].string &&
                                        string.locales[self.defaultLocale].string[string.meta.render_value] &&
                                        string.locales[self.defaultLocale].string[string.meta.render_value].value.indexOf(self.filter) >= 0
                                    )
                            );
                        })
                );
            },
            editModalString(){
                return this.$store.state.ModalEditString.string.locales || [];
            },
            canCreate() {
                return this.$store.state[this.manager.namespace].features.create;
            }
        },
        methods: {
            loadGroup(payload){
                this.$store.dispatch(this.manager.action('loadGroup'), payload);
            },
            exportGroup(){
                this._btnRequest('exportGroup', 'translations.export.group', (response) => {
                    // group was exported
                    this.$store.commit(this.manager.mutation('changeStat'), {type: 'changed', value: response.body.changed});
                    this.$store.commit(this.manager.mutation('markGroupSaved'), this.$route.params.group);
                    // Recalculate global stats
                    this.$store.commit('recalculateStats');
                });
            },
            saveKey(){
                // Start row loader
                this.status = 'loading';

                // Send request
                this.$http
                        .post(laroute.route('translations.keys.update', {
                            manager: this.manager.namespace,
                            group: this.$route.params.group
                        }), this.$store.state.ModalEditString.string)
                        .then((response) => {
                            switch (response.body.status) {
                                case 'success':
                                    this.formError = false;
                                    this.status = false;

                                    this.$store.commit(this.manager.mutation('changeStat'), {
                                        type: 'changed',
                                        value: response.body.changed
                                    });

                                    this.$store.commit(this.manager.mutation('updateString'), {
                                        group: this.$route.params.group,
                                        key: this.$store.state.ModalEditString.string.key
                                    });

                                    $('#edit-string').modal('hide');

                                    break;
                                case 'error':
                                    this.formError = response.body.message;
                                    this.status = 'error';
                                    break;
                            }
                        })
                        .catch((response) => {
                            this.formError = 'There was an error whilst sending the request.';
                            this.rowError = this.definition.key;
                            this.status = 'error';
                            console.log(response);
                        });
            },
            openCreate(){
                this.newKeys = [];
                this.addNewEmptyKey();
                this.createError = false;
                var self = this;

                // Reset row editing on close (which should only happen once)
                $('#new-strings').modal('show').one('hide.bs.modal', () => {
                    self.newKeys = [];
                    self.createError = false;
                });
            },
            addNewEmptyKey(){
                this.newKeys.push({
                    value: '',
                    error: false
                });
            },
            createKeys() {
                // If already loading, do nothing
                if (this.loading.create.disabled)
                    return;

                // Remove any empty values
                this.newKeys = _.pickBy(this.newKeys, (k) => {
                    return k.value != ''
                });

                if (this.newKeys.length == 0) {
                    this.createError = 'Seems like you did not define any new keys.';
                    return;
                }

                // Start loader
                this.loading.create.disabled = true;

                this.$http
                        .post(laroute.route('translations.keys.create', {
                            manager: this.manager.namespace,
                            group: this.$route.params.group
                        }), {
                            keys: this.newKeys,
                            locale: this.defaultLocale
                        })
                        .then((response) => {
                            switch (response.body.status) {
                                case 'success':
                                    if (response.body.errors > 0) {
                                        this.newKeys = _.reduce(response.body.keys, ['error', false]);
                                        this.createError = 'Not all keys could be created';

                                        let self = this;
                                        $('#new-strings').modal('show').one('hide.bs.modal', () => {
                                            self.loadGroup({
                                                manager: self.manager.namespace,
                                                group: self.$route.params.group
                                            })
                                        });
                                        return;
                                    }

                                    $('#new-strings').modal('hide');

                                    // reload the translations
                                    this.loadGroup({manager: this.manager.namespace, group: this.$route.params.group})

                                    break;
                                case 'error':
                                    let self = this;
                                    this.createError = response.body.message;

                                    // Mark all as errored
                                    _.each(this.newKeys, function (key, index) {
                                        self.newKeys[index].error = true;
                                    })
                                    break;
                            }
                        })
                        .catch((response) => {
                            this.createError = 'The server had a hiccup';
                            console.log(response);
                        })
                        .finally(() => {
                            this.loading.create.disabled = false;
                        });
            },
            importAppend(){
                this._btnRequest('importAppend', 'translations.import.append.group', (response) => {
                    // group was appended
                    this.$store.commit(this.manager.mutation('changeStats'), [
                        {type: 'keys', value: response.body.records},
                        {type: 'locales', value: response.body.locales.length},
                        {type: 'changed', value: response.body.changed}
                    ]);
                    this.$store.commit(this.manager.mutation('setLocales'), response.body.locales);
                    this.$store.commit(this.manager.mutation('registerGroups'), response.body.groups);
                    // Recalculate global stats
                    this.$store.commit('recalculateStats');

                    // reload translations
                    this.loadGroup({group: this.$route.params.group});
                });
            },
            importReplace(){
                this._btnRequest('importReplace', 'translations.import.replace.group', (response) => {
                    // manager's translations were replaced
                    this.$store.commit(this.manager.mutation('setLocales'), response.body.locales);
                    this.$store.commit(this.manager.mutation('registerGroups'), response.body.groups);

                    this.$store.commit(this.manager.mutation('changeStats'), [
                        {type: 'keys', value: response.body.records},
                        {type: 'locales', value: response.body.locales.length},
                        {type: 'changed', value: response.body.changed}
                    ]);

                    // Recalculate global stats
                    this.$store.commit('recalculateStats');

                    // reload translations
                    this.loadGroup({group: this.$route.params.group});
                });
            },
            _btnRequest(key, route, success) {
                // If already loading, do nothing
                if (this.loading[key].disabled)
                    return;

                // Start button loader
                this.loading[key].disabled = true;

                // Send request
                this.$http
                        .get(laroute.route(route, {manager: this.manager.namespace, group: this.$route.params.group}))
                        .then(success)
                        .catch((response) => {
                            // Request error
                            this.failedRequestNotification();
                        })
                        .finally(function () {
                            // Stop button loader
                            this.loading[key].disabled = false;
                        });
            }
        },
        notifications: {
            failedRequestNotification: {
                message: 'Unable to complete the request',
                type: 'error'
            },
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
    }
</script>
