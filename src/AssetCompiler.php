<?php

namespace Massaal\Dusha;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use SplFileInfo;

class AssetCompiler
{
    public function compile(): void
    {
        $this->ensureOutputDirectory();

        $files = $this->getAssetFiles();

        $manifest = $files->mapWithKeys(function (SplFileInfo $file) {
            return [$this->relativePath($file), $this->digest($file)];
        });

        $this->writeManifest($manifest);
    }

    protected function ensureOutputDirectory(): void
    {
        File::ensureDirectoryExists(config("dusha.output_path"));
        File::cleanDirectory(config("dusha.output_path"));
    }

    protected function getAssetFiles(): Collection
    {
        return collect(File::allFiles(config("dusha.source_path")))->filter(
            fn(SplFileInfo $file) => in_array(
                $file->getExtension(),
                config("dusha.extensions"),
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
        $hash = substr(md5($content), 0, 8);

        $name = str($file->getFilename())
            ->beforeLast(".")
            ->append("-", $hash, ".", $file->getExtension())
            ->toString();

        File::put(config("dusha.output_path") . "/" . $name, $content);

        return "/assets/" . $name;
    }

    protected function writeManifest(Collection $manifest): void
    {
        File::put(
            config("dusha.output_path") . "/.manifest.json",
            $manifest->toPrettyJson(),
        );
    }
}
