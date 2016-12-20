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
    <tr :class="{warning: status == 'loading', info: status == 'editing', error: status == 'error', active: status == 'warning'}">
        <td class="col-md-4" v-html="key"></td>
        <td class="col-md-12 text-truncate">{{renderValue}}</td>
        <td class="col-md-2">{{localeString()}}</td>
        <td class="col-md-2">
            <button class="btn btn-xs btn-primary" @click="openKey()">Edit</button>
            <button @click="deleteString()" type="button"
                    v-if="$store.state.permissions.delete_translations"
                    class="btn btn-xs btn-danger">
                Delete
            </button>
            <button @click="resetToFile()" class="btn btn-xs btn-default" v-if="hasChanges()">
                Reset
            </button>
        </td>
    </tr>
</template>

<script>
    import _ from 'lodash';
    import laroute from '../laroute';
    import Modal from './modal.vue';
    import {mapActions} from 'vuex';

    import ManagerMixin from '../mixins/managers';
    export default {
        mixins: [ManagerMixin],
        props: ['definition'],
        data(){
            return {
                status: false,
                formError: false,
                newKeys: [],
                createError: false,
            }
        },
        computed: {
            defaultLocale() {
                // The laravel manager will always hold the default locale
                return this.$store.state.laravel.defaultLocale;
            },
            key() {
                if (this.$route.meta.type == 'local') {
                    return this.definition.key;
                }

                return `<small>(#${this.definition.key})</small> ${this.definition.meta.identifier}`;
            },
            renderValue() {
                return (this.$route.meta.type == 'local') ?
                        this.definition.locales[defaultLocale].string.value :
                        this.definition.locales[defaultLocale].string[this.definition.meta.render_value].value
            }
        },
        methods: {
            loadGroup(payload){
                // (re)load the group translations
                this.$store.dispatch(this.manager.action('loadGroup'), payload);
            },
            localeString() {
                // Return a list of locales for the specified string
                let filledLocales;
                if (this.$route.meta.type == 'local') {
                    filledLocales = _.reject(
                                    this.definition.locales,
                                    (l) => {
                                        return (l.string == null || l.string.value == null || l.string.value == '');
                                    }
                            );
                }
                else {
                    filledLocales = _.reject(
                                    this.definition.locales,
                                    (l) => {
                                let filled = 0;
                    if (l.string == null || (l.string.value && l.string.value == null))
                        return;

                    _.keys(l.string).forEach((string) => {
                        if(l.string[string].value != null && l.string[string].value != ''
                )
                    {
                        filled++;
                    }
                });
                    return filled == 0;
                }
                )
                    ;
                }


                if (filledLocales.length == 0)
                    return '';

                return _.map(filledLocales, (l) => {
                            return l.locale;
            }).
                join(', ');
            },
            locale() {
                return _.keys(this.definition.locales);
            },
            hasChanges() {
                return _.size(_.reject(this.definition.locales, (l) => {
                            return l.status == 0
                        })) > 0;
            },
            addNewEmptyKey() {
                this.newKeys.push({
                    value: '',
                    error: false
                });
            },
            openKey(){
                let managerType = (this.manager.namespace == 'laravel') ? 'local' : 'external';

                this.$store.commit('MODAL_EDIT/update', {
                    string: this.definition,
                    title: (managerType == 'local') ? this.definition.key : this.definition.meta.identifier,
                    locales: this.$store.state.allLocales,
                    defaultLocale: this.$store.state.laravel.defaultLocale,
                    type: managerType
                });

                var self = this;

                // Reset row editing on close (which should only happen once)
                $('#edit-string').modal('show').one('hide.bs.modal', () => {
                    self.$store.commit('MODAL_EDIT/reset');
            })
                ;
            },
            deleteString() {
                this.status = 'warning';

                this.$http
                        .delete(laroute.route('translations.keys.delete', {
                            manager: this.manager.namespace,
                            group: this.$route.params.group,
                            key: this.definition.key
                        }))
                        .then((response) => {
                            // reload the translations
                            this.loadGroup({manager: this.manager.namespace, group: this.$route.params.group});

                            // Update stats
                            this.$store.commit(this.manager.mutation('changeStats'), [
                                {type: 'keys', value: response.body.records},
                                {type: 'changed', value: response.body.changed}
                            ]);
                        })
                        .catch((response) => {
                            // server error
                        })
                        .finally(() => {
                            this.status = false;
                        })
            },
            resetToFile() {
                // Start row loader
                this.status = 'loading';

                // Send request
                this.$http
                        .post(laroute.route('translations.keys.local', {
                            manager: this.manager.namespace,
                            group: this.$route.params.group
                        }), this.definition)
                        .then((response) => {
                            switch (response.body.status) {
                                case 'success':
                                    this.rowError = -1;

                                    this.$store.commit(this.manager.mutation('changeStat'), {
                                        type: 'changed',
                                        value: response.body.changed
                                    });

                                    // reload translations
                                    this.loadGroup({group: this.$route.params.group});
                                    this.status = false;

                                    break;
                                case 'error':
                                    //this.formError = response.body.message;
                                    console.log(response.body.message);
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
            }
        }
    }
</script>
