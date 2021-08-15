<?php

namespace Developersunesis\Lang2js;

use Exception;
use Illuminate\Support\Str;
use JShrink\Minifier;

class Lang2js
{

    private $locales_path, $exports_path, $use_base_path, $js_export_index_name;

    // Environment names
    private $_locales_path_name = 'lang2js.locale.path';
    private $_exports_path_name = 'lang2js.export.path';
    private $_use_base_path_name = 'lang2js.use.base.path';
    private $_js_export_index_file_name = 'lang2js.export.index.name';

    private $_use_files_extension = ['php', 'json'];
    private $name_prefix = 'LANG2JS_';

    function __construct()
    {
        $this->use_base_path = env($this->_use_base_path_name, true);
        $this->locales_path = env($this->_locales_path_name, "/resources/lang");
        $this->exports_path = env($this->_exports_path_name);
        $this->js_export_index_name = env($this->_js_export_index_file_name, 'lang2js.min.js');
    }

    /**
     * @throws Exception
     */
    public function toJS($exports_path = null)
    {
        if (isset($exports_path)) {
            $this->exports_path = $exports_path;
        }

        if (!isset($this->exports_path)) {
            throw new Exception("Path to export translations not found. 
            Please add an export location, you can specify this in your environment as:
            $this->_exports_path_name=/path/to/export/to/here");
        }

        $_locales_path = $this->toBasePath($this->locales_path);
        $_exports_path = $this->exports_path;

        if (!file_exists($_exports_path) && !mkdir($_exports_path, 0777, true)) {
            throw new Exception("Unable to successfully create an export directory in: $_exports_path");
        }

        if (!is_dir($_exports_path)) {
            throw new Exception("Specified export path is not a directory: $_exports_path");
        }

        if (!is_writable($_exports_path)) {
            throw new Exception("Cannot write to specified export directory: $_exports_path");
        }

        if (!file_exists($_exports_path)) {
            throw new Exception("Specified locale path does not exist: $_locales_path");
        }

        if (!is_dir($_locales_path)) {
            throw new Exception("Specified locale path is not a directory: $_locales_path");
        }

        // Collect all files paths in the locale directory to an array
        $locale_file_paths = $this->findAllFiles($_locales_path);
        $allowedExtensions = $this->_use_files_extension;
        $locale_file_paths = array_filter($locale_file_paths, function ($value) use ($allowedExtensions, $_locales_path) {
            try {
                $path_parts = pathinfo("$_locales_path/$value");
                return in_array($path_parts['extension'], $allowedExtensions);
            } catch (Exception $ignored) {
            }
            return false;
        });

        // In the export folder, create an index.min.js file that handles
        // useful functions to utilize each locale files that will be created
        $this->createExportIndexFile();

        // Export all locale content now
        $this->createJSExportForFiles($locale_file_paths);
        dd($locale_file_paths);
    }

    /**
     * @throws Exception
     */
    private function toBasePath($path)
    {
        if ($this->use_base_path) {
            $base_path = base_path();
            $path = "$base_path/$path";
        }

        $new_path = realpath($path);
        if ($new_path) return $new_path;
        else throw new Exception("Bad directory path specified: $path");
    }

    /**
     * @throws Exception
     */
    private function createExportIndexFile()
    {
        // Read the content of a generic lang2js.skeleton.js file
        $contents = $this->getResourceFile('lang2js.skeleton.js');

        // We aim to add available locales constant to the contents
        $provideAvailableLocales = $this->getAvailableLocales();
        $availableLocalesString = '';
        foreach($provideAvailableLocales as $locale){
            // We are replacing this following string '$AVAILABLE_LOCALES' in the generic lang2js.skeleton.js
            $availableLocalesString .= "'$locale', ";
        }
        // Remove the last , added to the string if any
        $availableLocalesString = Str::replaceLast(',', '', $availableLocalesString);
        $contents = Str::replaceFirst('\'$AVAILABLE_LOCALES\'', $availableLocalesString, $contents);

        // Minify contents
        $contents = Minifier::minify($contents, array('flaggedComments' => true));

        // Overwrite existing
        $this->createFileInExports($this->js_export_index_name, $contents);
    }

    /**
     * @throws Exception
     */
    private function getAvailableLocales(){
        $available_locales = scandir($this->getLocalesPath());
        $available_locales = array_filter($available_locales, function($value) {
            return (Str::contains($value, '.json')
                || !Str::contains($value, '.'));
//                && strlen($value) == 2;
        });
        return array_map(function($value) {
            $value = str_replace('.json', '', $value);
            return "$this->name_prefix$value";
        }, $available_locales);
    }

    /**
     * @throws Exception
     */
    private function createJSExportForFiles(array $locale_file_paths)
    {
        $skeleton = $this->getResourceFile('export.skeleton.js');
        $locales = $this->getAvailableLocales();
        sort($locale_file_paths);
        foreach ($locales as $_locale) {
            $locale = $_locale;
            $content = array();
            $locale = str_replace($this->name_prefix, '', $locale);
            $locale_path = $this->getLocalesPath();
            $locale_path = "$locale_path/$locale";
            foreach ($locale_file_paths as $filepath) {
                if(Str::startsWith($filepath, $locale_path)) {
                    $path_parts = pathinfo($filepath);
                    $filename = $path_parts['filename'];
                    $extension = $path_parts['extension'];
                    $new_values = [];
                    if($extension == 'php'){
                        $file_array_content = include($filepath);
                        $new_values = $this->addLocaleExportContent($file_array_content, $filename);
                    } else if($extension == 'json'){
                        $file_array_content = file_get_contents($filepath);
                        $file_array_content = json_decode($file_array_content, true);
                        $new_values = $this->addLocaleExportContent($file_array_content);
                    }

                    $content = array_merge($content, $new_values);
                }
            }
            $content = json_encode($content);
            $file_content = $skeleton;
            $file_content = Str::replaceFirst('$LOCALE_NAME', $_locale, $file_content);
            $file_content = Str::replaceFirst('\'$LOCALE_CONTENT\'', $content, $file_content);
            $this->createFileInExports("$locale.min.js", $file_content);
//            $this->createJSExportFromPHP($filepath, $filename);
//            $this->createJSExportFromJSON($filepath, $filename);
        }
    }

    /**
     * @throws Exception
     */
    private function addLocaleExportContent($input, $filename=null)
    {
        $new_array = [];
        if(is_array($input)){
            foreach ($input as $key=>$item){
                $new_name = $filename ? "$filename.$key" : $key;
                $new_array[$new_name] = $item;
            }
        }
        return $new_array;
    }

    /**
     * @throws Exception
     */
    private function getResourceFile($filename){
        $path = __DIR__;
        $path = "$path/Resources/$filename";
        if (!$handle = fopen($path, 'r')) {
            throw new Exception("Cannot read default file ; $path");
        }
        return fread($handle, filesize($path));
    }

    /**
     * @throws Exception
     */
    private function createFileInExports($filename, $contents){
        $path = $this->getExportPath();
        $path = "$path/$filename";
        if (file_exists($path)) unlink($path);
        if (!$handle = fopen($path, 'x+')) {
            throw new Exception("Cannot create or read file ; $path");
        }

        if (fwrite($handle, $contents) === false) {
            throw new Exception("Error creating $filename file ; $path");
        }
    }

    /**
     * https://www.php.net/manual/en/function.scandir.php#107117
     * @param $dir
     * @return array
     */
    function findAllFiles($dir)
    {
        $result = array();
        $root = scandir($dir);
        foreach ($root as $value) {
            if ($value === '.' || $value === '..') {
                continue;
            }
            if (is_file("$dir/$value")) {
                $result[] = "$dir/$value";
                continue;
            }
            foreach ($this->findAllFiles("$dir/$value") as $value2) {
                $result[] = $value2;
            }
        }
        return $result;
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getLocalesPath()
    {
        return $this->toBasePath($this->locales_path);
    }

    /**
     * @return mixed
     * @throws Exception
     */
    public function getExportPath()
    {
        return $this->toBasePath($this->exports_path);
    }

    /**
     * @return mixed
     */
    public function getUseBasePath()
    {
        return $this->use_base_path;
    }

    /**
     * @return mixed
     */
    public function getJsExportIndexName()
    {
        return $this->js_export_index_name;
    }

    /**
     * @param mixed $js_export_index_name
     */
    public function setJsExportIndexName($js_export_index_name)
    {
        $this->js_export_index_name = $js_export_index_name;
    }

    /**
     * @param mixed $use_base_path
     */
    public function setUseBasePath($use_base_path)
    {
        $this->use_base_path = $use_base_path;
    }

    /**
     * @param $path
     */
    public function setLocalesPath($path)
    {
        $this->locales_path = $path;
    }

    /**
     * @param $path
     */
    public function setExportFilePath($path)
    {
        $this->exports_path = $path;
    }
}