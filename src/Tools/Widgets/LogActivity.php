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

use Bonfire\Tools\Libraries\LogStore;
use Bonfire\Widgets\Interfaces\Item;
use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;
use Bonfire\Widgets\WidgetOptions;
use CodeIgniter\I18n\Time;
use IntlDatePatternGenerator;

/**
 * The "Log activity" dashboard widget: the entries of the last days'
 * log files, per level, as a stacked bar per day.
 */
class LogActivity implements Item
{
    /**
     * The levels shown, and the log levels each one counts.
     */
    private const LEVELS = [
        'error'   => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
        'warning' => ['WARNING'],
        'info'    => ['INFO', 'NOTICE'],
        'debug'   => ['DEBUG'],
    ];

    private const MAX_DAYS = 31;

    private ?string $title = null;

    public function __construct(private readonly LogStore $store = new LogStore())
    {
    }

    public function setTitle(?string $title): Item
    {
        $this->title = $title;

        return $this;
    }

    public function title(): string
    {
        return $this->title ?? lang('Tools.logActivity');
    }

    /**
     * Who may see the widget on their dashboard.
     */
    public function permission(): string
    {
        return 'logs.view';
    }

    public function settings(): ItemSettings
    {
        return new ItemSettings(WidgetKind::Logs, 'logActivity');
    }

    /**
     * The levels shown, most severe first.
     *
     * @return list<string>
     */
    public function levels(): array
    {
        $levels = array_keys(self::LEVELS);

        return $this->options()->get('hideDebug') ? array_values(array_diff($levels, ['debug'])) : $levels;
    }

    /**
     * The last days up to today, oldest first, with the entry count per shown
     * level of their log file. Days without a log file have no name or counts.
     *
     * @return list<array{name: string|null, label: string, counts: array<string, int>}>
     */
    public function days(): array
    {
        $count   = max(1, min(self::MAX_DAYS, (int) $this->options()->get('fileCount')));
        $pattern = (new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('Md');
        $days    = [];

        for ($ago = $count - 1; $ago >= 0; $ago--) {
            $day  = Time::now()->subDays($ago);
            $name = 'log-' . $day->toDateString();
            $has  = $this->store->has($name);

            $days[] = [
                'name'   => $has ? $name : null,
                'label'  => $day->toLocalizedString($pattern),
                'counts' => $has ? $this->counts($this->store->levelCounts($name)) : [],
            ];
        }

        return $days;
    }

    /**
     * The widget's card, or nothing for users who cannot view the logs.
     */
    public function render(): string
    {
        if (! auth()->user()?->can('logs.view')) {
            return '';
        }

        $days = $this->days();

        return view('Bonfire\Tools\Views\_log_activity', [
            'title'  => $this->title(),
            'levels' => $this->levels(),
            'days'   => $days,
            'errors' => array_sum(array_column(array_column($days, 'counts'), 'error')),
            'most'   => max([1, ...array_map(static fn (array $day) => array_sum($day['counts']), $days)]),
        ]);
    }

    /**
     * @param array<string, int> $levelCounts Entries per log level, e.g. ['ERROR' => 2]
     *
     * @return array<string, int>
     */
    private function counts(array $levelCounts): array
    {
        $counts = [];

        foreach ($this->levels() as $level) {
            $counts[$level] = array_sum(array_intersect_key($levelCounts, array_flip(self::LEVELS[$level])));
        }

        return $counts;
    }

    private function options(): WidgetOptions
    {
        return WidgetOptions::forAlias('logactivity');
    }
}
