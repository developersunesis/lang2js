<?php

namespace Developersunesis\Lang2js\Tests\Unit;

use Developersunesis\Lang2js\Lang2js;
use Developersunesis\Lang2js\Tests\TestCase;

class Lang2jsTest extends TestCase {

    private $currentDirectory = __DIR__;

    public function test(){
        $lang2js = new Lang2js();
        $lang2js->setUseBasePath(false);
        $lang2js->setLocalesPath("$this->currentDirectory/../Resources/lang");
        $lang2js->setExportFilePath("$this->currentDirectory/../Resources/exports");
        $lang2js->toJS();
        dd($lang2js->getLocaleFilesPath());
    }
}