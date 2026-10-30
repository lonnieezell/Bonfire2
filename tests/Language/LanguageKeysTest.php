<?php

namespace Tests\Language;

use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LanguageKeysTest extends TestCase
{
    /**
     * Every literal lang('File.key') call must resolve to an English line.
     * lang() hands the key back unchanged when it can't find one.
     */
    #[DataProvider('provideLangKeyResolves')]
    public function testLangKeyResolves(string $key, string $file): void
    {
        $this->assertNotSame($key, lang($key), "{$key} in {$file} has no English line.");
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function provideLangKeyResolves(): iterable
    {
        $root = dirname(__DIR__, 2);

        foreach (['src', 'themes'] as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}"));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Language/')) {
                    continue;
                }

                preg_match_all('/\blang\(\s*[\'"]([A-Z]\w*\.[\w.]+)[\'"]\s*[,)]/', file_get_contents($file->getPathname()), $matches);

                $relative = substr($file->getPathname(), strlen($root) + 1);

                foreach (array_unique($matches[1]) as $key) {
                    yield "{$key} ({$relative})" => [$key, $relative];
                }
            }
        }
    }
}
