<?php

namespace Massaal\Dusha\Compilers;

use Illuminate\Support\Collection;

abstract class Compiler
{
    public function __construct(protected Collection $manifest) {}

    abstract public function compile(string $content, string $filePath): string;
}
