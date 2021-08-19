<?php

namespace Developersunesis\Lang2js\Tests\Unit;

use Developersunesis\Lang2js\Facades\Lang2Js as L2J;
use Developersunesis\Lang2js\Lang2Js;
use Developersunesis\Lang2js\Tests\TestCase;
use Exception;
use Illuminate\Support\Facades\Artisan;

class Lang2jsTest extends TestCase
{

    private $currentDirectory = __DIR__;

    /**
     * @throws Exception
     */
    public function testIsResolvingFolderPaths()
    {
        $lang2js = new Lang2js();
        $lang2js->setUseBasePath(false);
        $lang2js->setLocalesDir("$this->currentDirectory/../Resources/lang");
        $lang2js->setExportsDir("$this->currentDirectory/../Resources/exports");

        // confirm paths are being resolved
        $this->assertStringNotContainsString('..', $lang2js->getLocalesDir());
        $this->assertStringNotContainsString('..', $lang2js->getExportsDir());
    }

    /**
     * @throws Exception
     */
    public function testIsAllLocaleBeingPicked()
    {
        $lang2js = new Lang2js();
        $lang2js->setUseBasePath(false);
        $lang2js->setLocalesDir("$this->currentDirectory/../Resources/lang");
        $lang2js->setExportsDir("$this->currentDirectory/../Resources/exports");

        $expectedAvailableLocale = array('en', 'fr');
        $availableLocales = $lang2js->getAvailableLocales(false);

        self::assertIsArray($availableLocales);

        sort($expectedAvailableLocale);
        sort($availableLocales);

        self::assertTrue(implode($expectedAvailableLocale) == implode($availableLocales));
    }

    /**
     * @throws Exception
     */
    public function testIsFileBeingCreated_UseBasePathIsFalse()
    {
        $lang2js = new Lang2js();
        $lang2js->setUseBasePath(false);
        $lang2js->setLocalesDir("$this->currentDirectory/../Resources/lang");
        $lang2js->setExportsDir("$this->currentDirectory/../Resources/exports");

        // confirm paths are being resolved
        $this->assertStringNotContainsString('..', $lang2js->getExportsDir());

        // export file is being created
        $this->assertTrue(file_exists($lang2js->getExportsDir()));

        $lang2js->export();

        $availableLocales = $lang2js->getAvailableLocales(false);
        $availableLocales = array_map(function ($value) {
            return "$value.min.js";
        }, $availableLocales);
        $availableFiles = scandir($lang2js->getExportsDir());

        foreach ($availableLocales as $locale) {
            self::assertContains($locale, $availableFiles);
        }
    }

    /**
     * @throws Exception
     */
    public function testIsFileBeingCreated_UseBasePathIsTrue()
    {
        $lang2js = new Lang2js();
        $lang2js->setExportsDir("resources/exports");

        // confirm paths are being resolved
        $this->assertStringNotContainsString('..', $lang2js->getExportsDir());

        // export file is being created
        $this->assertTrue(file_exists($lang2js->getExportsDir()));

        $lang2js->export();

        $availableLocales = ['en', 'fr'];
        $availableLocales = array_map(function($value){
            return "$value.min.js";
        }, $availableLocales);
        $availableFiles = scandir($lang2js->getExportsDir());

        foreach($availableLocales as $locale){
            self::assertContains($locale, $availableFiles);
        }
    }

    function testCommandInterface(){
        $importPath = "$this->currentDirectory/../Resources/lang";
        $exportPath = "$this->currentDirectory/../Resources/cmd_exports";

        Artisan::call("lang2js:export --exportDir=$exportPath --importDir=$importPath --ubp");

        $availableLocales = ['en', 'fr'];
        $availableLocales = array_map(function($value){
            return "$value.min.js";
        }, $availableLocales);

        $exportPath = realpath($exportPath);
        $availableFiles = scandir($exportPath);

        foreach($availableLocales as $locale){
            self::assertContains($locale, $availableFiles);
        }
    }
}