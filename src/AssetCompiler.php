<?php

namespace Massaal\Dusha;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use SplFileInfo;

class AssetCompiler
{
    private Collection $manifest;

    public function compile(): int
    {
        $this->ensureOutputDirectory();

        $files = $this->getAssetFiles();

        [$css_files, $other_files] = $files->partition(
            fn(SplFileInfo $file) => $file->getExtension() === "css",
        );

        $this->manifest = $other_files->mapWithKeys(function (
            SplFileInfo $file,
        ) {
            return [$this->relativePath($file) => $this->digest($file)];
        });

        $css_manifest = $css_files->mapWithKeys(function (SplFileInfo $file) {
            return [
                $this->relativePath($file) => $this->digestCss($file),
            ];
        });

        $this->writeManifest($css_manifest);

        return $files->count();
    }

    protected function ensureOutputDirectory(): void
    {
        File::ensureDirectoryExists(public_path(config("dusha.output_path")));
        File::cleanDirectory(public_path(config("dusha.output_path")));
    }

    protected function getAssetFiles(): Collection
    {
        $source_path = config("dusha.source_path");
        $paths = config("dusha.paths");
        $extensions = config("dusha.extensions");

        return collect($paths)
            ->filter(
                fn(string $path) => File::isDirectory(
                    $source_path . "/" . $path,
                ),
            )
            ->flatMap(
                fn(string $path) => File::allFiles($source_path . "/" . $path),
            )
            ->filter(
                fn(SplFileInfo $file) => in_array(
                    $file->getExtension(),
                    $extensions,
                ),
            );
    }

    protected function relativePath(SplFileInfo $file)
    {
        return str($file->getPathname())
            ->after(config("dusha.source_path") . "/")
            ->toString();
    }

    protected function digest(SplFileInfo $file): string
    {
        $content = File::get($file);
        $hash = substr(md5($content), 0, config("dusha.digest_length"));

        $name = str($file->getFilename())
            ->beforeLast(".")
            ->append("-", $hash, ".", $file->getExtension())
            ->toString();

        File::put(
            public_path(config("dusha.output_path")) . "/" . $name,
            $content,
        );

        return "/" . config("dusha.output_path") . "/" . $name;
    }

    protected function digestCss(SplFileInfo $file): string
    {
        $content = File::get($file);

        if (config("dusha.css_url_rewriting")) {
            $css_directory = dirname($this->relativePath($file));
            $content = preg_replace_callback(
                '/url\(\s*["\']?(?!(?:data:|https?:|\/\/|\/))([^"\')\s]+)["\']?\s*\)/i',
                fn(array $matches) => $this->rewriteUrl(
                    $matches,
                    $css_directory,
                ),
                $content,
            );
        }

        // todo: extract duplicate code
        $hash = substr(md5($content), 0, config("dusha.digest_length"));

        $name = str($file->getFilename())
            ->beforeLast(".")
            ->append("-", $hash, ".", $file->getExtension())
            ->toString();

        File::put(
            public_path(config("dusha.output_path")) . "/" . $name,
            $content,
        );

        return "/" . config("dusha.output_path") . "/" . $name;
    }

    private function rewriteUrl(array $matches, string $css_directory): string
    {
        $resolved_path = $this->resolvePath($matches[1], $css_directory);

        $manifest = $this->manifest->toArray();
        if (isset($manifest[$resolved_path])) {
            return 'url("' . $manifest[$resolved_path] . '")';
        }

        return $matches[0];
    }

    private function resolvePath(string $url, string $css_directory): string
    {
        $url = preg_replace("/^\.\//", "", $url);
        $parts = explode("/", $css_directory . "/" . $url);
        $normalized = [];

        foreach ($parts as $part) {
            if ($part === "..") {
                array_pop($normalized);
            } elseif ($part !== "." && $part !== "") {
                $normalized[] = $part;
            }
        }

        return implode("/", $normalized);
    }

    protected function writeManifest(Collection $css_manifest): void
    {
        $this->manifest = $this->manifest->merge($css_manifest);

        File::put(
            public_path(config("dusha.output_path")) . "/.manifest.json",
            $this->manifest->toPrettyJson(),
        );
    }
}
