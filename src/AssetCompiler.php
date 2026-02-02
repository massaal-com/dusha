<?php

namespace Massaal\Dusha;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Massaal\Dusha\Compilers\CssUrlCompiler;
use SplFileInfo;

use function Illuminate\Filesystem\{join_paths};

class AssetCompiler
{
    private Collection $manifest;
    private readonly string $sourcePath;
    private readonly string $outputPath;

    public function __construct()
    {
        $this->sourcePath = config("dusha.source_path");
        $this->outputPath = config("dusha.output_path");
    }

    public function compile(): int
    {
        $this->ensureOutputDirectory();
        $files = $this->getAssetFiles();

        [$cssFiles, $otherFiles] = $files->partition(
            fn(SplFileInfo $file) => $file->getExtension() === "css",
        );

        $this->manifest = $otherFiles->mapWithKeys(
            fn(SplFileInfo $file) => [
                $this->relativePath($file) => $this->digest($file),
            ],
        );

        $cssManifest = $this->compileCssFiles($cssFiles);

        $this->writeManifest($cssManifest);

        return $files->count();
    }

    protected function compileCssFiles(Collection $cssFiles): Collection
    {
        $compiler = new CssUrlCompiler($this->manifest);

        return $cssFiles->mapWithKeys(function (SplFileInfo $file) use (
            $compiler,
        ) {
            return [
                $this->relativePath($file) => $this->digestCss(
                    $file,
                    $compiler,
                ),
            ];
        });
    }

    protected function ensureOutputDirectory(): void
    {
        File::ensureDirectoryExists(public_path($this->outputPath));
        File::cleanDirectory(public_path($this->outputPath));
    }

    protected function getAssetFiles(): Collection
    {
        $paths = config("dusha.paths");
        $extensions = config("dusha.extensions");

        return collect($paths)
            ->map(fn(string $path) => join_paths($this->sourcePath, $path))
            ->filter(fn(string $path) => File::isDirectory($path))
            ->flatMap(fn(string $path) => File::allFiles($path))
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
            ->after("$this->sourcePath/")
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

        $outputPath = public_path($this->outputPath);

        if ($directory !== ".") {
            $fullOutputPath = join_paths($outputPath, $directory, $name);
            File::ensureDirectoryExists(join_paths($outputPath, $directory));
        } else {
            $fullOutputPath = join_paths($outputPath, $name);
        }

        File::put($fullOutputPath, $content);

        $urlBase = "/$this->outputPath";

        if ($directory !== ".") {
            return join_paths($urlBase, $directory, $name);
        }

        return join_paths($urlBase, $name);
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

        $manifestPath = join_paths(
            public_path($this->outputPath),
            ".manifest.json",
        );

        File::put($manifestPath, $this->manifest->toPrettyJson());
    }
}
