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

use CodeIgniter\HTTP\IncomingRequest;

/**
 * The display options of Stats widgets and of each chart type, as data.
 *
 * Every set maps an option to the value stored when the settings form
 * leaves it out. Keys are <Config class>.<type>_<option>, e.g.
 * BarChart.bar_showTitle, and the defaults come from that Config class.
 * Saving, resetting and reading work the same for every set.
 */
final readonly class WidgetOptions
{
    /**
     * The one option that is not prefixed by its type, and that decides
     * whether CUSTOM options are kept or forgotten.
     */
    private const SWITCH = 'useCustomSettings';

    private const COMMON = [
        'showTitle'       => false,
        'showLegend'      => false,
        'legendPosition'  => false,
        'enableAnimation' => false,
    ];
    private const SCHEMED = self::COMMON + ['colorScheme' => 'null'];
    private const SETS    = [
        'stats' => ['showLink' => false],
        'line'  => self::COMMON + [
            'showSubTitle'  => false,
            'usePermission' => false,
            'tension'       => null,
            self::SWITCH    => false,
        ],
        'bar'       => self::SCHEMED,
        'doughnut'  => self::SCHEMED,
        'pie'       => self::SCHEMED,
        'polarArea' => self::SCHEMED,
    ];

    /**
     * Options only kept while SWITCH is on.
     */
    private const CUSTOM = [
        'borderColor'          => '#000000',
        'borderWidth'          => 1,
        'pointBackgroundColor' => null,
        'pointBorderColor'     => null,
        'pointBorderWidth'     => null,
    ];

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $custom
     */
    private function __construct(
        private string $type,
        private array $options,
        private array $custom,
    ) {
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return array_map(self::of(...), array_keys(self::SETS));
    }

    /**
     * The set behind a tab of the widgets settings: "stats", "barchart", ...
     */
    public static function forAlias(string $alias): ?self
    {
        foreach (self::all() as $set) {
            if ($set->alias() === $alias) {
                return $set;
            }
        }

        return null;
    }

    /**
     * The set of a ChartsItem type: "line", "bar", "polarArea", ...
     */
    public static function forChartType(?string $type): ?self
    {
        return $type !== 'stats' && isset(self::SETS[$type]) ? self::of($type) : null;
    }

    private static function of(string $type): self
    {
        $options = self::SETS[$type];

        return new self($type, $options, isset($options[self::SWITCH]) ? self::CUSTOM : []);
    }

    public function alias(): string
    {
        return strtolower($this->configClass());
    }

    /**
     * @return list<string>
     */
    public function optionNames(): array
    {
        return array_keys($this->options + $this->custom);
    }

    /**
     * The name of the form input carrying this option.
     */
    public function fieldName(string $option): string
    {
        return $option === self::SWITCH ? $option : $this->type . '_' . $option;
    }

    public function key(string $option): string
    {
        return $this->configClass() . '.' . $this->fieldName($option);
    }

    /**
     * The stored value, or the Config default; null for an option this set does not have.
     */
    public function get(string $option): mixed
    {
        return in_array($option, $this->optionNames(), true) ? setting()->get($this->key($option)) : null;
    }

    public function save(IncomingRequest $request): void
    {
        foreach ($this->options as $option => $whenAbsent) {
            setting($this->key($option), $request->getPost($this->fieldName($option)) ?? $whenAbsent);
        }

        $keepCustom = (bool) setting($this->key(self::SWITCH));

        foreach ($this->custom as $option => $whenAbsent) {
            if ($keepCustom) {
                setting($this->key($option), $request->getPost($this->fieldName($option)) ?? $whenAbsent);
            } else {
                setting()->forget($this->key($option));
            }
        }
    }

    public function reset(): void
    {
        foreach ($this->optionNames() as $option) {
            setting()->forget($this->key($option));
        }
    }

    private function configClass(): string
    {
        return $this->type === 'stats' ? 'Stats' : ucfirst($this->type) . 'Chart';
    }
}
