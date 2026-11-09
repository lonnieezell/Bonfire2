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
use Bonfire\Fields\ChoiceList;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\SignedToken;

/**
 * A repeater: rows with the same fixed sub-fields, listed in `fields`. `max_items` and `min_items` bound how many rows
 * there are. The front matter holds a list of maps. The form value is that list as JSON, with every value as text like
 * any other form value, or the empty string for no rows.
 * Read the rows back with `field()`, which casts each value by its sub-field's type.
 */
final class ListField extends FieldType
{
    private const TOKEN_PURPOSE = 'content-list';

    public function partial(): string
    {
        return 'list';
    }

    public function viewNamespace(): string
    {
        return 'Bonfire\Content\Views\fields';
    }

    /**
     * The sub-fields have to be usable, and there has to be at least one.
     */
    public function check(Field $field): void
    {
        Blueprint::subFields($field);
    }

    /**
     * Takes the posted rows, in the order they were posted. A value is tidied by the type of its sub-field, and an item
     * that isn't a map is dropped.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        $subFields = Blueprint::subFields($field);
        $rows      = [];

        foreach (array_filter(is_array($input) ? $input : [], is_array(...)) as $item) {
            $rows[] = BlocksField::preparedValues($subFields, $item);
        }

        return ChoiceList::encode($rows);
    }

    /**
     * There can be no more rows than `max_items` and no fewer than `min_items`, and each value passes its sub-field's
     * rules. The message names the first problem and the row's position.
     */
    public function rules(Field $field): array|string
    {
        return $this->withRules($field, function (mixed $value, mixed $data, ?string &$error = null) use ($field): bool {
            $problems = $this->itemErrors(is_string($value) ? $value : '', $field);

            if ($problems === []) {
                return true;
            }

            $position = (int) strtok(array_key_first($problems), '.');
            $error    = $position === 0 ? reset($problems) : lang('Content.listError', [$position, reset($problems)]);

            return false;
        });
    }

    public function itemErrors(string $input, Field $field): array
    {
        $rows   = BlocksField::decode($input);
        $errors = [];
        $max    = BlocksField::bound($field, 'max_items');
        $min    = BlocksField::bound($field, 'min_items');

        if ($max !== null && count($rows) > $max) {
            $errors[''] = lang('Content.listTooMany', [$max]);
        } elseif ($min !== null && count($rows) < $min) {
            $errors[''] = lang('Content.listTooFew', [$min]);
        }

        $subFields = Blueprint::subFields($field);

        foreach ($rows as $index => $row) {
            $errors += BlocksField::fieldErrors($subFields, $row, $index + 1);
        }

        return $errors;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toStored(string $input, Field $field): array
    {
        $subFields = Blueprint::subFields($field);

        return array_map(static fn (array $row): array => BlocksField::storedValues($subFields, $row), BlocksField::decode($input));
    }

    /**
     * Only the keys the sub-fields list come into the form, so saving in the admin drops any other key written into a
     * row by hand.
     */
    public function fromStored(mixed $stored, Field $field): string
    {
        $subFields = Blueprint::subFields($field);
        $rows      = array_map(static fn (array $item): array => BlocksField::valuesFrom($subFields, $item), self::maps($stored));

        return ChoiceList::encode($rows);
    }

    /**
     * The rows as a template reads them: every sub-field of every row, each value cast by its type.
     *
     * @return list<array<string, mixed>>
     */
    public static function cast(mixed $stored, Field $field): array
    {
        $subFields = Blueprint::subFields($field);
        $keys      = array_map(static fn (Field $subField): string => EntryStorage::key(FieldTypes::get($subField->type), $subField), $subFields);
        $rows      = [];

        foreach (self::maps($stored) as $item) {
            $row = [];

            foreach ($subFields as $index => $subField) {
                $row[$subField->handle] = EntryStorage::cast($item[$keys[$index]] ?? null, $subField);
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * The sub-fields as the blueprint of a row, which is how the admin draws one.
     */
    public static function rowBlueprint(Field $field): Blueprint
    {
        return new Blueprint('', $field->handle, lang('Content.listRow'), Blueprint::subFields($field));
    }

    /**
     * What the Add row button sends so the server can draw a row: the field's definition, signed with the app's
     * encryption key so the endpoint draws nothing a blueprint didn't define. Empty when the app has no key.
     */
    public static function token(Field $field): string
    {
        return SignedToken::encode(self::TOKEN_PURPOSE, ['handle' => $field->handle, 'fields' => $field->options['fields'], 'max_items' => BlocksField::bound($field, 'max_items')]);
    }

    /**
     * The field a token was made for, or null for anything else: no token, one that was changed, or one signed with
     * another key.
     */
    public static function fromToken(mixed $token): ?Field
    {
        $data = SignedToken::decode(self::TOKEN_PURPOSE, $token);

        return $data === null ? null : new Field($data['handle'], 'list', '', '', null, ['fields' => $data['fields'], 'max_items' => $data['max_items']]);
    }

    /**
     * The maps in what the front matter holds.
     *
     * @return list<array<array-key, mixed>>
     */
    private static function maps(mixed $stored): array
    {
        return is_array($stored) && array_is_list($stored) ? array_values(array_filter($stored, is_array(...))) : [];
    }
}
