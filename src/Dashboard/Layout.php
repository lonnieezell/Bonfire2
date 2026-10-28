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
 * The widgets on a dashboard: sections of placed widgets, in order.
 */
final readonly class Layout
{
    /**
     * @param list<Section> $sections
     */
    public function __construct(public array $sections)
    {
    }

    /**
     * Every placed widget id, in order.
     *
     * @return list<string>
     */
    public function ids(): array
    {
        $ids = [];

        foreach ($this->sections as $section) {
            foreach ($section->widgets as $placement) {
                $ids[] = $placement->id;
            }
        }

        return $ids;
    }

    /**
     * The stored format, version 1.
     *
     * @return array{version: int, sections: list<array{title: string, widgets: list<array{id: string, size: string}>}>}
     */
    public function toArray(): array
    {
        return [
            'version'  => 1,
            'sections' => array_map(static fn (Section $section) => [
                'title'   => $section->title,
                'widgets' => array_map(static fn (Placement $placement) => [
                    'id'   => $placement->id,
                    'size' => $placement->size->value,
                ], $section->widgets),
            ], $this->sections),
        ];
    }
}
