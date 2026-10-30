<?php

namespace Tests\Language;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * @internal
 */
final class ViewTextTest extends TestCase
{
    /**
     * Literal text that is not words to translate, such as file names.
     */
    private const ALLOWED = ['.env', '.log ·'];

    /**
     * Attributes, of HTML and of the admin components, that hold text a visitor sees or hears.
     */
    private const TEXT_ATTRIBUTES = 'label|title|subtitle|description|placeholder|aria-label|alt|hint|save|backLabel';

    /**
     * Text a visitor can see or hear must come from lang(), so views
     * may hold no literal words outside PHP. Text built inside PHP,
     * like an array of labels, is stripped before the scan and so is not checked.
     */
    #[DataProvider('provideViewHasNoLiteralText')]
    public function testViewHasNoLiteralText(string $path): void
    {
        $html = file_get_contents($path);
        $html = preg_replace('/<\?(?:php|=).*?(?:\?>|\z)/s', ' ', $html);
        $html = preg_replace('/<(script|style)\b.*?<\/\1>|<!--.*?-->|&#?\w+;/s', ' ', $html);

        $tag = '/<[!\/]?[a-zA-Z][^>"\']*(?:(?:"[^"]*"|\'[^\']*\')[^>"\']*)*>/';

        preg_match_all($tag, $html, $tags);
        preg_match_all('/\s(?:' . self::TEXT_ATTRIBUTES . ')=(?:"([^"]*)"|\'([^\']*)\')/', implode(' ', $tags[0]), $attributes);

        $texts = array_map(trim(...), [...$attributes[1], ...$attributes[2], ...preg_split($tag, $html)]);
        $found = array_filter($texts, static fn ($text) => preg_match('/[a-zA-Z]{2}/', $text) && ! in_array($text, self::ALLOWED, true));

        $this->assertSame([], array_values($found));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideViewHasNoLiteralText(): iterable
    {
        $root  = dirname(__DIR__, 2);
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("{$root}/src"));

        foreach ($files as $file) {
            // Generator templates (.tpl.php) write code into apps; they are never rendered
            if ($file->getExtension() === 'php' && str_contains($file->getPathname(), '/Views/') && ! str_ends_with($file->getPathname(), '.tpl.php')) {
                yield substr($file->getPathname(), strlen($root) + 1) => [$file->getPathname()];
            }
        }
    }
}
