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

use InvalidArgumentException;

/**
 * Turns a posted or stored layout into a Layout of available widgets.
 *
 * A wrong shape or version, or too many sections, is rejected. Unknown,
 * unavailable and repeated widgets are dropped, then empty sections; an
 * unknown or out-of-range size becomes the widget's default, and titles
 * are trimmed.
 */
class LayoutValidator
{
    public const MAX_SECTIONS = 20;
    public const MAX_TITLE    = 100;

    public function __construct(private readonly WidgetRegistry $registry)
    {
    }

    /**
     * Whether the layout has the right shape and version, and at most
     * MAX_SECTIONS sections. Needs no registry, so it can be asked before
     * every widget is registered.
     */
    public static function wellFormed(mixed $payload): bool
    {
        if (! is_array($payload) || ($payload['version'] ?? null) !== 1 || ! is_array($payload['sections'] ?? null)) {
            return false;
        }

        if (count($payload['sections']) > self::MAX_SECTIONS) {
            return false;
        }

        foreach ($payload['sections'] as $section) {
            if (! is_array($section) || ! is_array($section['widgets'] ?? null) || ! is_string($section['title'] ?? '')) {
                return false;
            }

            foreach ($section['widgets'] as $widget) {
                if (! is_array($widget) || ! is_string($widget['id'] ?? null)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function validate(mixed $payload): Layout
    {
        if (! self::wellFormed($payload)) {
            throw new InvalidArgumentException('A layout needs version 1 and at most ' . self::MAX_SECTIONS . ' sections, each a list of widgets with ids, and a title if any.');
        }

        $available = $this->registry->available();
        $placed    = [];
        $sections  = [];

        foreach ($payload['sections'] as $section) {
            $placements = [];

            foreach ($section['widgets'] as $widget) {
                $id = $widget['id'];
                if (! isset($available[$id]) || isset($placed[$id])) {
                    continue;
                }

                $placed[$id]  = true;
                $placements[] = new Placement($id, $this->size($available[$id]->sizes(), $widget['size'] ?? null));
            }

            if ($placements !== []) {
                $sections[] = new Section(mb_substr(trim($section['title'] ?? ''), 0, self::MAX_TITLE), $placements);
            }
        }

        return new Layout($sections);
    }

    private function size(SizeRange $range, mixed $size): Size
    {
        $size = Size::tryFrom(is_string($size) ? $size : '');

        return in_array($size, $range->presets(), true) ? $size : $range->default;
    }
}
