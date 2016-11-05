<style>
    .modal .control-label {
        font-size: 15px;
    }
</style>
<template>
        <div>
            <div class="row">
                <div class="col-md-3">
                    <div class="panel panel-danger">
                        <div class="panel-heading">
                            <h4 style="margin: 5px;">Stats</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="panel panel-danger">
                                <div class="panel-body">
                                    Groups <span class="pull-right">{{$store.getters.groupCount}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-danger">
                                <div class="panel-body">
                                    Keys <span class="pull-right">{{$store.state.stats.keys}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-danger">
                                <div class="panel-body">
                                    Unsaved changes <span class="pull-right">{{$store.state.stats.changed}}</span>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-danger">
                                <div class="panel-body">
                                    Locales <span class="pull-right">{{$store.state.locales.length}}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="panel panel-success">
                        <div class="panel-heading">
                            <h4 style="margin: 5px;">Sync</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="panel panel-success" style="cursor: pointer;" @click="importAppend">
                                <div :class="{'panel-body': true, 'text-muted': loading.importAppend.loading}" :style="{'background': loading.importAppend.background, 'transition-duration': '0.3s'}">
                                    Import <small class="text-muted pull-right" style="padding-top: 4px;">append</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-success" style="cursor: pointer;" @click="importReplaceConfirm">
                                <div :class="{'panel-body': true, 'text-muted': importReplace.loading}" :style="{'background': loading.importReplace.background, 'transition-duration': '0.3s'}">
                                    Import <small class="text-muted pull-right" style="padding-top: 4px;">replace</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-success" style="cursor: pointer;" @click="scan">
                                <div :class="{'panel-body': true, 'text-muted': loading.scan.loading}" :style="{'background': loading.scan.background, 'transition-duration': '0.3s'}">
                                    Scan files
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="panel panel-success" style="cursor: pointer;" @click="exportAll">
                                <div :class="{'panel-body': true, 'text-muted': loading.exportAll.loading}" :style="{background: loading.exportAll.background, 'transition-duration': '0.3s'}">
                                    Export
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-3">
                    <div class="panel panel-info">
                        <div class="panel-heading">
                            <h4 style="margin: 5px;">Actions</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-9">
                    <div class="row">
                        <div class="col-md-3" v-if="$store.state.features.create_locales">
                            <div @click="localeOpen" class="panel panel-info" style="cursor: pointer;">
                                <div :class="{'panel-body': true, 'text-muted': loading.locale.loading}" :style="{'background': loading.locale.background, 'transition-duration': '0.3s'}">
                                    New locale
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div @click="clean" class="panel panel-info" style="cursor: pointer;">
                                <div :class="{'panel-body': true, 'text-muted': clean.loading}" :style="{'background': loading.clean.background, 'transition-duration': '0.3s'}">
                                    Clean
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div @click="truncateConfirm" class="panel panel-info" style="cursor: pointer;" v-if="$store.state.features.truncate_translations">
                                <div :class="{'panel-body': true, 'text-muted': loading.truncate.loading}" :style="{'background': loading.truncate.background, 'transition-duration': '0.3s'}">
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
            <modal id="importReplace">
                <p slot="content">You are about to replace all strings in the database with local ones.</p>
                <button slot="button" type="button" @click="importReplace" class="btn btn-primary">Replace</button>
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
    import { mapMutations } from 'vuex';

    import Modal from '../components/modal.vue';
    export default {
        components: {
            modal: Modal
        },
        data(){
            return {
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
                    clean: {
                        loading: false,
                        background: '', //#F0F8FF
                    },
                    truncate: {
                        loading: false,
                        background: '', //#F0F8FF
                    },
                    importAppend: {
                        loading: false,
                        background: '', //#d1f9e0
                    },
                    importReplace: {
                        loading: false,
                        background: '', //#d1f9e0
                    },
                    scan: {
                        loading: false,
                        background: '', //#d1f9e0
                    },
                    exportAll: {
                        loading: false,
                        background: '', //#d1f9e0
                    }
                }
            }
        },
        methods: {
            exportAll(){
                this._doRequest('exportAll', '#d1f9e0', laroute.route('translations.export'), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'changed', value: 0});

                    // Mark all the groups' strings as saved
                    _.each(this.$store.state.groups, (strings, name) => {
                        this.$store.commit('markGroupSaved', name);
                    });
                });
            },
            scan(){
                this._doRequest('scan', '#d1f9e0', laroute.route('translations.scan'), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            importAppend(){
                this._doRequest('importAppend', '#d1f9e0', laroute.route('translations.import.append'), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            importReplace(){
                $('#importReplace').modal('hide');
                this._doRequest('importReplace', '#d1f9e0', laroute.route('translations.import.replace'), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            importReplaceConfirm() {
                $('#importReplace').modal('show');
            },
            truncate(){
                $('#truncate').modal('hide');
                this._doRequest('truncate', '#F0F8FF', laroute.route('translations.truncate'), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: 0});
                    this.$store.commit('changeStat', {type: 'changed', value: 0});
                    this.$store.commit('changeStat', {type: 'locales', value: []});
                    this.$store.commit('registerGroups', {'': 'Choose a group'});
                });
            },
            truncateConfirm() {
                $('#truncate').modal('show');
            },
            clean(){
                this._doRequest('clean', '#F0F8FF', laroute.route('translations.clean'), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            localeOpen() {
                this.newLocale.value = '';
                this.newLocale.classes['has-error'] = false;
                $('#newLocale').modal('show');
            },
            localeSave(){
                // Check if we already have the locale on record
                if(this.$store.state.locales.indexOf(this.newLocale.value) >= 0)
                {
                    this.newLocale.classes['has-error'] = true;
                    return;
                }

                // Remove any errors, hide the modal and start loading
                this.newLocale.classes['has-error'] = false;
                $('#newLocale').modal('hide');
                this.loading.locale.loading = true;
                this.loading.locale.background = '#F0F8FF';

                this.$http
                        .post(laroute.route('translations.locale'), {locale: this.newLocale.value})
                        .then((response) => {
                            if(response.body.status == 'success')
                            {
                                this.$store.commit('addLocale', this.newLocale.value);
                            }
                        })
                        .catch((response) => {
                            // Request error
                        })
                        .finally(() => {
                            // Finish loading
                            this.loading.locale.loading = false;
                            this.loading.locale.background = '#FFFFFF';

                            // Empty local translations to prevent stale data
                        });
            },
            _doRequest(key, newColor, route, success) {
                // Start loading
                this.loading[key].loading = true;
                this.loading[key].background = newColor;

                this.$http
                        .get(route)
                        .then(success)
                        .catch((response) => {
                            // Request error
                        })
                        .finally(() => {
                            // Finish loading
                            this.loading[key].loading = false;
                            this.loading[key].background = '#FFFFFF';

                            // Empty local translations to prevent stale data
                        });
            }
        }
    }
</script>
