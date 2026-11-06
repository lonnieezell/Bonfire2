<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields\Types;

use Bonfire\Fields\Field;
use Bonfire\Fields\FieldException;
use Bonfire\Fields\FieldType;
use Bonfire\Fields\OptionsToken;
use CodeIgniter\Model;

/**
 * One record of another model, picked from a select of the records' titles. `model` is the model's class and `title`
 * the column that names a record. What is stored is the record's ID, as a number when it is one, never the record.
 *
 * Above `searchAbove` records (default 100) the select becomes a typeahead that asks the options endpoint for the
 * records that match what was typed. `permission` is what a user needs on top of `admin.access` to search them.
 */
final class BelongsToField extends FieldType
{
    /**
     * How many records a model may have before the select becomes a typeahead.
     */
    public const SEARCH_ABOVE = 100;

    /**
     * The most records one search gives back.
     */
    public const SEARCH_LIMIT = 20;

    public function partial(): string
    {
        return 'belongsTo';
    }

    public function check(Field $field): void
    {
        $class = self::modelClass($field);

        if ($class === '' || ! is_a($class, Model::class, true)) {
            throw new FieldException("Field `{$field->handle}` needs a `model`: the class of a model, such as App\\Models\\AuthorModel.");
        }

        $title = self::titleColumn($field);

        if ($title === '') {
            throw new FieldException("Field `{$field->handle}` needs a `title`: the column of {$class} that names a record.");
        }

        $model = self::model($field);

        if (! $model->db->fieldExists($title, $model->table)) {
            throw new FieldException("Field `{$field->handle}`: the table of {$class} has no `{$title}` column.");
        }

        $above = $field->options['searchAbove'] ?? self::SEARCH_ABOVE;

        if (! is_int($above) || $above < 0) {
            throw new FieldException("Field `{$field->handle}`: `searchAbove` is a number of records, such as 100.");
        }

        $permission = $field->options['permission'] ?? null;

        if ($permission !== null && (! is_string($permission) || $permission === '')) {
            throw new FieldException("Field `{$field->handle}`: `permission` is the name of a permission, such as users.list.");
        }
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return trim(parent::prepare($input, $field, $submitted));
    }

    /**
     * The ID has to belong to a record the model finds, so a soft-deleted record is refused too.
     */
    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'permit_empty', static function (mixed $value, mixed $data, ?string &$error = null) use ($field): bool {
            $model = self::model($field);

            if (! is_string($value) || $model->asArray()->where($model->primaryKey, $value)->first() === null) {
                $error = lang('Fields.belongsToInvalid');

                return false;
            }

            return true;
        });
    }

    /**
     * An ID such as `7` is stored as the number 7, and any other, such as a UUID, as text.
     */
    public function toStored(string $input, Field $field): int|string|null
    {
        if ($input === '') {
            return null;
        }

        $number = filter_var($input, FILTER_VALIDATE_INT);

        return $number !== false && (string) $number === $input ? $number : $input;
    }

    /**
     * The records the select offers, by ID, as the model lists them with the title column.
     *
     * @return array<string, string> title by ID
     */
    public static function choices(Field $field): array
    {
        return self::titles(self::model($field), self::titleColumn($field));
    }

    /**
     * Whether the model has more records than the select should list.
     */
    public static function searches(Field $field): bool
    {
        $above = $field->options['searchAbove'] ?? self::SEARCH_ABOVE;

        return self::model($field)->countAllResults() > (is_int($above) ? $above : self::SEARCH_ABOVE);
    }

    /**
     * The signed token that lets the options endpoint search this field's records and nothing else.
     */
    public static function token(Field $field): string
    {
        $permission = $field->options['permission'] ?? null;
        $title      = self::titleColumn($field);

        return OptionsToken::create(self::model($field)::class, $title, [$title], is_string($permission) ? $permission : null);
    }

    /**
     * The title of the record with this ID, or an empty string when there is none.
     */
    public static function titleOf(Field $field, string $id): string
    {
        $model = self::model($field);
        $row   = $id === '' ? null : $model->asArray()->where($model->primaryKey, $id)->first();

        return $row === null ? '' : (string) $row[self::titleColumn($field)];
    }

    /**
     * The records whose search columns hold the text, by ID, at most SEARCH_LIMIT of them in title order. The text
     * is matched as it is: a `%` or `_` in it is not a wildcard.
     *
     * @param class-string<Model> $class
     * @param list<string>        $search the columns to match the text against
     *
     * @return array<string, string> title by ID
     */
    public static function search(string $class, string $title, array $search, string $text): array
    {
        $model = new $class();

        if ($text !== '' && $search !== []) {
            // CodeIgniter adds the ESCAPE clause for the character but leaves the wildcards in the text alone.
            $char = $model->db->likeEscapeChar;
            $text = str_replace([$char, '%', '_'], [$char . $char, $char . '%', $char . '_'], $text);

            $model->groupStart();

            foreach ($search as $column) {
                $model->orLike($column, $text, 'both', true);
            }

            $model->groupEnd();
        }

        return self::titles($model, $title, self::SEARCH_LIMIT);
    }

    /**
     * The model's records, whatever conditions it has been given, in title order.
     *
     * @param int $limit 0 for all of them
     *
     * @return array<string, string> title by ID
     */
    private static function titles(Model $model, string $title, int $limit = 0): array
    {
        $key    = $model->primaryKey;
        $titles = [];

        foreach ($model->asArray()->select([$key, $title])->orderBy($title)->findAll($limit) as $row) {
            $titles[(string) $row[$key]] = (string) $row[$title];
        }

        return $titles;
    }

    private static function model(Field $field): Model
    {
        $class = self::modelClass($field);

        return new $class();
    }

    private static function modelClass(Field $field): string
    {
        $class = $field->options['model'] ?? null;

        return is_string($class) ? ltrim($class, '\\') : '';
    }

    private static function titleColumn(Field $field): string
    {
        $title = $field->options['title'] ?? null;

        return is_string($title) ? $title : '';
    }
}
