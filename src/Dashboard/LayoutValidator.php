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
     * @throws InvalidArgumentException
     */
    public function validate(mixed $payload): Layout
    {
        if (! is_array($payload) || ($payload['version'] ?? null) !== 1 || ! is_array($payload['sections'] ?? null)) {
            throw new InvalidArgumentException('A layout needs version 1 and a list of sections.');
        }

        if (count($payload['sections']) > self::MAX_SECTIONS) {
            throw new InvalidArgumentException('A layout holds at most ' . self::MAX_SECTIONS . ' sections.');
        }

        $available = $this->registry->available();
        $placed    = [];
        $sections  = [];

        foreach ($payload['sections'] as $section) {
            if (! is_array($section) || ! is_array($section['widgets'] ?? null) || ! is_string($section['title'] ?? '')) {
                throw new InvalidArgumentException('Each section needs a list of widgets, and a title if any.');
            }

            $placements = [];

            foreach ($section['widgets'] as $widget) {
                if (! is_array($widget) || ! is_string($widget['id'] ?? null)) {
                    throw new InvalidArgumentException('Each widget needs an id.');
                }

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
