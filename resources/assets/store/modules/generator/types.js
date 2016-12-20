export default {
    types: false,
    convert(namespace){
        return {
            mutations: {
                placeGroup: namespace+'/PLACE_GROUP',
                mergeGroup: namespace+'/MERGE_GROUP',
                registerGroups: namespace+'/REGISTER_GROUPS',
                setGroups: namespace+'/SET_GROUPS',
                markGroupSaved: namespace+'/MARK_GROUP_SAVED',
                switchGroup: namespace+'/SWITCH_GROUP',
                switchLocale: namespace+'/SWITCH_LOCALE',
                addLocale: namespace+'/ADD_LOCALE',
                setLocales: namespace+'/SET_LOCALES',
                changeStat: namespace+'/CHANGE_STAT',
                changeStats: namespace+'/CHANGE_STATS',
                updateString: namespace+'/UPDATE_STRING',
                showMenuItem: namespace+'/SHOW_MENU_ITEM'
            },
            actions: {
                loadGroup: namespace+'/PLACE_GROUP',
            },
            getters: {
                groupCount: namespace+'/GROUP_COUNT',
            }
        }
    },
    retrieve(ns)
    {
        return this.convert(ns);
    }
}