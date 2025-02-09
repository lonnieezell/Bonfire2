<?php

require 'vendor/autoload.php';

use CodeIgniter\CLI\CLI;

// Define ENVIRONMENT if not already defined
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'development');
}

// Define ROOTPATH if not already defined
if (!defined('ROOTPATH')) {
    define('ROOTPATH', __DIR__ . '../' . DIRECTORY_SEPARATOR);
}

// Define BFPATH if not already defined
if (!defined('BFPATH')) {
    define('BFPATH', __DIR__ . '/../src/');
}

// Bootstrap CodeIgniter
require_once __DIR__ . '/../vendor/codeigniter4/framework/app/Config/Paths.php';
$paths = new Config\Paths();

// Verify the system directory path
if (!is_dir($paths->systemDirectory)) {
    CLI::error("System directory not found: " . $paths->systemDirectory);
    exit(1);
}

require_once $paths->systemDirectory . '/Common.php';
require_once $paths->appDirectory . '/Config/Constants.php';
require_once $paths->appDirectory . '/Config/Logger.php';
require_once $paths->appDirectory . '/Config/Services.php';

// Load the framework helpers
require_once $paths->systemDirectory . '/Helpers/url_helper.php';
require_once $paths->systemDirectory . '/Helpers/filesystem_helper.php';

// Load the framework bootstrap file
require_once $paths->systemDirectory . '/Boot.php';

class LangStringsExtract
{
    protected $description = 'Scans the project and updates language files with strings extracted from the code.';

    public function run()
    {
        $defaultLang = 'en';

        // Check for 'other' option
        global $argv;
        if (in_array('other', $argv)) {
            $this->handleOtherOption($defaultLang);
            return;
        }

        // Discover modules with Language directory
        $modules = $this->discoverModules(BFPATH);

        foreach ($modules as $module) {
            // Extract language strings for each module
            $strings = $this->findTranslations(BFPATH . $module, $module);

            // Update language files for each module
            $this->updateLanguageFiles(BFPATH . $module . '/Language/' . $defaultLang, $defaultLang, $strings, $module);
        }

        CLI::write('Language files updated successfully.', 'green');
    }

