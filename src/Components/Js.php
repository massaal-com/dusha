<?php

namespace Massaal\Dusha\Components;

use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Massaal\Dusha\Traits\BuildsHtmlAttributes;

class Js extends Component
{
    use BuildsHtmlAttributes;

    public function __construct(public string $src) {}

    public function render(): HtmlString
    {
        $path = dusha($this->src);

        $attributes = $this->parseAttributes([
            "src" => $path,
        ]);

        return new HtmlString("<script {$attributes}></script>");
    }
}
