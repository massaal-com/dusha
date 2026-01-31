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
        // dependency graph
        $graph = [];
        foreach ($cssFiles as $file) {
            $path = $this->relativePath($file);
            $content = File::get($file);
            $compiler = new CssUrlCompiler(collect());
            $graph[$path] = $compiler->references($content, $path);
        }

        $sorted = $this->topologicalSort($graph);

        $cssManifest = collect();
        $cssByPath = $cssFiles->keyBy(fn($file) => $this->relativePath($file));

        foreach ($sorted as $path) {
            if (!isset($cssByPath[$path])) {
                continue;
            }

            $compiler = new CssUrlCompiler(
                $this->manifest->merge($cssManifest),
            );
            $hashedPath = $this->digestCss($cssByPath[$path], $compiler);
            $cssManifest->put($path, $hashedPath);
        }

        return $cssManifest;
    }

    protected function topologicalSort(array $graph): array
    {
        $sorted = [];
        $visited = [];
        $visiting = [];

        $visit = function ($node) use (
            &$visit,
            &$sorted,
            &$visited,
            &$visiting,
            $graph,
        ) {
            if (isset($visited[$node])) {
                return;
            }
            if (isset($visiting[$node])) {
                throw new \RuntimeException("Circular CSS dependency: $node");
            }

            $visiting[$node] = true;
            foreach ($graph[$node] ?? [] as $dep) {
                if (isset($graph[$dep])) {
                    $visit($dep);
                }
            }
            unset($visiting[$node]);
            $visited[$node] = true;
            $sorted[] = $node;
        };

        foreach (array_keys($graph) as $node) {
            $visit($node);
        }

        return $sorted;
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
            $resultPath = "/" . $outputDir . "/" . $directory . "/" . $name;
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
