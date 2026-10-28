<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Widgets;

use Bonfire\Dashboard\DashboardWidget;
use Bonfire\Dashboard\Size;
use Bonfire\Dashboard\SizeRange;
use Bonfire\Dashboard\WidgetField;
use Bonfire\Dashboard\WidgetValues;
use Bonfire\Tools\Libraries\LogStore;
use CodeIgniter\I18n\Time;
use IntlDatePatternGenerator;

/**
 * The "Recent errors" dashboard widget: the newest error entries across
 * the log files, each linking to its log file.
 */
class RecentErrorsWidget implements DashboardWidget
{
    public const ID = 'tools:recentErrors';

    /**
     * The log levels each level option shows.
     */
    private const LEVELS = [
        'errors'   => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
        'warnings' => ['WARNING', 'ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
    ];

    public function __construct(private readonly LogStore $store = new LogStore())
    {
    }

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Tools.recentErrors');
    }

    public function icon(): string
    {
        return 'fas fa-triangle-exclamation';
    }

    public function group(): string
    {
        return 'custom';
    }

    public function description(): string
    {
        return lang('Tools.recentErrorsCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Half, Size::Third);
    }

    public function permission(): ?string
    {
        return 'logs.view';
    }

    public function fields(): array
    {
        return [
            WidgetField::number('count', lang('Tools.recentErrorsCount'), default: 5, min: 1, max: 20),
            WidgetField::choice('level', lang('Tools.recentErrorsLevel'), ['errors' => lang('Tools.recentErrorsOnly'), 'warnings' => lang('Tools.recentErrorsAndWarnings')], 'errors'),
        ];
    }

    /**
     * The newest entries of the level option, newest first, reading only
     * as many log files as it takes.
     *
     * @return list<array{level: string, date: string, content: string, log: string}>
     */
    public function entries(int $count, string $level): array
    {
        $levels  = self::LEVELS[$level] ?? self::LEVELS['errors'];
        $entries = [];

        foreach ($this->store->names() as $name) {
            foreach (array_reverse($this->store->entries($name)) as $entry) {
                if (in_array($entry['level'], $levels, true)) {
                    $entries[] = ['level' => $entry['level'], 'date' => $entry['date'], 'content' => $entry['content'], 'log' => $name];

                    if (count($entries) === $count) {
                        return $entries;
                    }
                }
            }
        }

        return $entries;
    }

    public function render(WidgetValues $values): string
    {
        return view('Bonfire\Tools\Views\_recent_errors', [
            'title'   => $this->title(),
            'entries' => $this->entries((int) $values->get('count'), (string) $values->get('level')),
            'today'   => Time::today()->toDateString(),
            'pattern' => (new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('MMMd'),
        ]);
    }
}
