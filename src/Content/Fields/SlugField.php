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

use Bonfire\Content\Field;

/**
 * The entry's slug. It names the file, so it isn't in the front matter and can't change once the entry exists.
 * Left empty, it's made from the field named by `from`, which is `title` unless the blueprint says otherwise.
 */
final class SlugField extends FieldType
{
    public const MAX_LENGTH = 100;

    public function partial(): string
    {
        return 'slug';
    }

    /**
     * The slug is lowercase letters and numbers with dashes between, so it's safe in a filename and a URI.
     */
    public function prepare(mixed $input, Field $field, array $submitted): string
    {
        helper('url');

        $slug = trim(parent::prepare($input, $field, $submitted));

        if ($slug === '') {
            $from = $submitted['fields'][self::source($field)] ?? '';
            $slug = is_string($from) ? $from : '';
        }

        return mb_substr(url_title($slug, '-', true), 0, self::MAX_LENGTH);
    }

    public function rules(Field $field): array|string
    {
        return $this->withRules($field, 'required', 'max_length[' . self::MAX_LENGTH . ']');
    }

    public function storedInFrontMatter(): bool
    {
        return false;
    }

    /**
     * The handle of the field the slug is made from.
     */
    public static function source(Field $field): string
    {
        return is_string($field->options['from'] ?? null) ? $field->options['from'] : 'title';
    }
}
