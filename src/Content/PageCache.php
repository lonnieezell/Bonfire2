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

/**
 * The cached pages of collections that set `cache`, kept in the app's cache. Every page is stored under the
 * current generation, and clear() makes a new one, which leaves all the old pages unreachable
 * until they expire. That works with any cache handler, since not all can delete by pattern.
 *
 * A request takes the generation when it starts, so a page that was rendered while a save was clearing the cache
 * is stored under the old generation and never served.
 */
final readonly class PageCache
{
    private const GENERATION_KEY = 'content_page_generation';

    private string $generation;

    public function __construct()
    {
        $generation = cache(self::GENERATION_KEY);

        // Without a stored generation, say after the cache was emptied, pages from an earlier one must stay unreachable.
        if (! is_string($generation)) {
            $generation = self::renew();
        }

        $this->generation = $generation;
    }

    /**
     * @return array{body: string, contentType: string|null}|null the page, if it's cached and hasn't expired
     */
    public function get(string $path): ?array
    {
        $page = cache($this->key($path));

        return is_array($page) && is_string($page['body'] ?? null) ? ['body' => $page['body'], 'contentType' => is_string($page['contentType'] ?? null) ? $page['contentType'] : null] : null;
    }

    /**
     * @param int $ttl seconds until the page expires
     */
    public function put(string $path, string $body, ?string $contentType, int $ttl): void
    {
        cache()->save($this->key($path), ['body' => $body, 'contentType' => $contentType], $ttl);
    }

    /**
     * Forgets every cached page. Anything that changes what a page shows calls it.
     */
    public static function clear(): void
    {
        self::renew();
    }

    private function key(string $path): string
    {
        return "content_page_{$this->generation}_" . hash('sha256', $path);
    }

    private static function renew(): string
    {
        $generation = bin2hex(random_bytes(8));

        cache()->save(self::GENERATION_KEY, $generation, 0);

        return $generation;
    }
}
