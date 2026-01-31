<?php

namespace Massaal\Dusha\Compilers;

class CssUrlCompiler extends Compiler
{
    private const string URL_PATTERN = '/url\(\s*["\']?(?!(?:data:|https?:|\/\/|\/))([^"\')\s]+)["\']?\s*\)/i';

    public function compile(string $content, string $filePath): string
    {
        $directory = dirname($filePath);

        return preg_replace_callback(
            self::URL_PATTERN,
            fn(array $matches) => $this->rewriteUrl($matches, $directory),
            $content,
        );
    }

    private function rewriteUrl(array $matches, string $directory): string
    {
        $resolved = $this->resolvePath($matches[1], $directory);

        if ($this->manifest->has($resolved)) {
            return 'url("' . $this->manifest->get($resolved) . '")';
        }

        return $matches[0];
    }

    private function resolvePath(string $url, string $directory): string
    {
        $url = preg_replace("/^\.\//", "", $url);
        $parts = explode("/", $directory . "/" . $url);
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
}
