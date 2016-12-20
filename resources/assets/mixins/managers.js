export default {
    data(){
        return {
            $managers: false,
            manager: false,
        }
    },
    created(){
        // Shortcut to all available managers
        this.$managers = this.$store.state.managers;

        // Set the manager for easy access if it's defined as a meta property in the route
        this.syncRouteMeta();
    },
    watch: {
        // Call again the syncRouteMeta method if the route changes
        // This way we keep the current manager up to date
        '$route': 'syncRouteMeta'
    },
    methods: {
        syncRouteMeta() {
            if (this.$route.meta.manager) {
                this.manager = this.$managers[this.$route.meta.manager];
            }
        }
    }
}