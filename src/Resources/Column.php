<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources;

use Closure;

/**
 * A column of a resource's list that isn't one of its fields as they are stored. The handle can be:
 *
 * - a column of the model's table, such as `sku`;
 * - a relation's column, such as `category.name`. It reads `name` from the record's `category`, or else the record's
 *   `category_name`, which a join in the resource's `query()` selects as `categories.name AS category_name`, so the
 *   list needs no query for each row;
 * - any name, with `using()` to compute the value from the record.
 */
final class Column
{
    public readonly string $label;
    private ?Closure $compute = null;

    public function __construct(public readonly string $handle, ?string $label = null)
    {
        $this->label = $label ?? ucfirst(str_replace(['.', '_'], ' ', $handle));
    }

    public static function make(string $handle, ?string $label = null): self
    {
        return new self($handle, $label);
    }

    /**
     * Computes what the cell shows from the record, an entity or an array. Such a column can't be sorted.
     *
     * @param Closure(mixed): (float|int|string|null) $compute
     */
    public function using(Closure $compute): self
    {
        $this->compute = $compute;

        return $this;
    }

    /**
     * The text the cell shows for a record.
     */
    public function valueOf(mixed $record): string
    {
        $value = $this->compute instanceof Closure ? ($this->compute)($record) : $this->read($record);

        return is_scalar($value) ? (string) $value : '';
    }

    private function read(mixed $record): mixed
    {
        $value = $record;

        foreach (explode('.', $this->handle) as $key) {
            $value = self::get($value, $key);
        }

        // A relation that wasn't loaded as an object or array may be a column the query joined.
        return $value ?? self::get($record, str_replace('.', '_', $this->handle));
    }

    private static function get(mixed $from, string $key): mixed
    {
        return match (true) {
            is_array($from)  => $from[$key] ?? null,
            is_object($from) => $from->{$key} ?? null,
            default          => null,
        };
    }
}
