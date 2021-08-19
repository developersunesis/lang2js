<?php

namespace Developersunesis\Lang2js;

use Developersunesis\Lang2js\Console\Lang2jsCommand;
use Illuminate\Support\ServiceProvider;

class Lang2jsServiceProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Register the command if we are using the application via the CLI
//        if ($this->app->runningInConsole()) {
            $this->commands([
                Lang2jsCommand::class,
            ]);
//        }
    }
}