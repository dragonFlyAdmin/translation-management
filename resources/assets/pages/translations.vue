<template>
    <div>
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
        <modal id="importReplaceAll">
            <p slot="content">You are about to replace all strings in the database with local ones.</p>
            <button slot="button" type="button" @click="importReplace" class="btn btn-primary">Replace</button>
        </modal>
        <div class="row">
            <div class="col-md-12">
                <select class="form-control" @change="loadTranslationGroup" v-model="activeGroup">
                    <option v-for="(option, index) in $store.state.groups" :value="index">{{option}}</option>
                </select>
            </div>
        </div>
        <div class="row" v-show="activeGroup!=''">
            <div class="col-md-12" style="padding-top: 20px;">
                <router-view></router-view>
            </div>
        </div>
    </div>
</template>

<script>
    import { mapActions } from 'vuex';

    import Modal from '../components/modal.vue';
    export default {
        components: {
            modal: Modal
        },
        created () {
            // Set the correct translation group on creation.
            this.syncRoute();

            if(this.$route.params.group) {
                this.loadGroup({manager: this.$route.meta.manager, group: this.$route.params.group});
            }
        },
        watch: {
            // call again the syncRoute method if the route changes
            '$route': 'syncRoute'
        },
        data(){
            return {
                activeGroup: '',
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
                this._doRequest('exportAll', '#d1f9e0', laroute.route('translations.export', {manager: this.$route.meta.manager}), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'changed', value: 0});

                    // Mark all the groups' strings as saved
                    _.each(this.$store.state.groups, (strings, name) => {
                        this.$store.commit('markGroupSaved', name);
                    });
                });
            },
            scan(){
                this._doRequest('scan', '#d1f9e0', laroute.route('translations.scan', {manager: this.$route.meta.manager}), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            importAppend(){
                this._doRequest('importAppend', '#d1f9e0', laroute.route('translations.import.append', {manager: this.$route.meta.manager}), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            importReplace(){
                $('#importReplaceAll').modal('hide');
                this._doRequest('importReplace', '#d1f9e0', laroute.route('translations.import.replace', {manager: this.$route.meta.manager}), (response) => {
                    // Update stats and groups
                    this.$store.commit('changeStat', {type: 'keys', value: response.body.records});
                    this.$store.commit('changeStat', {type: 'locales', value: response.body.locales});
                    this.$store.commit('changeStat', {type: 'changed', value: response.body.changed});
                    this.$store.commit('registerGroups', response.body.groups);
                });
            },
            importReplaceConfirm() {
                $('#importReplaceAll').modal('show');
            },
                ...mapActions(['loadGroup', 'updateActiveGroup']),
            syncRoute(){
                // Set the active group based on the route param
                this.activeGroup = this.$route.params.group || '';

                this.updateActiveGroup(this.activeGroup);
            },
            loadTranslationGroup(){
                // Seems like we're back at the dashboard
                if(this.activeGroup == '')
                {
                    this.$router.push({
                        name: 'translations'
                    });
                    return;
                }

                // Check for strings in the group
                this.loadGroup({manager: this.$route.meta.manager, group: this.activeGroup})

                // Load the specified group
                this.$router.push({
                    name: 'group',
                    params: {
                        group: this.activeGroup
                    }
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
