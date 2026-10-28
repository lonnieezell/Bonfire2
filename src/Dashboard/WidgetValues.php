<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard;

/**
 * The settings a widget renders with: each field's value as placed.
 */
final readonly class WidgetValues
{
    /**
     * @param array<string, bool|float|int|string> $values
     */
    private function __construct(private array $values)
    {
    }

    /**
     * Each field's value: the placement's own, else what it inherits.
     *
     * @param list<WidgetField>                    $fields
     * @param array<string, bool|float|int|string> $own    The placement's settings, as validated
     */
    public static function resolve(array $fields, array $own = []): self
    {
        $values = [];

        foreach ($fields as $field) {
            $values[$field->name] = $own[$field->name] ?? $field->inherited();
        }

        return new self($values);
    }

    /**
     * The value of a field, or null for one the widget does not declare.
     */
    public function get(string $name): bool|float|int|string|null
    {
        return $this->values[$name] ?? null;
    }

    /**
     * @return array<string, bool|float|int|string>
     */
    public function all(): array
    {
        return $this->values;
    }
}
