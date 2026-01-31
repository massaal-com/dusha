<?php

namespace Massaal\Dusha;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Collection;
use Massaal\Dusha\Compilers\CssUrlCompiler;
use SplFileInfo;

class AssetCompiler
{
    private Collection $manifest;

    public function compile(): int
    {
        $this->ensureOutputDirectory();
        $files = $this->getAssetFiles();

        [$cssFiles, $otherFiles] = $files->partition(
            fn(SplFileInfo $file) => $file->getExtension() === "css",
        );

        $this->manifest = $otherFiles->mapWithKeys(function (
            SplFileInfo $file,
        ) {
            return [$this->relativePath($file) => $this->digest($file)];
        });

        $cssManifest = $this->compileCssFiles($cssFiles);

        $this->writeManifest($cssManifest);

        return $files->count();
    }

    protected function compileCssFiles(Collection $cssFiles): Collection
    {
        $compiler = new CssUrlCompiler($this->manifest);

        return $cssFiles->mapWithKeys(function (SplFileInfo $file) use ($compiler) {
            return [$this->relativePath($file) => $this->digestCss($file, $compiler)];
        });
    }

    protected function ensureOutputDirectory(): void
    {
        File::ensureDirectoryExists(public_path(config("dusha.output_path")));
        File::cleanDirectory(public_path(config("dusha.output_path")));
    }

    protected function getAssetFiles(): Collection
    {
        $sourcePath = config("dusha.source_path");
        $paths = config("dusha.paths");
        $extensions = config("dusha.extensions");

        return collect($paths)
            ->filter(
                fn(string $path) => File::isDirectory(
                    $sourcePath . "/" . $path,
                ),
            )
            ->flatMap(
                fn(string $path) => File::allFiles($sourcePath . "/" . $path),
            )
            ->filter(
                fn(SplFileInfo $file) => in_array(
                    $file->getExtension(),
                    $extensions,
                ),
            );
    }

    protected function relativePath(SplFileInfo $file): string
    {
        return str($file->getPathname())
            ->after(config("dusha.source_path") . "/")
            ->toString();
    }

    protected function writeDigestedFile(
        SplFileInfo $file,
        string $content,
    ): string {
        $hash = substr(md5($content), 0, config("dusha.digest_length"));
        $relativePath = $this->relativePath($file);
        $directory = dirname($relativePath);

        $name = str($file->getFilename())
            ->beforeLast(".")
            ->append("-", $hash, ".", $file->getExtension())
            ->toString();

        $outputPath = public_path(config("dusha.output_path"));

        $fullOutputPath = $outputPath . "/" . $name;
        if ($directory !== ".") {
            $fullOutputPath = $outputPath . "/" . $directory . "/" . $name;
            File::ensureDirectoryExists($outputPath . "/" . $directory);
        }

        File::put($fullOutputPath, $content);

        $outputDir = config("dusha.output_path");
        if ($directory !== ".") {
            return "/" . $outputDir . "/" . $directory . "/" . $name;
        }

        return "/" . $outputDir . "/" . $name;
    }

    protected function digest(SplFileInfo $file): string
    {
        return $this->writeDigestedFile($file, File::get($file));
    }

    protected function digestCss(
        SplFileInfo $file,
        CssUrlCompiler $compiler,
    ): string {
        $content = File::get($file);

        if (config("dusha.css_url_rewriting")) {
            $content = $compiler->compile($content, $this->relativePath($file));
        }

        return $this->writeDigestedFile($file, $content);
    }

    protected function writeManifest(Collection $cssManifest): void
    {
        $this->manifest = $this->manifest->merge($cssManifest);

        File::put(
            public_path(config("dusha.output_path")) . "/.manifest.json",
            $this->manifest->toPrettyJson(),
        );
    }
}
