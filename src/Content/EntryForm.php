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

use Bonfire\Content\Fields\EntryStorage;
use Bonfire\Content\Fields\SlugField;
use Bonfire\Fields\Field;
use Bonfire\Fields\FieldType;
use Bonfire\Fields\FieldTypes;
use Bonfire\Fields\Form;

/**
 * Moves an entry between its file and the blueprint's form: what the form shows, what a post holds once
 * it's tidied, whether it's valid, and the front matter and body to write for it. The form itself is a
 * Bonfire\Fields\Form, which renders through Bonfire\Fields\Views\form_fields.
 */
final readonly class EntryForm
{
    private Form $form;

    /**
     * @param bool $isNew an existing entry's slug is its file name, so the form can't change it
     */
    public function __construct(private Blueprint $blueprint, private bool $isNew)
    {
        $readOnly = [];

        foreach ($blueprint->fields as $field) {
            if (! $this->isEditable(FieldTypes::get($field->type))) {
                $readOnly[] = $field->handle;
            }
        }

        $this->form = new Form($blueprint->fields, $readOnly);
    }

    /**
     * The fields the form has, each with its type.
     *
     * @return list<array{Field, FieldType}>
     */
    public function fields(): array
    {
        return $this->form->fields();
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
        return $this->form->rows($values, $errors);
    }

    /**
     * Whether the user can type into the field. A slug is the name of an existing entry's file.
     */
    public function isEditable(FieldType $type): bool
    {
        return $this->isNew || EntryStorage::inFrontMatter($type);
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
                $field->handle === Blueprint::BODY   => $body,
                ! EntryStorage::inFrontMatter($type) => $slug,
                default                              => $data[EntryStorage::key($type, $field)] ?? null,
            };

            $values[$field->handle] = $type->fromStored($stored, $field);
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
        return $this->form->prepare($post);
    }

    /**
     * @param array<string, string> $values as prepare() returned them
     *
     * @return array<string, string> the first error of each field that has one, by handle, and then the errors inside a
     *                               field that holds several values, by the handle, a dot and the position the editor
     *                               sees: `sections.2.heading`
     */
    public function validate(array $values): array
    {
        return $this->form->validate($values);
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

            if (! EntryStorage::inFrontMatter($type)) {
                continue;
            }

            $value = $type->toStored($values[$field->handle], $field);
            $key   = EntryStorage::key($type, $field);

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
            if (! EntryStorage::inFrontMatter($type) && isset($values[$field->handle])) {
                return $values[$field->handle];
            }
        }

        helper('url');

        return mb_substr(url_title($values['title'] ?? '', '-', true), 0, SlugField::MAX_LENGTH);
    }
}
