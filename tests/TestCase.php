<?php

namespace Developersunesis\Lang2js\Tests;

use Developersunesis\Lang2js\Lang2JsServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    public function setUp(): void
    {
        parent::setUp();
        // additional setup
    }

    protected function getPackageProviders($app)
    {
        // make package provider available
        return [
            Lang2JsServiceProvider::class
        ];
    }

    protected function getEnvironmentSetUp($app)
    {
        // perform environment setup
    }
}

