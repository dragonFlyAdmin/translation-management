# Translation manager


[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]

A customizable laravel translation management SPA built with vue.

## Install

Via Composer

``` bash
$ composer require DragonFly/TranslationManager
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
mix..webpack('dragonfly/translations/app.js', './public/js/translations.js');
```

Now everything's set to compile, let's run gulp

``` bash
$ gulp
```

Publish the view (optional)

``` bash
$ php artisan vendor:publish --tag=view
```

## Usage

``` php
$skeleton = new DragonFly\TranslationManager();
echo $skeleton->echoPhrase('Hello, League!');
```

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

[ico-version]: https://img.shields.io/packagist/v/DragonFly/TranslationManager.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/DragonFly/translation-management/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/DragonFly/TranslationManager.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/DragonFly/TranslationManager.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/DragonFly/TranslationManager.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/DragonFly/TranslationManager
[link-travis]: https://travis-ci.org/DragonFly/TranslationManager
[link-scrutinizer]: https://scrutinizer-ci.com/g/DragonFly/TranslationManager/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/DragonFly/TranslationManager
[link-downloads]: https://packagist.org/packages/DragonFly/TranslationManager
[link-author]: https://github.com/happyDemon
[link-contributors]: ../../contributors
