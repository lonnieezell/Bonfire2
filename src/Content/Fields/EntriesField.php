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

use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\Field;
use Bonfire\Content\Models\ContentEntryModel;

/**
 * Links to other entries, kept in the front matter as a list of their UUIDs. `collections` names the collections
 * that entries can be picked from, and is every collection when it's left out. `max_items` caps how many.
 * Read the entries back with `$entry->entries('handle')`. A link to an entry in the trash stays until the user unticks it.
 */
final class EntriesField extends FieldType
{
    public function partial(): string
    {
        return 'entries';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return ChoiceList::encode(array_values(array_unique(array_map(strtolower(...), ChoiceList::fromInput($input)))));
    }

    /**
     * Every entry chosen has to exist in an allowed collection, in the trash or not, and there can't be more than `max_items`.
     */
    public function rules(Field $field): array|string
    {
        $collections = self::collections($field);
        $max         = self::maxItems($field);

        return $this->withRules($field, 'permit_empty', static function (mixed $value, mixed $data, ?string &$error = null) use ($collections, $max): bool {
            $ids = ChoiceList::decode(is_string($value) ? $value : '');

            if ($ids === []) {
                return true;
            }

            if ($max !== null && count($ids) > $max) {
                $error = lang('Content.entriesTooMany', [$max]);

                return false;
            }

            $model = (new ContentEntryModel())->withDeleted()->select('id')->whereIn('id', $ids);

            if ($collections !== []) {
                $model->whereIn('collection', $collections);
            }

            if (count($model->findAll()) !== count($ids)) {
                $error = lang('Content.entriesInvalid');

                return false;
            }

            return true;
        });
    }

    public function toFrontMatter(string $input, Field $field): mixed
    {
        return ChoiceList::decode($input) ?: null;
    }

    public function fromFrontMatter(mixed $stored, Field $field): string
    {
        return ChoiceList::encode(array_map(strtolower(...), ChoiceList::fromFrontMatter($stored)));
    }

    /**
     * The collections entries can be picked from, empty for any.
     *
     * @return list<string>
     */
    public static function collections(Field $field): array
    {
        $named = $field->options['collections'] ?? [];

        // `collections: posts` names one collection, and must not widen to every collection.
        return array_values(array_filter(is_array($named) ? $named : [$named], static fn (mixed $handle): bool => is_string($handle) && $handle !== ''));
    }

    public static function maxItems(Field $field): ?int
    {
        $max = $field->options['max_items'] ?? null;

        return is_int($max) && $max > 0 ? $max : null;
    }

    /**
     * The entries the form offers: those of the allowed collections, drafts too, in each collection's order.
     * A collection that doesn't exist offers none.
     *
     * @return list<Entry>
     */
    public static function choices(Field $field): array
    {
        $handles = self::collections($field) ?: array_map(static fn (Collection $collection): string => $collection->handle, Collection::all(config('Content')->contentPath));
        $entries = [];

        foreach ($handles as $handle) {
            try {
                array_push($entries, ...content()->collection($handle)->get());
            } catch (ContentException) {
                // A collection that doesn't exist offers nothing.
            }
        }

        return $entries;
    }

    /**
     * The entries in the trash that the field links to, which the form shows but never offers.
     *
     * @param list<string> $ids the ids the field holds
     *
     * @return list<Entry>
     */
    public static function trashedLinks(Field $field, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $model       = (new ContentEntryModel())->onlyDeleted()->whereIn('id', $ids)->orderBy('id');
        $collections = self::collections($field);

        if ($collections !== []) {
            $model->whereIn('collection', $collections);
        }

        return array_map(Entry::fromRow(...), $model->findAll());
    }
}
