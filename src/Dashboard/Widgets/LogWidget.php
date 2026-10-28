<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Dashboard\Widgets;

use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\Tools\Widgets\LogActivity;
use Bonfire\Widgets\WidgetOptions;

/**
 * An item of the "logs" widget, such as Log activity, which renders itself.
 */
class LogWidget extends ItemWidget
{
    public function title(): string
    {
        return method_exists($this->item, 'title') ? (string) $this->item->title() : '';
    }

    public function icon(): string
    {
        return 'fas fa-file-lines';
    }

    public function description(): string
    {
        return lang('Bonfire.logCard');
    }

    public function group(): string
    {
        return $this->moduleOr('charts');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Full, Size::Half);
    }

    /**
     * The options of Settings → Widgets → Log activity, for Log activity.
     */
    public function fields(): array
    {
        if (! $this->item instanceof LogActivity) {
            return [];
        }

        $options = WidgetOptions::forAlias('logactivity');

        return [
            WidgetField::number('fileCount', lang('Tools.logActivityDays'), default: 7, min: 1, max: LogActivity::MAX_DAYS, key: $options->key('fileCount'), description: lang('Tools.logActivityDaysHint')),
            WidgetField::toggle('hideDebug', lang('Tools.logActivityHideDebug'), default: false, key: $options->key('hideDebug')),
        ];
    }

    public function render(WidgetValues $values): string
    {
        if ($this->item instanceof LogActivity) {
            return $this->item->withOptions($values->all())->render();
        }

        return method_exists($this->item, 'render') ? (string) $this->item->render() : '';
    }
}