    private function discoverModules($baseDir)
    {
        $modules = [];
        $iterator = new \DirectoryIterator($baseDir);

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $moduleDir = $fileinfo->getFilename();
                if (is_dir($baseDir . $moduleDir . '/Language')) {
                    $modules[] = $moduleDir;
                }
            }
        }

        return $modules;
    }

    private function findTranslations($dir, $module)
    {
        $strings = [];

        // Scan the directory for PHP files
        $files = $this->getPhpFiles($dir);

        foreach ($files as $file) {
            $fileStrings = $this->findTranslationsInFile($file, $module);
            $strings = array_merge_recursive($strings, $fileStrings);
        }

        return $strings;
    }

    private function getPhpFiles($dir)
    {
        $files = [];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir));

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function findTranslationsInFile($file, $module)
    {
        $foundLanguageKeys = [];
        $badLanguageKeys = [];

        if (is_string($file) && is_file($file)) {
            $file = new \SplFileInfo($file);
        }

        $fileContent = file_get_contents($file->getRealPath());
        preg_match_all('/lang\(\s*\'([._a-z0-9\-]+)\'\s*\)/ui', $fileContent, $matches);

        if ($matches[1] === []) {
            return $foundLanguageKeys;
        }

        foreach ($matches[1] as $phraseKey) {
            // Skip keys that do not match the module
            if (strpos($phraseKey, $module . '.') !== 0) {
                continue;
            }

            $phraseKeys = explode('.', $phraseKey);

            // Language key not have Filename or Lang key
            if (count($phraseKeys) < 2) {
                $badLanguageKeys[] = [mb_substr($file->getRealPath(), mb_strlen(ROOTPATH)), $phraseKey];
                continue;
            }

            $languageFileName = array_shift($phraseKeys);
            $isEmptyNestedArray = ($languageFileName !== '' && $phraseKeys[0] === '')
                || ($languageFileName === '' && $phraseKeys[0] !== '')
                || ($languageFileName === '' && $phraseKeys[0] === '');

            if ($isEmptyNestedArray) {
                $badLanguageKeys[] = [mb_substr($file->getRealPath(), mb_strlen(ROOTPATH)), $phraseKey];
                continue;
            }

            if (count($phraseKeys) === 1) {
                $foundLanguageKeys[$languageFileName][$phraseKeys[0]] = $phraseKey;
            } else {
                $childKeys = $this->buildMultiArray($phraseKeys, $phraseKey);
                $foundLanguageKeys[$languageFileName] = array_replace_recursive($foundLanguageKeys[$languageFileName] ?? [], $childKeys);
            }
        }

        return $foundLanguageKeys;
    }

    private function buildMultiArray(array $fromKeys, string $lastArrayValue = ''): array
    {
        $newArray = [];
        $lastIndex = array_pop($fromKeys);
        $current = &$newArray;

        foreach ($fromKeys as $value) {
            $current[$value] = [];
            $current = &$current[$value];
        }

        $current[$lastIndex] = $lastArrayValue;

        return $newArray;
    }

    private function updateLanguageFiles($languageDir, $defaultLang, $strings, $module)
    {
        $langFile = $languageDir . '/' . $module . '.php';

        if (!file_exists($langFile)) {
            CLI::error("Language file not found: $langFile");
            return;
        }

        $translations = include $langFile;

        $allExist = true;
        foreach ($strings as $keys) {
            foreach ($keys as $key => $value) {
                // Skip keys defined in other language files
                if (strpos($key, '.') !== false) {
                    continue;
                }

                if (!isset($translations[$key])) {
                    $allExist = false;
                    break 2;
                }
            }
        }

        if ($allExist) {
            CLI::write("All strings already exist in: $langFile", 'yellow');
            return;
        }

        foreach ($strings as $file => $keys) {
            foreach ($keys as $key => $value) {
                // Skip keys defined in other language files
                if (strpos($key, '.') !== false) {
                    continue;
                }

                if (!isset($translations[$key])) {
                    $translations[$key] = $value;
                }
            }
        }

        // Read the existing content to preserve the header
        $existingContent = file_get_contents($langFile);
        $header = '';
        if (preg_match('/^(.*?return\s+)/s', $existingContent, $matches)) {
            $header = $matches[1];
        }

        // Format the array in the default style
        $content = $header . "[\n";
        $content .= $this->formatArray($translations);
        $content .= "];\n";

        file_put_contents($langFile, $content);

        CLI::write("Updated language file: $langFile", 'green');
    }

    private function formatArray(array $array, int $level = 1): string
    {
        $indent = str_repeat('    ', $level);
        $content = '';

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $content .= "{$indent}'{$key}' => [\n";
                $content .= $this->formatArray($value, $level + 1);
                $content .= "{$indent}],\n";
            } else {
                if (strpos($value, " // TODO: please translate") !== false) {
                    // Correctly format the commented out keys
                    $content .= "{$indent}// '{$key}' => '" . str_replace(" // TODO: please translate", "", $value) . "', // TODO: please translate\n";
                } else {
                    // Escape single quotes in the value
                    $escapedValue = str_replace("'", "\\'", $value);
                    $content .= "{$indent}'{$key}' => '{$escapedValue}',\n";
                }
            }
        }

        return $content;
    }

    private function handleOtherOption($defaultLang)
    {
        // Get a list of all languages available
        $languages = $this->getAvailableLanguages();

        // Discover modules with Language directory
        $modules = $this->discoverModules(BFPATH);

        foreach ($modules as $module) {
            foreach ($languages as $lang) {
                // Skip the default language as a target
                if ($lang === $defaultLang) {
                    continue;
                }

                $langDir = BFPATH . $module . '/Language/' . $lang;
                $langFile = $langDir . '/' . $module . '.php';

                // Check if the language file exists; if not, create it
                if (!file_exists($langFile)) {
                    if (!is_dir($langDir)) {
                        mkdir($langDir, 0777, true);
                    }
                    $this->createEmptyLanguageFile($langFile);
                }

                // Compare each language to English and transfer missing keys
                $this->compareAndUpdateLanguageFile($langDir, $defaultLang, $lang, $module);
            }
        }

        CLI::write('Language files updated successfully for other languages.', 'green');
    }

    private function getAvailableLanguages()
    {
        $languages = [];
        $iterator = new \DirectoryIterator(BFPATH . 'Language');

        foreach ($iterator as $fileinfo) {
            if ($fileinfo->isDir() && !$fileinfo->isDot()) {
                $languages[] = $fileinfo->getFilename();
            }
        }

        return $languages;
    }

    private function createEmptyLanguageFile($langFile)
    {
        $header = "<?php\n\n/**\n * This file is part of Bonfire.\n *\n * (c) Lonnie Ezell <lonnieje@gmail.com>\n *\n * For the full copyright and license information, please view\n * the LICENSE file that was distributed with this source code.\n */\n\n";
        $content = $header . "return [\n];\n";
        file_put_contents($langFile, $content);
    }

    private function compareAndUpdateLanguageFile($langDir, $defaultLang, $lang, $module)
    {
        $defaultLangFile = BFPATH . $module . '/Language/' . $defaultLang . '/' . $module . '.php';
        $langFile = $langDir . '/' . $module . '.php';

        if (!file_exists($defaultLangFile)) {
            CLI::error("Default language file not found: $defaultLangFile");
            return;
        }

        $defaultTranslations = include $defaultLangFile;
        $translations = include $langFile;

        $updatedTranslations = $translations;
        $newKeysAdded = false;

        foreach ($defaultTranslations as $key => $value) {
            if (!isset($translations[$key])) {
                $updatedTranslations[$key] = $value . " // TODO: please translate";
                $newKeysAdded = true;
            }
        }

        // Skip file if no new keys are added
        if (!$newKeysAdded) {
            CLI::write("No new keys to add for: $langFile", 'yellow');
            return;
        }

        // Read the existing content to preserve the header
        $existingContent = file_get_contents($langFile);
        $header = '';
        if (preg_match('/^(.*?return\s+)/s', $existingContent, $matches)) {
            $header = $matches[1];
        }

        // Format the array in the default style
        $content = $header . "[\n";
        $content .= $this->formatArray($updatedTranslations);
        $content .= "];\n";

        file_put_contents($langFile, $content);

        CLI::write("Updated language file: $langFile", 'green');
    }
}

// Run the script
$extractor = new LangStringsExtract();
$extractor->run();
