<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Docs;

use Bonfire\Docs\Libraries\Documentation;
use Bonfire\Docs\Libraries\MarkdownRenderer;
use Tests\Support\TestCase;

/**
 * The package's own mkdocs.yml and docs/, read the way an install reads them.
 *
 * @internal
 */
final class RealDocsTest extends TestCase
{
    public function testEveryInternalLinkAndAnchorResolves(): void
    {
        $docs  = new Documentation(dirname(BFPATH) . '/', 'development');
        $pages = [];

        foreach ($this->navPaths() as $path) {
            $uri = MarkdownRenderer::uriFor($path);

            $page = $docs->page($uri);
            $this->assertNotNull($page, $path);
            $pages[$uri] = $page['html'];
        }

        $base   = site_url(ADMIN_AREA . '/docs');
        $broken = [];

        foreach ($pages as $from => $html) {
            preg_match_all('/<a href="([^"]+)"/', $html, $matches);

            foreach ($matches[1] as $href) {
                $href = html_entity_decode($href);

                if (str_starts_with($href, '#')) {
                    $target   = $from;
                    $fragment = substr($href, 1);
                } elseif (str_starts_with($href, $base)) {
                    [$target, $fragment] = explode('#', ltrim(substr($href, strlen($base)), '/'), 2) + [1 => ''];
                } else {
                    continue;
                }

                if (! isset($pages[$target])) {
                    $broken[] = "{$from} → {$href} (no such page)";
                } elseif ($fragment !== '' && ! str_contains($pages[$target], 'id="' . $fragment . '"')) {
                    $broken[] = "{$from} → {$href} (no such anchor)";
                }
            }
        }

        $this->assertSame([], $broken);
    }

    /**
     * @return list<string>
     */
    private function navPaths(): array
    {
        preg_match_all('/^\s*-\s+(?:[^:\n]+:\s+)?([\w\/]+\.md)\s*$/m', (string) file_get_contents(dirname(BFPATH) . '/mkdocs.yml'), $matches);

        return $matches[1];
    }
}
