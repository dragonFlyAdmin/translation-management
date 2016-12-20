import _ from 'lodash';

export const groupCount = (state) => {
    return _.keys(state.groups).length - 1;
};