<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;

Route::get(config("dusha.source_path") . "/{path}", function (string $path) {
    $fullPath = base_path(config("dusha.source_path") . "/" . $path);

    abort_unless(File::exists($fullPath), 404);

    $extension = pathinfo($fullPath, PATHINFO_EXTENSION);

    $type = match ($extension) {
        "css" => "text/css",
        "js" => "application/javascript",
        "png" => "image/png",
        "jpg", "jpeg" => "image/jpeg",
        "svg" => "image/svg+xml",
        "woff" => "font/woff",
        "woff2" => "font/woff2",
        "ttf" => "font/ttf",
        "otf" => "font/otf",
        default => "application/octet-stream",
    };

    return response()->file($fullPath, [
        "Content-Type" => $type,
        "Cache-Control" => "no-cache, no-store, must-revalidate",
    ]);
})
    ->where("path", ".*")
    ->name("dusha.dev-asset");
