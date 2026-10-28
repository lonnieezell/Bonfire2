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
 * The "Log activity" dashboard widget: the entries of the last days'
 * log files, per level, as a stacked bar per day.
 */
class LogActivity implements DashboardWidget
{
    /**
     * Stored in users' saved layouts since it was a "logs" widget item.
     */
    public const ID = 'logs:logActivity';

    /**
     * The levels shown, and the log levels each one counts.
     */
    private const LEVELS = [
        'error'   => ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY'],
        'warning' => ['WARNING'],
        'info'    => ['INFO', 'NOTICE'],
        'debug'   => ['DEBUG'],
    ];

    public const MAX_DAYS = 31;

    public function __construct(private readonly LogStore $store = new LogStore())
    {
    }

    public function id(): string
    {
        return self::ID;
    }

    public function title(): string
    {
        return lang('Tools.logActivity');
    }

    public function icon(): string
    {
        return 'fas fa-file-lines';
    }

    public function group(): string
    {
        return lang('Bonfire.toolsModTitle');
    }

    public function description(): string
    {
        return lang('Bonfire.logCard');
    }

    public function sizes(): SizeRange
    {
        return new SizeRange(Size::Full, Size::Half);
    }

    public function permission(): ?string
    {
        return 'logs.view';
    }

    /**
     * Keyed to its defaults in Settings → Widgets.
     */
    public function fields(): array
    {
        return [
            WidgetField::number('fileCount', lang('Tools.logActivityDays'), default: 7, min: 1, max: self::MAX_DAYS, key: 'LogActivity.logActivity_fileCount', description: lang('Tools.logActivityDaysHint')),
            WidgetField::toggle('hideDebug', lang('Tools.logActivityHideDebug'), default: false, key: 'LogActivity.logActivity_hideDebug'),
        ];
    }

    /**
     * The levels shown, most severe first.
     *
     * @return list<string>
     */
    public function levels(bool $hideDebug): array
    {
        $levels = array_keys(self::LEVELS);

        return $hideDebug ? array_values(array_diff($levels, ['debug'])) : $levels;
    }

    /**
     * The last days up to today, oldest first, with the entry count per shown
     * level of their log file. Days without a log file have no name or counts.
     *
     * @return list<array{name: string|null, label: string, counts: array<string, int>}>
     */
    public function days(int $count, bool $hideDebug): array
    {
        $levels  = $this->levels($hideDebug);
        $pattern = (new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('Md');
        $days    = [];

        for ($ago = $count - 1; $ago >= 0; $ago--) {
            $day  = Time::now()->subDays($ago);
            $name = 'log-' . $day->toDateString();
            $has  = $this->store->has($name);

            $days[] = [
                'name'   => $has ? $name : null,
                'label'  => $day->toLocalizedString($pattern),
                'counts' => $has ? $this->counts($this->store->levelCounts($name), $levels) : [],
            ];
        }

        return $days;
    }

    public function render(WidgetValues $values): string
    {
        $hideDebug = (bool) $values->get('hideDebug');
        $days      = $this->days((int) $values->get('fileCount'), $hideDebug);

        return view('Bonfire\Tools\Views\_log_activity', [
            'title'  => $this->title(),
            'levels' => $this->levels($hideDebug),
            'days'   => $days,
            'errors' => array_sum(array_column(array_column($days, 'counts'), 'error')),
            'most'   => max([1, ...array_map(static fn (array $day) => array_sum($day['counts']), $days)]),
        ]);
    }

    /**
     * @param array<string, int> $levelCounts Entries per log level, e.g. ['ERROR' => 2]
     * @param list<string>       $levels      The levels shown
     *
     * @return array<string, int>
     */
    private function counts(array $levelCounts, array $levels): array
    {
        $counts = [];

        foreach ($levels as $level) {
            $counts[$level] = array_sum(array_intersect_key($levelCounts, array_flip(self::LEVELS[$level])));
        }

        return $counts;
    }
}
