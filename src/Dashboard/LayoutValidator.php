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
 * A wrong shape or version is rejected. Unknown, unavailable and
 * repeated widgets are dropped, and an unknown size becomes the
 * widget's default.
 */
class LayoutValidator
{
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

        $available = $this->registry->available();
        $placed    = [];
        $sections  = [];

        foreach ($payload['sections'] as $section) {
            if (! is_array($section) || ! is_array($section['widgets'] ?? null)) {
                throw new InvalidArgumentException('Each section needs a list of widgets.');
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
                $placements[] = new Placement($id, Size::tryFrom((string) ($widget['size'] ?? '')) ?? $available[$id]->sizes()->default);
            }

            $sections[] = new Section('', $placements);
        }

        return new Layout($sections);
    }
}
