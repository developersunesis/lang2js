<?php

namespace Developersunesis\Lang2js;

use Exception;
use Illuminate\Support\Str;
use JShrink\Minifier;

class Lang2Js
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
        // If this value wasn't provided at all then the developer probably
        // wants the library to use the base_url()
        $this->use_base_path = env($this->_use_base_path_name, true);

        // If not specified, use the conventional laravel lang location
        $this->locales_path = env($this->_locales_path_name, "/resources/lang");

        // Must be specified either using env variable or the setter
        $this->exports_path = env($this->_exports_path_name);

        // Allow customization of the utils file generated
        // if not specified use default lang2js.min.js
        $this->js_export_index_name = env($this->_js_export_index_file_name, 'lang2js.min.js');
    }

    /**
     * This function copies the Laravel lang from the specified import folder to
     * the exports_path
     * @return $this
     * @param null $exports_path The path where the extracted files go to
     * @throws Exception
     */
    public function export($exports_path = null)
    {
        if (isset($exports_path)) {
            $this->exports_path = $exports_path;
        }

        if (!isset($this->exports_path)) {
            throw new Exception("Path to export translations not found. 
            Please add an export location, you can specify this in your environment as:
            $this->_exports_path_name=/path/to/export/to/here");
        }

        // Convert the locales_path to a proper file location
        $_locales_path = $this->toBaseDir($this->locales_path);
        $_exports_path = $this->toBaseDir($this->exports_path);

        // Start : Interaction with the specified locales_path and exports_path
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
        // End : Interaction with the specified locales_path and exports_path

        // Collect all file paths in the locale directory to an array
        $locale_file_paths = $this->findAllFilesInDir($_locales_path);
        $allowedExtensions = $this->_use_files_extension;

        // Filter out files that are not in the allowedExtensions
        $locale_file_paths = array_filter($locale_file_paths, function ($value) use ($allowedExtensions, $_locales_path) {
            try {
                $path_parts = pathinfo("$_locales_path/$value");
                return in_array($path_parts['extension'], $allowedExtensions);
            } catch (Exception $ignored) {
            }
            return false;
        });

        // In the export folder, create an index.min.js file
        // useful functions to utilize each locale files that will be created
        $this->createExportIndexFile();

        // Export all locales content now to usable js
        $this->createJSExportForFiles($locale_file_paths);

        return $this;
    }

    /**
     * Takes a simple path and converts to an absolute path for use
     * @param $path
     * @return string
     * @throws Exception
     */
    private function toBaseDir($path): string
    {
        if ($this->use_base_path) {
            $base_path = base_path();
            $path = "$base_path/$path";
        }

        $new_path = realpath($path);
        if ($new_path) return $new_path;
        else if (mkdir($path, 0777, true))
            return $this->toBaseDir($path);
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

        // Generate the AVAILABLE_LOCALE object's content
        // i.e. const AVAILABLE_LOCALES={'LANG2JS_en':LANG2JS_en,'LANG2JS_fr':LANG2JS_fr}
        foreach ($provideAvailableLocales as $locale) {
            // In attempt to replace ('$AVAILABLE_LOCALES') in the generic lang2js.skeleton.js
            $availableLocalesString .= "'$locale': $locale, ";
        }

        // Remove the last , added to the if any
        $availableLocalesString = Str::replaceLast(',', '', $availableLocalesString);

        // Replace '$AVAILABLE_LOCALES' now
        $contents = Str::replaceFirst('\'$AVAILABLE_LOCALES\':\'\'', $availableLocalesString, $contents);

        // Replace $PREFIX with specified on
        $contents = Str::replaceFirst('$PREFIX', $this->name_prefix, $contents);

        // Minify contents of the js file
        $contents = Minifier::minify($contents, array('flaggedComments' => true));

        // Overwrite existing files in the export path with new one
        $this->createFileInExports($this->js_export_index_name, $contents);
    }

    /**
     * @param bool $prefixed
     * @return array
     * @throws Exception
     */
    public function getAvailableLocales(bool $prefixed = true): array
    {
        $available_locales = scandir($this->getLocalesDir());
        $available_locales = array_filter($available_locales, function ($value) {
            return (Str::contains($value, '.json')
                || !Str::contains($value, '.'));
        });
        return array_map(function ($value) use ($prefixed) {
            $value = str_replace('.json', '', $value);
            return $prefixed ? "$this->name_prefix$value" : $value;
        }, $available_locales);
    }

    /**
     * @param array $locale_file_paths
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
            $locale_path = $this->getLocalesDir();
            $locale_path = "$locale_path/$locale";

            foreach ($locale_file_paths as $filepath) {
                if (Str::startsWith($filepath, $locale_path)) {
                    $path_parts = pathinfo($filepath);
                    $filename = $path_parts['filename'];
                    $extension = $path_parts['extension'];
                    $new_values = [];
                    if ($extension == 'php') {
                        $file_array_content = include($filepath);
                        $new_values = $this->addLocaleExportContent($file_array_content, $filename);
                    } else if ($extension == 'json') {
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
        }
    }

    /**
     * @param $input
     * @param null $filename
     * @return array
     */
    private function addLocaleExportContent($input, $filename = null): array
    {
        $new_array = [];
        if (is_array($input)) {
            foreach ($input as $key => $item) {
                $new_name = $filename ? "$filename.$key" : $key;
                $new_array[$new_name] = $item;
            }
        }
        return $new_array;
    }

    /**
     * @param $filename
     * @return false|string
     * @throws Exception
     */
    private function getResourceFile($filename)
    {
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
    private function createFileInExports($filename, $contents)
    {
        $path = $this->getExportsDir();
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
     * Source:: https://www.php.net/manual/en/function.scandir.php#107117
     * Scan through a folder an get a list of all the files in the folder
     * @param $dir
     * @return array
     */
    function findAllFilesInDir($dir): array
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
            foreach ($this->findAllFilesInDir("$dir/$value") as $value2) {
                $result[] = $value2;
            }
        }
        return $result;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getLocalesDir(): string
    {
        return $this->toBaseDir($this->locales_path);
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getExportsDir(): string
    {
        return $this->toBaseDir($this->exports_path);
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
     * @param $js_export_index_name
     * @return $this
     */
    public function setJsExportIndexName($js_export_index_name)
    {
        $this->js_export_index_name = $js_export_index_name;
        return $this;
    }

    /**
     * @param mixed $use_base_path
     */
    public function setUseBasePath($use_base_path)
    {
        $this->use_base_path = $use_base_path;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setLocalesDir($path)
    {
        $this->locales_path = $path;
        return $this;
    }

    /**
     * @param $path
     * @return $this
     */
    public function setExportsDir($path)
    {
        $this->exports_path = $path;
        return $this;
    }
}