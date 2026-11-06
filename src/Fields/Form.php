<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Fields;

/**
 * A form made of fields: what it shows for each, what a post holds once it's tidied, and whether it's valid.
 * Where the values come from and go to is up to the caller.
 */
final readonly class Form
{
    /**
     * @param list<Field>  $fields
     * @param list<string> $readOnly the handles of fields the form shows but the user can't change
     */
    public function __construct(private array $fields, private array $readOnly = [])
    {
    }

    /**
     * The fields the form has, each with its type.
     *
     * @return list<array{Field, FieldType}>
     */
    public function fields(): array
    {
        $fields = [];

        foreach ($this->fields as $field) {
            $fields[] = [$field, FieldTypes::get($field->type)];
        }

        return $fields;
    }

    public function isEditable(Field $field): bool
    {
        return ! in_array($field->handle, $this->readOnly, true);
    }

    /**
     * What the form shows for each field.
     *
     * @param array<string, string> $values by handle
     * @param array<string, string> $errors by handle
     *
     * @return list<array{field: Field, view: string, value: string, error: string|null, disabled: bool}>
     */
    public function rows(array $values, array $errors): array
    {
        $rows = [];

        foreach ($this->fields as $field) {
            $rows[] = [
                'field'    => $field,
                'view'     => FieldTypes::view($field->type),
                'value'    => $values[$field->handle] ?? '',
                'error'    => $errors[$field->handle] ?? null,
                'disabled' => ! $this->isEditable($field),
            ];
        }

        return $rows;
    }

    /**
     * Tidies the posted values of the fields the user can edit.
     *
     * @param array<array-key, mixed> $post
     *
     * @return array<string, string> by handle
     */
    public function prepare(array $post): array
    {
        $posted = is_array($post['fields'] ?? null) ? $post['fields'] : [];
        $values = [];

        foreach ($this->fields() as [$field, $type]) {
            if ($this->isEditable($field)) {
                $values[$field->handle] = $type->prepare($posted[$field->handle] ?? null, $field, ['fields' => $posted]);
            }
        }

        return $values;
    }

    /**
     * @param array<string, string> $values as prepare() returned them
     *
     * @return array<string, string> the first error of each field that has one, by handle
     */
    public function validate(array $values): array
    {
        $rules  = [];
        $labels = [];

        foreach ($this->fields() as [$field, $type]) {
            $fieldRules = $type->rules($field);

            if (isset($values[$field->handle]) && $fieldRules !== '' && $fieldRules !== []) {
                $rules["fields.{$field->handle}"] = ['label' => $field->label, 'rules' => $fieldRules];
                $labels[$field->handle]           = "fields.{$field->handle}";
            }
        }

        if ($rules === []) {
            return [];
        }

        $validation = single_service('validation')->setRules($rules);
        $validation->run(['fields' => $values]);

        $errors = [];

        foreach ($labels as $handle => $key) {
            if (($error = $validation->getError($key)) !== '') {
                $errors[$handle] = $error;
            }
        }

        return $errors;
    }
}
