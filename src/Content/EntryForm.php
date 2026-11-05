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

use Bonfire\Content\Fields\FieldType;
use Bonfire\Content\Fields\FieldTypes;
use Bonfire\Content\Fields\SlugField;

/**
 * Moves an entry between its file and the blueprint's form: what the form shows, what a post holds once
 * it's tidied, whether it's valid, and the front matter and body to write for it.
 */
final readonly class EntryForm
{
    /**
     * @param bool $isNew an existing entry's slug is its file name, so the form can't change it
     */
    public function __construct(private Blueprint $blueprint, private bool $isNew)
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

        foreach ($this->blueprint->fields as $field) {
            $fields[] = [$field, FieldTypes::get($field->type)];
        }

        return $fields;
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

        foreach ($this->fields() as [$field, $type]) {
            $rows[] = [
                'field'    => $field,
                'view'     => FieldTypes::view($field->type),
                'value'    => $values[$field->handle] ?? '',
                'error'    => $errors[$field->handle] ?? null,
                'disabled' => ! $this->isEditable($type),
            ];
        }

        return $rows;
    }

    /**
     * Whether the user can type into the field. A slug is the name of an existing entry's file.
     */
    public function isEditable(FieldType $type): bool
    {
        return $this->isNew || $type->storedInFrontMatter();
    }

    /**
     * What the form shows for an entry that exists.
     *
     * @param array<array-key, mixed> $data the entry's front matter
     *
     * @return array<string, string> by handle
     */
    public function valuesOf(array $data, string $body, string $slug): array
    {
        $values = [];

        foreach ($this->fields() as [$field, $type]) {
            $stored = match (true) {
                $field->handle === Blueprint::BODY => $body,
                ! $type->storedInFrontMatter()     => $slug,
                default                            => $data[$type->key($field)] ?? null,
            };

            $values[$field->handle] = $type->fromFrontMatter($stored, $field);
        }

        return $values;
    }

    /**
     * What the form shows for a new entry.
     *
     * @return array<string, string> by handle
     */
    public function emptyValues(): array
    {
        return $this->valuesOf([], '', '');
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
            if ($this->isEditable($type)) {
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

    /**
     * Puts the values into an entry's front matter and body. Whatever the blueprint doesn't know stays as it was.
     *
     * @param array<string, string>   $values   as prepare() returned them
     * @param array<array-key, mixed> $existing the entry's front matter now, empty for a new entry
     *
     * @return array{array<array-key, mixed>, string} the front matter and the body
     */
    public function apply(array $values, array $existing, string $existingBody): array
    {
        $frontMatter = $existing;
        $body        = $existingBody;

        foreach ($this->fields() as [$field, $type]) {
            if (! isset($values[$field->handle]) || ! $this->isEditable($type)) {
                continue;
            }

            if ($field->handle === Blueprint::BODY) {
                $body = $values[$field->handle];

                continue;
            }

            if (! $type->storedInFrontMatter()) {
                continue;
            }

            $value = $type->toFrontMatter($values[$field->handle], $field);
            $key   = $type->key($field);

            // A field left empty doesn't add a key to the file.
            if (array_key_exists($key, $existing) || ! in_array($value, [null, '', []], true)) {
                $frontMatter[$key] = $value;
            }
        }

        return [$frontMatter, $body];
    }

    /**
     * The slug of a new entry: the slug field's value, or else one made from the title.
     *
     * @param array<string, string> $values as prepare() returned them
     */
    public function slugOf(array $values): string
    {
        foreach ($this->fields() as [$field, $type]) {
            if (! $type->storedInFrontMatter() && isset($values[$field->handle])) {
                return $values[$field->handle];
            }
        }

        helper('url');

        return mb_substr(url_title($values['title'] ?? '', '-', true), 0, SlugField::MAX_LENGTH);
    }
}
