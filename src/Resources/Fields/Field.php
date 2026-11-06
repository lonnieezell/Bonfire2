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

use Bonfire\Fields\Field as CompiledField;
use Bonfire\Fields\FieldTypes;
use Bonfire\Resources\ResourceException;

/**
 * Writes a field of a resource in PHP. The methods are the keys of a blueprint field: `label`, `validate`,
 * `instructions` and `width`, and the keys of the type, such as `options` or `places`. Each type has a subclass
 * (`Text::make('name')`), and this class takes any type by name: `Field::make('slug', type: 'text')`.
 * `compile()` turns it into the `Bonfire\Fields\Field` that forms and the list use.
 */
class Field
{
    /**
     * The type of a subclass, as `type:` in a blueprint.
     */
    protected const TYPE = 'text';

    private ?string $label         = null;
    private array|string $validate = '';
    private ?string $instructions  = null;
    private bool $full             = false;

    /**
     * @var array<string, mixed>
     */
    private array $options = [];

    final protected function __construct(private readonly string $handle, private readonly string $type)
    {
    }

    /**
     * @param string|null $type a type name such as `decimal`, for this class to take any type; a subclass has its own
     */
    public static function make(string $handle, ?string $type = null): static
    {
        return new static($handle, $type ?? static::TYPE);
    }

    public function label(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * CodeIgniter validation rules, as a `a|b` string or a list. `{id}` is the ID of the record being edited.
     *
     * @param list<string>|string $rules
     */
    public function validate(array|string $rules): static
    {
        $this->validate = $rules;

        return $this;
    }

    public function instructions(string $instructions): static
    {
        $this->instructions = $instructions;

        return $this;
    }

    /**
     * Makes the input span a whole row of its section. `full` is the only width there is.
     */
    public function width(string $width): static
    {
        if ($width !== 'full') {
            throw new ResourceException("Field `{$this->handle}`: `width` can only be `full`.");
        }

        $this->full = true;

        return $this;
    }

    /**
     * Sets keys the type reads, for a type that has no method for one.
     *
     * @param array<string, mixed> $options
     */
    public function with(array $options): static
    {
        $this->options = [...$this->options, ...$options];

        return $this;
    }

    /**
     * @throws ResourceException when the type doesn't exist
     */
    public function compile(): CompiledField
    {
        if (! FieldTypes::has($this->type)) {
            throw new ResourceException("Field `{$this->handle}` has the unknown type `{$this->type}`. Types: " . implode(', ', FieldTypes::names()) . '.');
        }

        return new CompiledField(
            $this->handle,
            $this->type,
            $this->label ?? ucfirst(str_replace('_', ' ', $this->handle)),
            $this->validate,
            $this->instructions,
            $this->options,
            $this->full,
        );
    }
}
