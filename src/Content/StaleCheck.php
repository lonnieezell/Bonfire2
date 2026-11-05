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

use Bonfire\Content\Models\ContentEntryModel;

/**
 * Reindexes when a content file has changed since the last check, so that
 * a developer sees an edit without running content:reindex. Development only:
 * production picks up changes through content:reindex at deploy.
 */
final readonly class StaleCheck
{
    private const CACHE_KEY = 'content_signature';

    public function __construct(private string $contentRoot)
    {
    }

    public function run(): void
    {
        $collections = rtrim($this->contentRoot, '/') . '/collections';

        if (! is_dir($collections) || $this->signature($collections) === cache(self::CACHE_KEY)) {
            return;
        }

        $result = (new Indexer($this->contentRoot))->reindex();

        foreach ($result->errors as $path => $error) {
            log_message('error', "Content: {$path}: {$error}");
        }

        if ($result->failure !== null) {
            log_message('error', "Content: {$result->failure}");

            return;
        }

        cache()->save(self::CACHE_KEY, $this->signature($collections), 0);
    }

    /**
     * Takes in every file, because editing a file in place doesn't change its directory's mtime.
     * The size catches a second edit within the same second, and the row count an emptied index.
     * The trash counts too, because where a file is decides whether its entry is live.
     */
    private function signature(string $collections): string
    {
        helper('filesystem');

        $stamps = [(new ContentEntryModel())->withDeleted()->countAllResults()];

        foreach ([...get_filenames($collections, true, false, false), ...get_filenames(dirname($collections) . '/.trash', true, false, false)] as $path) {
            $stamps[] = $path . ':' . filemtime($path) . ':' . filesize($path);
        }

        return hash('sha256', implode("\n", $stamps));
    }
}
