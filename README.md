# This is my package dusha

[![Latest Version on Packagist](https://img.shields.io/packagist/v/massaal-com/dusha.svg?style=flat-square)](https://packagist.org/packages/massaal-com/dusha)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/massaal-com/dusha/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/massaal-com/dusha/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/massaal-com/dusha/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/massaal-com/dusha/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/massaal-com/dusha.svg?style=flat-square)](https://packagist.org/packages/massaal-com/dusha)

No-build asset management for Laravel

## Installation

You can install the package via composer:

```bash
composer require massaal-com/dusha
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="dusha-config"
```

This is the contents of the published config file:

```php
return [
    /*
     * Source directory for your assets (relative to base_path)
     */
    "source_path" => "resources/assets",

    /*
     * Target directory for compiled assets (relative to public_path)
     */
    "output_path" => "assets",

    /*
     * Asset paths to compile
     */
    "paths" => ["css", "js", "images", "fonts"],

    /*
     * File extensions to process
     */
    "extensions" => [
        "css",
        "js",
        "jpg",
        "jpeg",
        "png",
        "gif",
        "svg",
        "webp",
        "woff",
        "woff2",
        "ttf",
        "eot",
        "otf",
    ],

    /*
     * Length of digest hash (default: 8)
     */
    "digest_length" => 8,
];
```

## Usage

```php
$dusha = new Massaal\Dusha();
echo $dusha->echoPhrase('Hello, Massaal!');
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Boyd Bloemsma](https://github.com/boydbloemsma)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
