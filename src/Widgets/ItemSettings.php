<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets;

/**
 * Owns the stored "enabled" flag of one widget item and the names it is
 * stored and posted under.
 *
 * The keys (Stats.Stats_<id>, Stats.Charts_<id>) are already in installs'
 * databases, so they must not change.
 */
final readonly class ItemSettings
{
    public function __construct(
        public WidgetKind $kind,
        public ?string $id,
    ) {
    }

    /**
     * The name of the checkbox on the widgets settings form.
     */
    public function fieldName(): string
    {
        return $this->kind->value . '_' . $this->id;
    }

    public function key(): string
    {
        return 'Stats.' . $this->fieldName();
    }

    public function enabled(): bool
    {
        return (bool) setting($this->key());
    }

    public function store(bool $enabled): void
    {
        setting($this->key(), $enabled ? 'on' : false);
    }

    public function forget(): void
    {
        setting()->forget($this->key());
    }
}
