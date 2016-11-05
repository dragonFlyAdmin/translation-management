# Translation manager


[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

A customizable laravel translation management SPA built with vue.

This was originally planned to be just a fork of [barryvdh/laravel-translation-manager](https://github.com/barryvdh/laravel-translation-manager), but the amount of changes I made were substantial enough for me to split it off and present it to you like this.

## Install

Via Composer

``` bash
$ composer require dragonfly/translation-manager
```

Add the service provider in your `app` config.

```php
    DragonFly\TranslationManager\TranslationManagerServiceProvider::class,
```

Publish the config file

``` bash
$ php artisan vendor:publish --tag=config
```

Run migrations

``` bash
$ php artisan migrate
```

Publish the view (optional)

``` bash
$ php artisan vendor:publish --tag=view
```

#### Publish and compile assets

First let's publish the assets to `resources/assets/js/dragonfly/translations`:

``` bash
$ php artisan vendor:publish --tag=assets
```

Next up we'll need to install a few packages from NPM.
Laravel 5.3 comes bundled with Vue 2, Vue-resource 2 and Lodash 4.16, if you don't have them you need to install these as wel.

``` bash
$ npm install vuex vue-router
```

Next up you'll need to add this command to your `gulpfile.js`:

```js
mix.webpack('dragonfly/translations/app.js', './public/js/dragonfly-translations.js');
```

Now everything's set to compile, let's run gulp

``` bash
$ gulp
```

## Configuration

There a few options you can set after you've exported the `translations.php` config file.

### Routes

*routes.prefix*: Set a prefix for the translation manager's routes.
*routes.middleware*: Here you can assign extra middleware for the routes (perfect for auth restrictions).

### Features

*features.create_locales*: Toggle the possibility to create new locales.
*features.create_delete_translations*: Toggle the possibility to delete single translation keys.
*features.create_truncate_translations*: Toggle the possibility to truncate the entire translation database.

### Group exclusion

*exclude_groups*: Define translation groups you don't want to show up in this tool.

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) and [CONDUCT](CONDUCT.md) for details.

## Security

If you discover any security related issues, please email maxim@happydemon.xyz instead of using the issue tracker.

## Credits

- [Maxim Kerstens][link-author]
- [All Contributors][link-contributors]

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/dragonfly/translation-manager.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/dragonfly/translation-manager/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/dragonfly/translation-manager.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/dragonfly/translation-manager.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/dragonfly/translation-manager.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/dragonfly/translation-manager
[link-travis]: https://travis-ci.org/dragonfly/translation-manager
[link-scrutinizer]: https://scrutinizer-ci.com/g/dragonfly/translation-manager/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/dragonfly/translation-manager
[link-downloads]: https://packagist.org/packages/dragonfly/translation-manager
[link-author]: https://github.com/happyDemon
[link-contributors]: ../../contributors
