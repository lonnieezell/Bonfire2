<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use DateTimeImmutable;

/**
 * A collection's entries as an RSS 2.0 feed.
 */
final class Feed
{
    public const CONTENT_TYPE = 'application/rss+xml';

    /**
     * @param list<Entry> $entries the entries to list, in order. One with no URI is left out.
     */
    public static function rss(Collection $collection, array $entries): string
    {
        $items = '';

        foreach ($entries as $entry) {
            if ($entry->uri === null) {
                continue;
            }

            $link = self::text(site_url(ltrim($entry->uri, '/')));
            $items .= "    <item>\n"
                . '      <title>' . self::text($entry->title) . "</title>\n"
                . "      <link>{$link}</link>\n"
                . "      <guid isPermaLink=\"true\">{$link}</guid>\n"
                . ($entry->publishedAt === null ? '' : '      <pubDate>' . (new DateTimeImmutable($entry->publishedAt))->format(DATE_RSS) . "</pubDate>\n")
                . "    </item>\n";
        }

        $title = self::text($collection->title);

        return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"
            . "<rss version=\"2.0\">\n"
            . "  <channel>\n"
            . "    <title>{$title}</title>\n"
            . '    <link>' . self::text(site_url()) . "</link>\n"
            . "    <description>{$title}</description>\n"
            . $items
            . "  </channel>\n"
            . "</rss>\n";
    }

    /**
     * Escapes a value for XML text, after dropping the characters XML 1.0 can't hold.
     */
    private static function text(string $value): string
    {
        $value = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', mb_scrub($value));

        return htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
