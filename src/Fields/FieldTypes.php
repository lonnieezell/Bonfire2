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

use Bonfire\Fields\Types\DateField;
use Bonfire\Fields\Types\DatetimeField;
use Bonfire\Fields\Types\IntegerField;
use Bonfire\Fields\Types\MarkdownField;
use Bonfire\Fields\Types\SelectField;
use Bonfire\Fields\Types\TextareaField;
use Bonfire\Fields\Types\TextField;
use Bonfire\Fields\Types\ToggleField;

/**
 * The field types a form can use.
 */
final class FieldTypes
{
    private const TYPES = [
        'text'     => TextField::class,
        'textarea' => TextareaField::class,
        'markdown' => MarkdownField::class,
        'toggle'   => ToggleField::class,
        'date'     => DateField::class,
        'datetime' => DatetimeField::class,
        'select'   => SelectField::class,
        'integer'  => IntegerField::class,
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
     * The built-in types, the ones other Bonfire modules add, and the ones registered in Config\Fields, by handle.
     * A registered class that isn't a FieldType is left out, so a form that uses it is refused.
     *
     * @return array<string, class-string<FieldType>>
     */
    private static function all(): array
    {
        $types  = self::TYPES;
        $config = config('Fields');

        foreach ([...$config->moduleTypes, ...$config->types] as $handle => $class) {
            if (is_string($class) && is_a($class, FieldType::class, true)) {
                $types[$handle] = $class;
            }
        }

        return $types;
    }

    /**
     * The view that renders the type's input: the app's copy in app/Views/fields when it has one,
     * otherwise the type's own.
     */
    public static function view(string $type): string
    {
        $type    = self::get($type);
        $partial = $type->partial();

        return is_file(APPPATH . "Views/fields/{$partial}.php") ? "App\\Views\\fields\\{$partial}" : "{$type->viewNamespace()}\\{$partial}";
    }
}
