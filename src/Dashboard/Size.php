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
 * The width presets of a dashboard widget. The value is what layouts store.
 */
enum Size: string
{
    case Quarter   = 'quarter';
    case Third     = 'third';
    case Half      = 'half';
    case TwoThirds = 'two-thirds';
    case Full      = 'full';

    /**
     * The columns spanned on the dashboard's 12-column grid.
     */
    public function columns(): int
    {
        return match ($this) {
            self::Quarter   => 3,
            self::Third     => 4,
            self::Half      => 6,
            self::TwoThirds => 8,
            self::Full      => 12,
        };
    }

    public static function tryFromColumns(int $columns): ?self
    {
        foreach (self::cases() as $size) {
            if ($size->columns() === $columns) {
                return $size;
            }
        }

        return null;
    }
}
