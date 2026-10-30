<?php

namespace Tests\Language;

use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ViewTextTest extends TestCase
{
    /**
     * Literal text that is not words to translate, such as file names.
     */
    private const ALLOWED = ['.env'];

    /**
     * Text a visitor can see or hear must come from lang(), so views
     * may hold no literal words outside PHP.
     */
    #[DataProvider('provideViewHasNoLiteralText')]
    public function testViewHasNoLiteralText(string $path): void
    {
        $html = file_get_contents($path);
        $html = preg_replace('/<\?(?:php|=).*?(?:\?>|\z)/s', ' ', $html);
        $html = preg_replace('/<(script|style)\b.*?<\/\1>|<!--.*?-->|&#?\w+;/s', ' ', $html);

        $found = [];
        $tag   = '/<[!\/]?[a-zA-Z][^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*>/';

        preg_match_all($tag, $html, $tags);

        foreach ($tags[0] as $element) {
            preg_match_all('/\s(?:label|title|description|placeholder|aria-label|alt|hint|save|backLabel)="([^"]*[a-zA-Z]{2}[^"]*)"/', $element, $attributes);
            array_push($found, ...$attributes[1]);
        }

        foreach (preg_split($tag, $html) as $text) {
            if (preg_match('/[a-zA-Z]{2}/', $text) && ! in_array(trim($text), self::ALLOWED, true)) {
                $found[] = trim($text);
            }
        }

        $this->assertSame([], $found);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideViewHasNoLiteralText(): iterable
    {
        $root  = dirname(__DIR__, 2);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/src"));

        foreach ($files as $file) {
            if ($file->getExtension() === 'php' && str_contains($file->getPathname(), '/Views/')) {
                yield substr($file->getPathname(), strlen($root) + 1) => [$file->getPathname()];
            }
        }
    }
}
