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

use Bonfire\Content\Models\ContentTermModel;
use Closure;
use CodeIgniter\Database\BaseBuilder;

/**
 * The content_entry_terms pivot, which says which terms each entry has. An entry holds
 * its terms as slugs in its front matter, and the pivot is only the index of them.
 */
final class TermIndex
{
    /**
     * How many ids go in one query, to stay under the database's limit on parameters.
     */
    public const CHUNK = 500;

    /**
     * A subquery for the ids of the entries that have a term, to use in whereIn().
     *
     * @return Closure(BaseBuilder): BaseBuilder
     */
    public static function entryIdsOf(string $termId): Closure
    {
        return static fn (BaseBuilder $builder): BaseBuilder => $builder->select('entry_id')->from('content_entry_terms')->where('term_id', $termId);
    }

    /**
     * The ids of the terms an entry has, for the taxonomies its collection uses. A slug no term has is left out.
     *
     * @param array<string, mixed>                      $record the entry's index columns, with its `data`
     * @param array<string, array<string, string>>|null $terms  term ids by slug by taxonomy, if the caller has them
     *                                                          all already. Otherwise they're looked up.
     *
     * @return list<string>
     */
    public static function termIdsOf(Collection $collection, array $record, ?array $terms = null): array
    {
        if ($collection->taxonomies === []) {
            return [];
        }

        $data = json_decode((string) $record['data'], true);
        $ids  = [];

        foreach ($collection->termSlugsIn(is_array($data) ? $data : []) as $taxonomy => $slugs) {
            $known = $terms[$taxonomy] ?? ($terms === null ? self::lookUp($taxonomy, $slugs) : []);

            foreach ($slugs as $slug) {
                if (isset($known[$slug])) {
                    $ids[] = $known[$slug];
                }
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * Makes the pivot rows of these entries the ones given, and leaves other entries' rows alone.
     *
     * @param array<string, list<string>> $desired the ids of the terms each entry has, by entry id
     */
    public static function replace(array $desired): void
    {
        $inserts = [];

        foreach (array_chunk(array_keys($desired), self::CHUNK) as $entryIds) {
            $current = [];

            foreach (db_connect()->table('content_entry_terms')->whereIn('entry_id', $entryIds)->get()->getResultArray() as $row) {
                $current[$row['entry_id']][] = $row['term_id'];
            }

            foreach ($entryIds as $entryId) {
                $have = $current[$entryId] ?? [];

                if (($stale = array_diff($have, $desired[$entryId])) !== []) {
                    db_connect()->table('content_entry_terms')->where('entry_id', $entryId)->whereIn('term_id', array_values($stale))->delete();
                }

                foreach (array_diff($desired[$entryId], $have) as $termId) {
                    $inserts[] = ['entry_id' => $entryId, 'term_id' => $termId];
                }
            }
        }

        if ($inserts !== []) {
            db_connect()->table('content_entry_terms')->insertBatch($inserts);
        }
    }

    /**
     * Drops the pivot rows of entries that are no longer indexed.
     *
     * @param list<string> $entryIds
     */
    public static function forget(array $entryIds): void
    {
        foreach (array_chunk($entryIds, self::CHUNK) as $chunk) {
            db_connect()->table('content_entry_terms')->whereIn('entry_id', $chunk)->delete();
        }
    }

    /**
     * @param list<string> $slugs
     *
     * @return array<string, string> term ids by slug
     */
    private static function lookUp(string $taxonomy, array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        $rows = (new ContentTermModel())->select('id, slug')->where('taxonomy', $taxonomy)->whereIn('slug', $slugs)->findAll();

        // A case-insensitive collation also matches other spellings of the slug.
        return array_column(array_filter($rows, static fn (array $row): bool => in_array($row['slug'], $slugs, true)), 'id', 'slug');
    }
}
