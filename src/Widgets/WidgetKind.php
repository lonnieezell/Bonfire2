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
 * The kinds of widget a dashboard can show. The value is the prefix
 * of the "enabled" setting stored for every item of that kind.
 */
enum WidgetKind: string
{
    case Stats  = 'Stats';
    case Charts = 'Charts';
}
