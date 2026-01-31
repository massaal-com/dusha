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

        [$css_files, $other_files] = $files->partition(
            fn(SplFileInfo $file) => $file->getExtension() === "css",
        );

        $this->manifest = $other_files->mapWithKeys(function (
            SplFileInfo $file,
        ) {
            return [$this->relativePath($file) => $this->digest($file)];
        });

        $css_manifest = $this->compileCssFiles($css_files);

        $this->writeManifest($css_manifest);

        return $files->count();
    }

    protected function compileCssFiles(Collection $css_files): Collection
    {
        // dependency graph
        $graph = [];
        foreach ($css_files as $file) {
            $path = $this->relativePath($file);
            $content = File::get($file);
            $compiler = new CssUrlCompiler(collect());
            $graph[$path] = $compiler->references($content, $path);
        }

        $sorted = $this->topologicalSort($graph);

        $css_manifest = collect();
        $css_by_path = $css_files->keyBy(fn($f) => $this->relativePath($f));

        foreach ($sorted as $path) {
            if (!isset($css_by_path[$path])) {
                continue;
            }

            $compiler = new CssUrlCompiler(
                $this->manifest->merge($css_manifest),
            );
            $hashed_path = $this->digestCss($css_by_path[$path], $compiler);
            $css_manifest->put($path, $hashed_path);
        }

        return $css_manifest;
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

    protected function digestCss(
        SplFileInfo $file,
        CssUrlCompiler $compiler,
    ): string {
        $content = File::get($file);

        if (config("dusha.css_url_rewriting")) {
            $content = $compiler->compile($content, $this->relativePath($file));
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

    protected function cssAssetsUrls(
        string $content,
        string $css_directory,
    ): array {
        preg_match_all(
            '/url\(\s*["\']?(?!(?:data:|https?:|\/\/|\/))([^"\')\s]+)["\']?\s*\)/i',
            $content,
            $matches,
        );

        return collect($matches[1])
            ->map(fn($url) => $this->resolvePath($url, $css_directory))
            ->values()
            ->all();
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
