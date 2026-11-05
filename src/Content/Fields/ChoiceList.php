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

/**
 * A list of strings as a form value, which is always a string: JSON, or the empty string for an empty list.
 * It's how a type that holds several values, such as a multiple select, keeps to the FieldType contract.
 */
final class ChoiceList
{
    /**
     * The strings in what was posted, which is a list for a group of checkboxes and a string for a single input.
     * Anything else is ignored.
     *
     * @return list<string> trimmed, without repeats or empty strings
     */
    public static function fromInput(mixed $input): array
    {
        $items = [];

        foreach (is_array($input) ? $input : [$input] as $item) {
            if (is_string($item) && trim($item) !== '') {
                $items[trim($item)] = true;
            }
        }

        return array_map(strval(...), array_keys($items));
    }

    /**
     * @param list<string> $items
     */
    public static function encode(array $items): string
    {
        return $items === [] ? '' : json_encode($items, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return list<string>
     */
    public static function decode(string $encoded): array
    {
        $items = json_decode($encoded, true);

        return is_array($items) && array_is_list($items) ? array_values(array_filter($items, is_string(...))) : [];
    }

    /**
     * The strings in what a file holds, which is a list or else a single value.
     *
     * @return list<string>
     */
    public static function fromFrontMatter(mixed $stored): array
    {
        return self::fromInput(array_map(static fn (mixed $item): ?string => is_scalar($item) ? (string) $item : null, (array) $stored));
    }
}
