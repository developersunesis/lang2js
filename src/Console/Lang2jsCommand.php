<?php

namespace Developersunesis\Lang2js\Console;

use Developersunesis\Lang2js\Lang2js;
use Exception;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class Lang2jsCommand extends Command
{
    private $useBasePathName = 'useBasePath';
    private $exportDirName = 'exportDir';
    private $localesDirName = 'localesDir';

    protected $signature = 'lang2js:export {exportDir} {localesDir?} {--useBasePath=true}';

    protected $description = 'Export lang files for JS use';

    /**
     * @throws Exception
     */
    public function handle(){
        $this->info('Exporting...');

        $lang2js = new Lang2js();

        if($this->option($this->useBasePathName)){
            $useBasePath = filter_var($this->option($this->useBasePathName), FILTER_VALIDATE_BOOLEAN);
            $lang2js->setUseBasePath($useBasePath);
        }

        $exportDir = $this->argument($this->exportDirName);
        $exportDir = array_reverse(explode('=', $exportDir))[0];
        $lang2js->setExportsDir($exportDir);

        $localesDir = $this->argument($this->localesDirName);
        if($localesDir){
            $localesDir = array_reverse(explode('=', $localesDir))[0];
            $lang2js->setLocalesDir($localesDir);
        }

        $lang2js->export();

        $this->info('Done Exporting!');
    }
}