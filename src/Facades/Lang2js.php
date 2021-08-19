<?php

namespace Developersunesis\Lang2js\Facades;

use Illuminate\Support\Facades\Facade;

class Lang2js extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'lang2js';
    }
}