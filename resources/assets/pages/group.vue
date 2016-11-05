<style>
    .text-truncate {
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
            <div class="input-group">
                <input type="text" class="form-control" placeholder="Search for..." v-model="filter">
                <span class="input-group-btn">
                <button @click="filter = ''" class="btn btn-default" type="button"><i class="glyphicon glyphicon-remove"></i></button>
              </span>
            </div>
        </div>
        <div class="col-md-8 text-right">
            <div class="btn-group" role="group" aria-label="Translation group actions">
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
                <tr v-for="(string, key) in filteredTranslations"
                    :class="{warning: key == rowLoading, info: key == rowEditing, error: key == rowError, active: key == rowWarning}">
                    <td class="col-md-4">{{key}}</td>
                    <td class="col-md-12 text-truncate">{{string.locales[$store.state.locale].value}}</td>
                    <td class="col-md-2">{{localeString(string)}}</td>
                    <td class="col-md-2">
                        <button class="btn btn-xs btn-primary" @click="openKey(string, key)">Edit</button>
                        <button @click="deleteString(string)" type="button"
                                v-if="$store.state.features.delete_translations"
                                class="btn btn-xs btn-danger">
                            Delete
                        </button>
                        <button @click="resetToFile(string)" class="btn btn-xs btn-default" v-if="hasChanges(string)">Reset</button>
                    </td>
                </tr>
                </tbody>
            </table>
            <modal id="edit-string" :title="'Editing '+string.key">
                <div slot="content">
                    <form class="form">
                        <div class="alert alert-warning" v-if="formError">
                            {{formError}}
                        </div>
                        <div class="row" v-for="locale in locale(string)">
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label :for="locale" class="col-md-1 control-label">{{locale}}</label>
                                    <div class="col-md-11">
                                        <textarea v-model="string.locales[locale].value" :name="locale"
                                                  class="form-control"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <button @click="saveKey" slot="button" type="button" class="btn btn-primary">Save changes</button>
            </modal>
        </div>
    </div>
</template>

<script>
    import _ from 'lodash';
    import laroute from '../laroute';
    import Modal from '../components/modal.vue';
    import {mapActions} from 'vuex';

    export default {
        components: {
            modal: Modal
        },
        data(){
            return {
                filter: '',
                string: {},
                rowLoading: -1,
                rowEditing: -1,
                rowError: -1,
                rowWarning: -1,
                formError: false,
                loading: {
                    importAppend: {
                        btn: true, 'btn-default': true, disabled: false
                    },
                    importReplace: {
                        btn: true, 'btn-default': true, disabled: false
                    },
                    exportGroup: {
                        btn: true, 'btn-success': true, disabled: false
                    }
                }
            }
        },
        computed: {
            translations(){
                return this.$store.state.current < 0 ? [] : this.$store.state.translations[this.$store.state.current].data;
            },
            filteredTranslations() {
                if(this.filter == '')
                    return this.translations;

                var self = this;

                // If the key contains the filter string
                // Or the shown value contains the filter string
                return _.pickBy(
                        this.translations,
                        ((string) => {
                            return (
                                string.key.indexOf(self.filter) >= 0 ||
                                string.locales[self.$store.state.locale].value.indexOf(self.filter) >= 0
                            );
                        })
                    );
            }
        },
        methods: {
            ...mapActions(['loadGroup']),
            localeString(string) {
                // Return a list of locales for the specified string
                let filledLocales = _.reject(
                        string.locales,
                        (l) => { return l.value == null}
                    );

                if (filledLocales.length == 0)
                    return '';

                return _.map(filledLocales, (l) => {return l.locale;}).join(', ');
            },
            locale(strings) {
                return _.keys(strings.locales);
            },
            hasChanges(string) {
                return _.size(_.reject(string.locales, (l) => {
                            return l.status == 0
                        })) > 0;
            },
            deleteString(string) {
                this.rowWarning = string.key;

                this.$http
                        .delete(laroute.route('translations.keys.delete', {
                            group: this.$route.params.group,
                            key: string.key
                        }))
                        .then((response) => {
                            // reload the translations
                            this.loadGroup({group: this.$route.params.group})
                        })
                        .catch((response) => {
                            // server error
                        })
                        .finally(() => {
                            this.rowWarning = -1;
                        })
            },
            resetToFile(string) {

            },
            openKey(string, key)
            {
                this.string = string;
                this.rowEditing = key;
                this.formError = false;
                var self = this;

                // Reset row editing on close (which should only happen once)
                $('#edit-string').modal('show').one('hide.bs.modal', () => {
                    self.rowEditing = -1;
                });
            },
            saveKey(){
                // Start row loader
                this.rowLoading = this.string.key;
                this.rowEditing = -1;

                // Send request
                this.$http
                        .post(laroute.route('translations.keys.update', {group: this.$route.params.group}), this.string)
                        .then((response) => {
                            switch (response.body.status) {
                                case 'success':
                                    $('#edit-string').modal('hide');
                                    this.formError = false;
                                    this.rowError = -1;

                                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});

                                    // Make sure empty strings are converted to null, other ones' status are set to changed
                                    var self = this;
                                    _.each(this.string.locales, (s, locale) => {
                                        if (s.value == '') {
                                            self.string.locales[locale].value = null;
                                        }
                                        else {
                                            self.string.locales[locale].status = 1;
                                        }
                                    });

                                    break;
                                case 'error':
                                    this.formError = response.body.message;
                                    this.rowError = this.string.key;
                                    break;
                            }

                            this.rowLoading = -1;
                        })
                        .catch((response) => {
                            this.formError = 'There was an error whilst sending the request.';
                            this.rowError = this.string.key;
                            this.rowLoading = -1;
                            console.log(response);
                        })
                        .finally(function () {
                            // Stop button loader
                            this.rowLoading = -1;
                        });
            },
            exportGroup(){
                this._btnRequest('exportGroup', 'translations.export.group', (response) => {
                    // group was exported
                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});
                    this.$store.commit('markGroupSaved', this.$route.params.group);
                });
            },
            importAppend(){
                this._btnRequest('importAppend', 'translations.import.append.group', (response) => {
                    // group was appended
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});
                    this.$store.commit('registerGroups', response.body.groups);

                    // reload translations
                    this.loadGroup({group: this.$route.params.group});
                });
            },
            importReplace(){
                this._btnRequest('importReplace', 'translations.import.replace.group', (response) => {
                    // group was replaced
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});
                    this.$store.commit('registerGroups', response.body.groups);

                    // reload translations
                    this.loadGroup({group: this.$route.params.group});
                });
            },
            _btnRequest(key, route, success) {
                // Start button loader
                this.loading[key].disabled = true;

                // Send request
                this.$http
                        .get(laroute.route(route, {group: this.$route.params.group}))
                        .then(success)
                        .catch((response) => {
                            // Request error
                        })
                        .finally(function () {
                            // Stop button loader
                            this.loading[key].disabled = false;
                        });
            }
        }
    }
</script>
