(function () {

    window.laroute = laroute = (function () {

        var routes = {

            absolute: false,
            rootUrl: window.endpointUrl,
            routes : [{"host":null,"methods":["GET","HEAD"],"uri":"\/","name":"translations.dashboard","action":"DragonFly\TranslationManager\Http\Controllers\WelcomeController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/groups\/{group}\/{timestamp?}","name":"translations.groups","action":"DragonFly\TranslationManager\Http\Controllers\WelcomeController@getLoadGroupTranslations"},{"host":null,"methods":["POST"],"uri":"\/api\/{manager}\/locale","name":"translations.locale","action":"DragonFly\TranslationManager\Http\Controllers\ActionsController@postCreateLocale"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/clean","name":"translations.clean","action":"DragonFly\TranslationManager\Http\Controllers\ActionsController@getClean"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/truncate","name":"translations.truncate","action":"DragonFly\TranslationManager\Http\Controllers\ActionsController@getTruncate"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/all\/import","name":"translations.import","action":"DragonFly\TranslationManager\Http\Controllers\ActionsController@getTruncate"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/scan","name":"translations.scan","action":"DragonFly\TranslationManager\Http\Controllers\ImportController@getScan"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/import\/append","name":"translations.import.append","action":"DragonFly\TranslationManager\Http\Controllers\ImportController@getAppend"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/import\/append\/{group}","name":"translations.import.append.group","action":"DragonFly\TranslationManager\Http\Controllers\ImportController@getAppendGroup"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/import\/replace","name":"translations.import.replace","action":"DragonFly\TranslationManager\Http\Controllers\ImportController@getReplace"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/import\/replace\/{group}","name":"translations.import.replace.group","action":"DragonFly\TranslationManager\Http\Controllers\ImportController@getReplaceGroup"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/export","name":"translations.export","action":"DragonFly\TranslationManager\Http\Controllers\ExportController@getExport"},{"host":null,"methods":["GET","HEAD"],"uri":"\/api\/{manager}\/export\/{group}","name":"translations.export.group","action":"DragonFly\TranslationManager\Http\Controllers\ExportController@getExportGroup"},{"host":null,"methods":["DELETE"],"uri":"\/api\/{manager}\/keys\/{group}\/{key}","name":"translations.keys.delete","action":"DragonFly\TranslationManager\Http\Controllers\KeyController@deleteRemoveKey"},{"host":null,"methods":["POST"],"uri":"\/api\/{manager}\/keys\/{group}","name":"translations.keys.update","action":"DragonFly\TranslationManager\Http\Controllers\KeyController@postSaveTranslation"},{"host":null,"methods":["POST"],"uri":"\/api\/{manager}\/keys\/{group}\/create","name":"translations.keys.create","action":"DragonFly\TranslationManager\Http\Controllers\KeyController@postCreateKeys"},{"host":null,"methods":["POST"],"uri":"\/api\/{manager}\/keys\/{group}\/local","name":"translations.keys.local","action":"DragonFly\TranslationManager\Http\Controllers\KeyController@postReplaceWithLocal"},{"host":null,"methods":["GET","HEAD"],"uri":"api\/user","name":null,"action":"Closure"},{"host":null,"methods":["GET","HEAD"],"uri":"\/","name":null,"action":"App\Http\Controllers\TranslationController@getIndex"},{"host":null,"methods":["GET","HEAD"],"uri":"translations\/groups\/{group}\/{timestamp?}","name":null,"action":"App\Http\Controllers\TranslationController@loadGroupTranslations"}],
            prefix: '',

            route : function (name, parameters, route) {
                route = route || this.getByName(name);

                if ( ! route ) {
                    return undefined;
                }

                return this.toRoute(route, parameters);
            },

            url: function (url, parameters) {
                parameters = parameters || [];

                var uri = url + '/' + parameters.join('/');

                return this.getCorrectUrl(uri);
            },

            toRoute : function (route, parameters) {
                var uri = this.replaceNamedParameters(route.uri, parameters);
                var qs  = this.getRouteQueryString(parameters);

                return this.getCorrectUrl(uri + qs);
            },

            replaceNamedParameters : function (uri, parameters) {
                uri = uri.replace(/\{(.*?)\??\}/g, function(match, key) {
                    if (parameters.hasOwnProperty(key)) {
                        var value = parameters[key];
                        delete parameters[key];
                        return value;
                    } else {
                        return match;
                    }
                });

                // Strip out any optional parameters that were not given
                uri = uri.replace(/\/\{.*?\?\}/g, '');

                return uri;
            },

            getRouteQueryString : function (parameters) {
                var qs = [];
                for (var key in parameters) {
                    if (parameters.hasOwnProperty(key)) {
                        qs.push(key + '=' + parameters[key]);
                    }
                }

                if (qs.length < 1) {
                    return '';
                }

                return '?' + qs.join('&');
            },

            getByName : function (name) {
                for (var key in this.routes) {
                    if (this.routes.hasOwnProperty(key) && this.routes[key].name === name) {
                        return this.routes[key];
                    }
                }
            },

            getByAction : function(action) {
                for (var key in this.routes) {
                    if (this.routes.hasOwnProperty(key) && this.routes[key].action === action) {
                        return this.routes[key];
                    }
                }
            },

            getCorrectUrl: function (uri) {
                var url = this.prefix + '/' + uri.replace(/^\/?/, '');

                return this.rootUrl.replace('/\/?$/', '') + url;
            }
        };

        var getLinkAttributes = function(attributes) {
            if ( ! attributes) {
                return '';
            }

            var attrs = [];
            for (var key in attributes) {
                if (attributes.hasOwnProperty(key)) {
                    attrs.push(key + '="' + attributes[key] + '"');
                }
            }

            return attrs.join(' ');
        };

        var getHtmlLink = function (url, title, attributes) {
            title      = title || url;
            attributes = getLinkAttributes(attributes);

            return '<a href="' + url + '" ' + attributes + '>' + title + '</a>';
        };

        return {
            // Generate a url for a given controller action.
            // laroute.action('HomeController@getIndex', [params = {}])
            action : function (name, parameters) {
                parameters = parameters || {};

                return routes.route(name, parameters, routes.getByAction(name));
            },

            // Generate a url for a given named route.
            // laroute.route('routeName', [params = {}])
            route : function (route, parameters) {
                parameters = parameters || {};

                return routes.route(route, parameters);
            },

            // Generate a fully qualified URL to the given path.
            // laroute.route('url', [params = {}])
            url : function (route, parameters) {
                parameters = parameters || {};

                return routes.url(route, parameters);
            },

            // Generate a html link to the given url.
            // laroute.link_to('foo/bar', [title = url], [attributes = {}])
            link_to : function (url, title, attributes) {
                url = this.url(url);

                return getHtmlLink(url, title, attributes);
            },

            // Generate a html link to the given route.
            // laroute.link_to_route('route.name', [title=url], [parameters = {}], [attributes = {}])
            link_to_route : function (route, title, parameters, attributes) {
                var url = this.route(route, parameters);

                return getHtmlLink(url, title, attributes);
            },

            // Generate a html link to the given controller action.
            // laroute.link_to_action('HomeController@getIndex', [title=url], [parameters = {}], [attributes = {}])
            link_to_action : function(action, title, parameters, attributes) {
                var url = this.action(action, parameters);

                return getHtmlLink(url, title, attributes);
            }

        };

    }).call(this);

    /**
     * Expose the class either via AMD, CommonJS or the global object
     */
    if (typeof define === 'function' && define.amd) {
        define(function () {
            return laroute;
        });
    }
    else if (typeof module === 'object' && module.exports){
        module.exports = laroute;
    }
    else {
        window.laroute = laroute;
    }

}).call(this);

