<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Fields;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\Field;
use Bonfire\Content\Taxonomy;

/**
 * The terms of a taxonomy an entry has, chosen from the terms that exist. The front matter holds their slugs as a list
 * under the taxonomy's handle, such as `tags: [php, laravel]`. The form value is the slugs separated by commas.
 * The collection has to list the taxonomy in `taxonomies` for the terms to be indexed.
 */
final class TermsField extends FieldType
{
    public function partial(): string
    {
        return 'terms';
    }

    public function check(Field $field): void
    {
        $taxonomy = self::taxonomy($field);

        if ($taxonomy === '') {
            throw new ContentException("Field `{$field->handle}` needs a `taxonomy`: the handle of the taxonomy its terms come from.");
        }

        // The taxonomy's handle is a front matter key, so it can't be one the entry already uses.
        if (in_array($taxonomy, [...Blueprint::RESERVED, 'title', Blueprint::BODY], true)) {
            throw new ContentException("Field `{$field->handle}`: its terms are saved under `{$taxonomy}`, which the entry uses for something else. Name the taxonomy differently.");
        }
    }

    /**
     * The terms are stored under the taxonomy's handle, which is where the index looks for them.
     */
    public function key(Field $field): string
    {
        return self::taxonomy($field);
    }

    /**
     * Takes the checked boxes, or a list of slugs separated by commas. Anything that isn't a slug is dropped.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        $slugs = [];

        foreach (is_array($input) ? $input : explode(',', is_string($input) ? $input : '') as $slug) {
            $slug = is_string($slug) ? trim($slug) : '';

            if (preg_match(Taxonomy::SLUG, $slug) === 1) {
                $slugs[$slug] = $slug;
            }
        }

        return implode(',', $slugs);
    }

    /**
     * @return list<string>
     */
    public function toFrontMatter(string $input, Field $field): array
    {
        return $input === '' ? [] : explode(',', $input);
    }

    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        return implode(',', array_filter((array) $stored, is_string(...)));
    }

    /**
     * The handle of the taxonomy the field takes its terms from.
     */
    public static function taxonomy(Field $field): string
    {
        $taxonomy = $field->options['taxonomy'] ?? null;

        return is_string($taxonomy) && preg_match('/^[A-Za-z0-9_-]{1,100}$/', $taxonomy) === 1 ? $taxonomy : '';
    }
}
