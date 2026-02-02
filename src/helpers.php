<?php

use Illuminate\Support\Facades\File;
use Massaal\Dusha\AssetNotFoundException;
use Massaal\Dusha\ManifestNotFoundException;

if (!function_exists("dusha_manifest")) {
    function dusha_manifest(): array
    {
        return once(function () {
            $path =
                public_path(config("dusha.output_path")) . "/.manifest.json";

            if (File::exists($path)) {
                return json_decode(File::get($path), true);
            }

            return [];
        });
    }
}

if (!function_exists("dusha")) {
    function dusha(string $path): string
    {
        $manifest = dusha_manifest();

        if (isset($manifest[$path])) {
            return asset($manifest[$path]);
        }

        if (!empty($manifest)) {
            throw new AssetNotFoundException(
                "Asset [{$path}] not found in Dusha manifest.",
            );
        }

        if (!app()->environment("local")) {
            throw new ManifestNotFoundException(
                'Dusha manifest not found. Run "php artisan dusha:compile"',
            );
        }

        $source = base_path($path);
        $mtime = file_exists($source) ? filemtime($source) : time();

        return asset($path) . "?v=" . $mtime;
    }
}
