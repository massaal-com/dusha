<?php
use Illuminate\Support\Facades\File;

if (!function_exists("dusha")) {
    function dusha(string $path): string
    {
        $manifest = once(function () {
            $path =
                public_path(config("dusha.output_path")) . "/.manifest.json";

            return File::exists($path)
                ? json_decode(File::get($path), true)
                : [];
        });

        return asset($manifest[$path] ?? "assets/{$path}");
    }
}
