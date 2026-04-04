<?php

namespace Flouci\Laravel\Facades;

use Flouci\Laravel\FlouciClient;
use Illuminate\Support\Facades\Facade;

class Flouci extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return FlouciClient::class;
    }
}
