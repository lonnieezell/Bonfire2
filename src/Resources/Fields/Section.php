<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Resources\Fields;

use Bonfire\Fields\Section as CompiledSection;
use Bonfire\Resources\ResourceException;

/**
 * A group of a resource's fields, shown under a title in one to three columns, like a blueprint's `sections`.
 */
final class Section
{
    private ?string $instructions = null;
    private int $columns          = CompiledSection::DEFAULT_COLUMNS;

    /**
     * @param list<Field> $fields
     */
    private function __construct(private readonly string $title, private readonly array $fields)
    {
    }

    /**
     * @param list<Field> $fields
     */
    public static function make(string $title, array $fields): self
    {
        return new self($title, $fields);
    }

    public function instructions(string $instructions): self
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * @throws ResourceException when the count isn't from 1 to 3
     */
    public function columns(int $columns): self
    {
        if ($columns < CompiledSection::MIN_COLUMNS || $columns > CompiledSection::MAX_COLUMNS) {
            throw new ResourceException("Section `{$this->title}`: `columns` must be a whole number from " . CompiledSection::MIN_COLUMNS . ' to ' . CompiledSection::MAX_COLUMNS . '.');
        }

        $this->columns = $columns;

        return $this;
    }

    public function compile(): CompiledSection
    {
        return new CompiledSection($this->title, $this->instructions, $this->columns, array_map(static fn (Field $field) => $field->compile(), $this->fields));
    }
}
