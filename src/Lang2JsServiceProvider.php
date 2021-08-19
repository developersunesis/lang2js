<?php

namespace Developersunesis\Lang2js;

use Illuminate\Support\ServiceProvider;

class Lang2JsServiceProvider extends ServiceProvider
{

    public function register()
    {
        //
        $this->app->bind('Lang2Js', function ($app){
            return new Lang2Js();
        });
    }

    public function boot()
    {
        //
    }
}