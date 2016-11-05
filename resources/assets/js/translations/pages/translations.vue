<template>
    <div>
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
    export default {
        created () {
            // Set the correct translation group on creation.
            this.syncRoute();

            if(this.$route.params.group) {
                this.loadGroup({group: this.$route.params.group});
            }
        },
        watch: {
            // call again the syncRoute method if the route changes
            '$route': 'syncRoute'
        },
        data(){
            return {
                activeGroup: ''
            }
        },
        methods: {
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
                this.loadGroup({group: this.activeGroup})

                // Load the specified group
                this.$router.push({
                    name: 'group',
                    params: {
                        group: this.activeGroup
                    }
                });
            }
        }
    }
</script>
