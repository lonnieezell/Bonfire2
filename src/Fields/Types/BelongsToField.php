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
use CodeIgniter\Model;

/**
 * One record of another model, picked from a select of the records' titles. `model` is the model's class and `title`
 * the column that names a record. What is stored is the record's ID, as a number when it is one, never the record.
 */
final class BelongsToField extends FieldType
{
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
        $model   = self::model($field);
        $key     = $model->primaryKey;
        $title   = self::titleColumn($field);
        $choices = [];

        foreach ($model->asArray()->select([$key, $title])->orderBy($title)->findAll() as $row) {
            $choices[(string) $row[$key]] = (string) $row[$title];
        }

        return $choices;
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
