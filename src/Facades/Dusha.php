<?php

namespace Massaal\Dusha\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Massaal\Dusha\Dusha
 */
class Dusha extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Massaal\Dusha\Dusha::class;
    }
}
