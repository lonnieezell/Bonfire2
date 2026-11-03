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
 * The field types a blueprint can use.
 */
final class FieldTypes
{
    private const TYPES = [
        'text'     => TextField::class,
        'textarea' => TextareaField::class,
        'markdown' => MarkdownField::class,
        'slug'     => SlugField::class,
        'toggle'   => ToggleField::class,
        'date'     => DateField::class,
    ];

    public static function has(string $type): bool
    {
        return isset(self::TYPES[$type]);
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_keys(self::TYPES);
    }

    public static function get(string $type): FieldType
    {
        $class = self::TYPES[$type];

        return new $class();
    }

    /**
     * The view that renders the type's input: the app's copy in app/Views/content/fields when it has one,
     * otherwise Bonfire's.
     */
    public static function view(string $type): string
    {
        $partial = self::get($type)->partial();

        return is_file(APPPATH . "Views/content/fields/{$partial}.php") ? "App\\Views\\content\\fields\\{$partial}" : "Bonfire\\Content\\Views\\fields\\{$partial}";
    }
}
