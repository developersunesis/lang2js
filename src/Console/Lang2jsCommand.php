<?php

namespace Developersunesis\Lang2js\Console;

use Developersunesis\Lang2js\Lang2js;
use Exception;
use Illuminate\Console\Command;

class Lang2jsCommand extends Command
{
    protected $signature = 'lang2js:export {localesDir?} {exportDir} {--ubp=true}';

    protected $description = 'Export lang files for JS use';

    /**
     * @throws Exception
     */
    public function handle(){
        $this->info('Exporting...');

        $lang2js = new Lang2js();
        $lang2js->setExportsDir($this->argument('exportDir'));

        if($this->argument('localesDir')){
            $lang2js->setLocalesDir($this->argument('localesDir'));
        }

        if($this->argument('--ubp')){
            $lang2js->setUseBasePath((bool)$this->argument('ubp'));
        }

        $lang2js->export();

        $this->info('Done Exporting!');
    }
}