<?php

namespace Massaal\Dusha\Components;

use Illuminate\Support\Facades\File;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use InvalidArgumentException;
use Massaal\Dusha\Traits\BuildsHtmlAttributes;

class Css extends Component
{
    use BuildsHtmlAttributes;

    public function __construct(
        public ?string $src = null,
        public bool $all = false,
    ) {}

    public function render(): HtmlString
    {
        if ($this->all && $this->src) {
            throw new InvalidArgumentException(
                'Cannot use both "src" and "all" attributes',
            );
        }

        if (!$this->all && !$this->src) {
            throw new InvalidArgumentException(
                'Either "src" or "all" attribute is required',
            );
        }

        if ($this->all) {
            return $this->renderAllTags();
        }

        return $this->renderTag();
    }

    private function renderAllTags(): HtmlString
    {
        $manifest = dusha_manifest();

        if (empty($manifest)) {
            $sourcePath = base_path(config("dusha.source_path"));

            $paths = collect(File::allFiles($sourcePath))
                ->map(fn($file) => $file->getRelativePathname())
                ->filter(fn($path) => str_ends_with($path, ".css"))
                ->map(fn($path) => config("dusha.source_path") . "/" . $path)
                ->sort();
        } else {
            $paths = collect($manifest)
                ->keys()
                ->filter(fn($path) => str_ends_with($path, ".css"))
                ->sort();
        }

        $tags = $paths
            ->map(fn($path) => $this->buildTag(dusha($path)))
            ->implode("\n");

        return new HtmlString($tags);
    }

    private function renderTag(): HtmlString
    {
        $path = dusha($this->src);

        return new HtmlString($this->buildTag($path));
    }

    private function buildTag(string $href): string
    {
        $attributes = $this->parseAttributes([
            "rel" => "stylesheet",
            "href" => $href,
        ]);

        return "<link {$attributes}>";
    }
}
