# No-build asset management for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/massaal-com/dusha.svg?style=flat-square)](https://packagist.org/packages/massaal-com/dusha)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/massaal-com/dusha/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/massaal-com/dusha/actions?query=workflow%3Arun-tests+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/massaal-com/dusha.svg?style=flat-square)](https://packagist.org/packages/massaal-com/dusha)

> [!WARNING]
> This package is currently in development.
> The API is subject to change until a stable release.

Dusha provides simple asset management for Laravel without requiring Node.js, Webpack, or Vite. It copies your assets to the public directory with content-based hashes for cache-busting, so browsers always load the latest version after updates.

```blade
<x-dusha-css src="resources/assets/css/app.css" />
<!-- Outputs: <link rel="stylesheet" href="/assets/css/app-a1b2c3d4.css"> -->
```

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
        "otf",
    ],

    /*
     * Length of digest hash (default: 8)
     */
    "digest_length" => 8,

    /*
     * Rewrite relative URLs in CSS files to use hashed asset paths
     */
    "css_url_rewriting" => true,
];
```

You can publish the blade components with:

```bash
php artisan vendor:publish --tag="dusha-components"
```

## Usage

### Compiling Assets

Place your assets in the source directory (default: `resources/assets/`) organized by type:

```
resources/assets/
├── css/
│   └── app.css
├── js/
│   └── app.js
├── images/
│   └── logo.png
└── fonts/
    └── custom.woff2
```

Compile your assets using the Artisan command:

```bash
php artisan dusha:compile
```

This will:

- Copy assets to `public/assets/`
- Add content-based hashes for cache-busting (e.g., `app-a1b2c3d4.css`)
- Generate a `.manifest.json` file for path resolution

### Including Assets in Views

Use the Blade components in your templates:

```blade
{{-- Single file --}}
<x-dusha-css src="resources/assets/css/app.css" />
<x-dusha-js src="resources/assets/js/app.js" />

{{-- Load all CSS files --}}
<x-dusha-css all />
```

Or use the `dusha()` helper function directly:

```blade
<link rel="stylesheet" href="{{ dusha('resources/assets/css/app.css') }}">
<script src="{{ dusha('resources/assets/js/app.js') }}"></script>
<img src="{{ dusha('resources/assets/images/logo.png') }}" alt="Logo">
```

The components and helper automatically resolve the hashed filename from the manifest.

### Clearing Compiled Assets

To remove all compiled assets:

```bash
php artisan dusha:clear
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
