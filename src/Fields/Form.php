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
     * @param list<Field>   $fields
     * @param list<string>  $readOnly the handles of fields the form shows but the user can't change
     * @param list<Section> $sections the same fields, grouped for showing them. Without sections, the form is one group
     */
    public function __construct(private array $fields, private array $readOnly = [], private array $sections = [])
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
     * @return list<array{field: Field, view: string, value: string, error: string|null, errors: array<string, string>, disabled: bool, full: bool}>
     */
    public function rows(array $values, array $errors): array
    {
        return array_map(fn (Field $field): array => $this->row($field, $values, $errors), $this->fields);
    }

    /**
     * What the form shows, group by group: each section's title, instructions and number of columns, with the
     * rows of its fields. A form without sections is one group with no title and one column.
     *
     * @param array<string, string> $values by handle
     * @param array<string, string> $errors by handle
     *
     * @return list<array{title: string|null, instructions: string|null, columns: int, rows: list<array{field: Field, view: string, value: string, error: string|null, errors: array<string, string>, disabled: bool, full: bool}>}>
     */
    public function sections(array $values, array $errors): array
    {
        $sections = $this->sections === [] ? [new Section(null, null, Section::DEFAULT_COLUMNS, $this->fields)] : $this->sections;
        $shown    = [];

        foreach ($sections as $section) {
            $shown[] = [
                'title'        => $section->title,
                'instructions' => $section->instructions,
                'columns'      => $section->columns,
                'rows'         => array_map(fn (Field $field): array => $this->row($field, $values, $errors), $section->fields),
            ];
        }

        return $shown;
    }

    /**
     * @param array<string, string> $values
     * @param array<string, string> $errors
     *
     * @return array{field: Field, view: string, value: string, error: string|null, errors: array<string, string>, disabled: bool, full: bool}
     */
    private function row(Field $field, array $values, array $errors): array
    {
        return [
            'field'    => $field,
            'view'     => FieldTypes::view($field->type),
            'value'    => $values[$field->handle] ?? '',
            'error'    => $errors[$field->handle] ?? null,
            'errors'   => $this->innerErrors($field, $errors),
            'disabled' => ! $this->isEditable($field),
            'full'     => $field->full || FieldTypes::get($field->type)->isFullWidth(),
        ];
    }

    /**
     * The errors inside a field that holds several values, by the position and handle: `2.heading`.
     *
     * @param array<string, string> $errors
     *
     * @return array<string, string>
     */
    private function innerErrors(Field $field, array $errors): array
    {
        $prefix = $field->handle . '.';
        $inside = [];

        foreach ($errors as $key => $message) {
            if (str_starts_with((string) $key, $prefix)) {
                $inside[substr((string) $key, strlen($prefix))] = $message;
            }
        }

        return $inside;
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
        $checks = [];

        foreach ($this->fields() as [$field, $type]) {
            $fieldRules = $type->rules($field);

            if (isset($values[$field->handle]) && $fieldRules !== '' && $fieldRules !== []) {
                $rules["fields.{$field->handle}"] = ['label' => $field->label, 'rules' => $fieldRules];
                $labels[$field->handle]           = "fields.{$field->handle}";
                $checks[$field->handle]           = [$field, $type];
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

                [$field, $type] = $checks[$handle];

                foreach ($type->itemErrors($values[$handle], $field) as $position => $message) {
                    if ($position !== '') {
                        $errors["{$handle}.{$position}"] = $message;
                    }
                }
            }
        }

        return $errors;
    }
}
