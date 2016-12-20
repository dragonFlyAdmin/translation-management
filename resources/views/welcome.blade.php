<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>Laravel translator</title>

        <!-- Style -->
        <link href="https://bootswatch.com/yeti/bootstrap.min.css" rel="stylesheet" type="text/css">

    </head>
    <body>
        <div class="container">
            <div class="row">
                <div class="col-md-12">
                    <h2>Translation manager</h2>
                </div>
            </div>
            <div class="row">
                <div id="translation-app"></div>
            </div>
        </div>
        <script>
            window.defaultLocale = '{{$defaultLocale}}';
            window.locales = {!! json_encode($locales, JSON_OBJECT_AS_ARRAY) !!};
            window.groups = {!! json_encode($groups) !!};
            window.stats = {!! json_encode($stats) !!};
            window.features = {!! json_encode($features) !!};
            window.managers = {!! json_encode($managers) !!};
            window.endpointUrl = '{{route('translations.dashboard')}}';
            window.csrfToken = "{{csrf_token()}}";
        </script>
        <script
                src="https://code.jquery.com/jquery-3.1.1.min.js"
                integrity="sha256-hVVnYaiADRTO2PzUGmuLJr8BLUSjGIZsDYGmIJLv2b8="
                crossorigin="anonymous"></script>
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>
        <script src="{{asset('js/translations.js')}}"></script>
    </body>
</html>
