<?php

namespace Massaal\Dusha\Traits;

trait BuildsHtmlAttributes
{
    protected function parseAttributes(array $attributes): string
    {
        return collect($attributes)
            ->filter(fn($value) => $value !== false)
            ->map(function ($value, $key) {
                if ($value === true) {
                    return $key;
                }

                return \sprintf('%s="%s"', $key, e($value));
            })
            ->implode(" ");
    }
}
