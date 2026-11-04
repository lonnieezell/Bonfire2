<?php

namespace Tests\Support\Content;

use Bonfire\Content\Field;
use Bonfire\Content\Fields\FieldType;

/**
 * A field type of an app's own: text that is kept in capitals. It borrows the bundled text input.
 */
final class ShoutField extends FieldType
{
    public function partial(): string
    {
        return 'text';
    }

    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        return strtoupper(parent::prepare($input, $field, $submitted));
    }

    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'max_length[10]');
    }
}
