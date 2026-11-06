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
        'datetime' => DatetimeField::class,
        'select'   => SelectField::class,
        'integer'  => IntegerField::class,
        'entries'  => EntriesField::class,
        'terms'    => TermsField::class,
        'blocks'   => BlocksField::class,
    ];

    public static function has(string $type): bool
    {
        return isset(self::all()[$type]);
    }

    /**
     * @return list<string>
     */
    public static function names(): array
    {
        return array_map(strval(...), array_keys(self::all()));
    }

    public static function get(string $type): FieldType
    {
        $class = self::all()[$type];

        return new $class();
    }

    /**
     * The built-in types and the ones registered in Config\Content, by handle. A registered class that
     * isn't a FieldType is left out, so a blueprint that uses it is refused.
     *
     * @return array<string, class-string<FieldType>>
     */
    private static function all(): array
    {
        $types = self::TYPES;

        foreach (config('Content')->fieldTypes as $handle => $class) {
            if (is_string($class) && is_a($class, FieldType::class, true)) {
                $types[$handle] = $class;
            }
        }

        return $types;
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
