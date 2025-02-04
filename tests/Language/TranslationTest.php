<?php

namespace Tests\Language;

use CodeIgniter\Test\CIUnitTestCase;

class TranslationTest extends CIUnitTestCase
{
    protected $englishFiles = [];
    protected $languageFiles = [];

    protected function setUp(): void
    {
        parent::setUp();

        // Path to the language directory
        $languageDir = BFPATH . 'Language';

        // Get all language files for the main app
        foreach (glob($languageDir . '/*/Bonfire.php') as $file) {
            $lang = basename(dirname($file));
            $this->languageFiles['Bonfire'][$lang][] = $file;

            if ($lang === 'en') {
                $this->englishFiles['Bonfire'] = $file;
            }
        }

        // Discover core modules and their language files
        $this->getCoreModulesLangFiles();
    }

    private function getCoreModulesLangFiles()
    {
        $modules = [];
        // TODO: only include modules with Language directory, no excludes
        $excluded = ['Core', 'Config', 'Language', 'Views'];

        $map = directory_map(BFPATH, 1);

        foreach ($map as $row) {
            if (substr($row, -1) !== DIRECTORY_SEPARATOR) {
                continue;
            }

            $name = trim($row, DIRECTORY_SEPARATOR);
            if (in_array($name, $excluded, true)) {
                continue;
            }

            $modules["Bonfire\\{$name}"] = BFPATH . "{$name}";
        }

        // Get language files for each module
        foreach ($modules as $dir) {
            $languageDir = $dir . '/Language';

            if (is_dir($languageDir)) {
                foreach (glob($languageDir . '/*/*.php') as $file) {
                    $lang = basename(dirname($file));
                    $module = basename($dir);
                    $this->languageFiles[$module][$lang][] = $file;

                    if ($lang === 'en') {
                        $this->englishFiles[$module] = $file;
                    }
                }
                //return;
            }
        }
    }

    public function testLanguageFilesReturnValidArray()
    {
        foreach ($this->languageFiles as $languages) {
            foreach ($languages as $files) {
                foreach ($files as $file) {
                    $translations = include $file;
                    $this->assertIsArray($translations, "The file $file does not return a valid array.");
                }
            }
        }
    }

    public function testLanguageFilesHaveSameKeysAsEnglish()
    {
        $allMissingKeys = [];
        $allExtraKeys = [];

        foreach ($this->englishFiles as $module => $englishFile) {
            if (!is_string($englishFile) || !file_exists($englishFile)) {
                $this->fail("English language file for module '$module' does not exist or is not a valid file path: $englishFile");
            }

            $englishTranslations = include $englishFile;
            if (!is_array($englishTranslations)) {
                $this->fail("English language file for module '$module' does not return a valid array: $englishFile");
            }

            foreach ($this->languageFiles[$module] as $lang => $files) {
                if (!isset($files[0]) || !is_string($files[0]) || !file_exists($files[0])) {
                    $this->fail("Missing or invalid language file for module '$module' in language '$lang'.");
                }

                $translations = include $files[0];
                if (!is_array($translations)) {
                    $this->fail("Language file for module '$module' in language '$lang' does not return a valid array: {$files[0]}");
                }

                $translations = $this->includeTodoTranslations($files[0], $translations);

                $missingKeys = array_diff_key($englishTranslations, $translations);
                $extraKeys = array_diff_key($translations, $englishTranslations);

                if (!empty($missingKeys)) {
                    $allMissingKeys[] = "Module '$module' in language '$lang': The file {$files[0]} is missing keys: " . implode(', ', array_keys($missingKeys)) . PHP_EOL . ' You may run "composer lang-update" to initiate missing keys' ;
                }

                if (!empty($extraKeys)) {
                    $allExtraKeys[] = "Module '$module' in language '$lang': The file {$files[0]} has extra keys: " . implode(', ', array_keys($extraKeys));
                }
            }
        }

        if (!empty($allMissingKeys)) {
            $this->fail(implode("\n", $allMissingKeys));
        }

        if (!empty($allExtraKeys)) {
            $this->fail(implode("\n", $allExtraKeys));
        }
    }

    private function includeTodoTranslations($file, $translations)
    {
        $content = file_get_contents($file);
        preg_match_all("/\/\/\s*'([^']+)'\s*=>\s*'([^']+)',\s*\/\/\s*TODO:\s*please\s*translate/", $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $translations[$match[1]] = $match[2];
        }

        return $translations;
    }
}
