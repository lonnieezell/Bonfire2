<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions;

/**
 * What an action asks before it runs. `{count}` in the title becomes the number of records.
 */
class Confirm
{
    private string $body        = '';
    private string $button      = '';
    private bool $typeToConfirm = false;

    private function __construct(private readonly string $title)
    {
    }

    public static function make(string $title): static
    {
        return new static($title);
    }

    public function body(string $body): static
    {
        $this->body = $body;

        return $this;
    }

    public function button(string $label): static
    {
        $this->button = $label;

        return $this;
    }

    /**
     * Makes the admin type the record count before the action runs.
     */
    public function typeToConfirm(): static
    {
        $this->typeToConfirm = true;

        return $this;
    }

    /**
     * The title with `{count}` filled in, or left in place for the browser to fill when null.
     */
    public function title(?int $count = null): string
    {
        return $count === null ? $this->title : str_replace('{count}', (string) $count, $this->title);
    }

    public function text(): string
    {
        return $this->body;
    }

    public function buttonLabel(): string
    {
        return $this->button;
    }

    public function asksToType(): bool
    {
        return $this->typeToConfirm;
    }
}
