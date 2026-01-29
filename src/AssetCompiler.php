<?php

namespace Massaal\Dusha;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use SplFileInfo;

class AssetCompiler
{
    public function compile(): int
    {
        $this->ensureOutputDirectory();

        $files = $this->getAssetFiles();

        $manifest = $files->mapWithKeys(function (SplFileInfo $file) {
            return [$this->relativePath($file) => $this->digest($file)];
        });

        $this->writeManifest($manifest);

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

    protected function writeManifest(Collection $manifest): void
    {
        File::put(
            public_path(config("dusha.output_path")) . "/.manifest.json",
            $manifest->toPrettyJson(),
        );
    }
}
