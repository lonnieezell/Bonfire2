<?php

namespace Tests\Content;

use PHPUnit\Framework\TestCase;

/**
 * Checks what `mkdocs build` warns about for the Content pages: pages missing from the nav, and links that don't resolve.
 *
 * @internal
 */
final class ContentDocsTest extends TestCase
{
    private const DOCS = __DIR__ . '/../../docs/';

    public function testEveryContentPageIsInTheNav(): void
    {
        $pages = array_map(
            static fn (string $file): string => 'content/' . basename($file),
            glob(self::DOCS . 'content/*.md'),
        );

        preg_match_all('#^\s+- (content/\S+\.md)$#m', (string) file_get_contents(__DIR__ . '/../../mkdocs.yml'), $matches);

        $this->assertNotSame([], $pages);
        $this->assertEqualsCanonicalizing($pages, $matches[1]);
        $this->assertSame(array_unique($matches[1]), $matches[1], 'A page is in the nav twice.');
    }

    public function testEveryLinkInTheContentPagesResolves(): void
    {
        $broken = [];

        foreach (glob(self::DOCS . 'content/*.md') as $page) {
            $text = $this->withoutCode((string) file_get_contents($page));

            preg_match_all('/\]\(([^)\s]+)\)/', $text, $links);

            foreach ($links[1] as $link) {
                if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $link) === 1) {
                    continue;
                }

                [$path, $anchor] = array_pad(explode('#', $link, 2), 2, null);
                $target          = $path === '' ? $page : realpath(dirname($page) . '/' . $path);

                if ($target === false || ! is_file($target)) {
                    $broken[] = basename($page) . " -> {$link}";

                    continue;
                }

                if ($anchor !== null && ! in_array($anchor, $this->anchors($target), true)) {
                    $broken[] = basename($page) . " -> {$link}";
                }
            }
        }

        $this->assertSame([], $broken);
    }

    /**
     * The ids of a page's headings, as MkDocs makes them.
     *
     * @return list<string>
     */
    private function anchors(string $file): array
    {
        preg_match_all('/^#{1,6}\s+(.+?)\s*$/m', $this->withoutCode((string) file_get_contents($file)), $headings);

        return array_map(static function (string $heading): string {
            $heading = strtolower(trim(preg_replace('/[`*_]/', '', $heading)));
            $heading = preg_replace('/[^\w\s-]/', '', $heading);

            return preg_replace('/[\s-]+/', '-', $heading);
        }, $headings[1]);
    }

    /**
     * Drops fenced code, where a `#` is a comment and `](` may be PHP.
     */
    private function withoutCode(string $markdown): string
    {
        return (string) preg_replace('/^```.*?^```$/ms', '', $markdown);
    }
}
