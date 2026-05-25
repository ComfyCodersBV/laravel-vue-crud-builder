<?php

namespace TranquilTools\CrudBuilder\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \TranquilTools\CrudBuilder\CrudBuilder
 */
class CrudBuilder extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \TranquilTools\CrudBuilder\CrudBuilder::class;
    }
}
