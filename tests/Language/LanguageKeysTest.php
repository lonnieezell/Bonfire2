<?php

namespace Tests\Language;

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
    public function testEveryLangKeyResolves(): void
    {
        $root    = dirname(__DIR__, 2);
        $missing = [];

        foreach (['src', 'themes'] as $dir) {
            $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/{$dir}"));

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), '/Language/')) {
                    continue;
                }

                preg_match_all('/\blang\(\s*[\'"]([A-Z]\w*\.[\w.]+)[\'"]\s*[,)]/', file_get_contents($file->getPathname()), $matches);

                foreach (array_unique($matches[1]) as $key) {
                    if (lang($key) === $key) {
                        $missing[] = $key . ' in ' . substr($file->getPathname(), strlen($root) + 1);
                    }
                }
            }
        }

        $this->assertSame([], $missing);
    }
}
