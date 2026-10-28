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
 * The "Log activity" dashboard widget: the entries of the newest log
 * files, per level, as a stacked bar per file.
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

    private const MAX_FILES = 31;

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
     * The newest log files, oldest first, with their entry count per shown level.
     *
     * @return list<array{name: string, label: string, counts: array<string, int>}>
     */
    public function files(): array
    {
        $count = max(1, min(self::MAX_FILES, (int) $this->options()->get('fileCount')));
        $names = array_reverse(array_slice($this->store->names(), 0, $count));

        $pattern = (new IntlDatePatternGenerator(service('request')->getLocale()))->getBestPattern('Md');

        return array_map(fn (string $name) => [
            'name'   => $name,
            'label'  => Time::parse(substr($name, 4))->toLocalizedString($pattern),
            'counts' => $this->counts($this->store->levelCounts($name)),
        ], $names);
    }

    /**
     * The widget's card, or nothing for users who cannot view the logs.
     */
    public function render(): string
    {
        if (! auth()->user()?->can('logs.view')) {
            return '';
        }

        $files = $this->files();

        return view('Bonfire\Tools\Views\_log_activity', [
            'title'  => $this->title(),
            'levels' => $this->levels(),
            'files'  => $files,
            'errors' => array_sum(array_column(array_column($files, 'counts'), 'error')),
            'most'   => max([1, ...array_map(static fn (array $file) => array_sum($file['counts']), $files)]),
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
