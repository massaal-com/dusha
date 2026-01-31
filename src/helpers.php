<?php

use Illuminate\Support\Facades\File;

if (!function_exists("dusha_manifest")) {
    function dusha_manifest(): array
    {
        return once(function () {
            $path =
                public_path(config("dusha.output_path")) . "/.manifest.json";

            return File::exists($path)
                ? json_decode(File::get($path), true)
                : [];
        });
    }
}

if (!function_exists("dusha")) {
    function dusha(string $path): string
    {
        $manifest = dusha_manifest();

        if (empty($manifest)) {
            $source = base_path($path);

            $mtime = file_exists($source) ? filemtime($source) : time();

            return asset($path) . "?v=" . $mtime;
        }

        return asset($manifest[$path] ?? $path);
    }
}

if (!function_exists("dusha_css")) {
    function dusha_css(string|array $paths): void
    {
        foreach ((array) $paths as $path) {
            echo '<link rel="stylesheet" href="' . dusha($path) . '">' . "\n";
        }
    }
}

if (!function_exists("dusha_js")) {
    function dusha_js(string|array $paths): void
    {
        foreach ((array) $paths as $path) {
            echo '<script src="' . dusha($path) . '" defer></script>' . "\n";
        }
    }
}

if (!function_exists("dusha_css_all")) {
    function dusha_css_all(): void
    {
        $manifest = dusha_manifest();

        if (empty($manifest)) {
            $sourcePath = base_path(config("dusha.source_path"));

            $paths = collect(File::allFiles($sourcePath))
                ->map(fn($file) => $file->getRelativePathname())
                ->filter(fn($path) => str_ends_with($path, ".css"))
                ->map(fn($path) => config("dusha.source_path") . "/" . $path)
                ->sort()
                ->all();
        } else {
            $paths = collect($manifest)
                ->keys()
                ->filter(fn($path) => str_ends_with($path, ".css"))
                ->sort()
                ->all();
        }

        dusha_css($paths);
    }
}
