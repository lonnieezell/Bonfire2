<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Widgets\Types\Stats;

use Bonfire\Widgets\Interfaces\Item;
use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;
use Closure;

/**
 * Represents an individual widget stats.
 *
 * @property string $bgColor
 * @property string $faIcon
 * @property string $id
 * @property string $title
 * @property string $url
 * @property string $value
 */
class StatsItem implements Item
{
    /**
     * @var string|null
     */
    protected $title;

    /**
     * Unique identifier for the widget, used for storage in settings.
     *
     * @var string
     */
    protected $id;

    /**
     * @var string|null
     */
    protected $value;

    /**
     * FontAwesome 5 icon name
     *
     * @var string|null
     */
    protected $faIcon;

    /**
     * @var string|null
     */
    protected $url;

    /**
     * The assignable background color on the statistics widget
     *
     * Possible values are:
     * bg-blue
     * bg-red
     * bg-orange
     * bg-light
     * bg-dark
     * bg-inverse
     * bg-indigo
     * bg-purple
     * bg-pink
     * bg-yellow
     * bg-green
     * bg-teal
     * bg-lime
     * bg-cyan
     * bg-white
     * bg-gray
     * bg-gray-dark
     */
    protected $bgColor;

    /**
     * The permission a user needs to see the item on their dashboard.
     */
    protected ?string $permission = null;

    /**
     * The name of the module that adds the item, its heading in the widget library.
     */
    protected ?string $module = null;

    /**
     * The query addValue() or addValueByFreeQuery() stored, run when the value is first read unless it is set first.
     */
    private ?Closure $fetch = null;

    public function __construct(?array $data = null)
    {
        if (! is_array($data)) {
            return;
        }

        foreach ($data as $key => $value) {
            $method = 'set' . ucfirst((string) $key);
            if (method_exists($this, $method)) {
                $this->{$method}($value);
            }
        }
    }

    public function setTitle(?string $title): StatsItem
    {
        $this->title = $title;

        return $this;
    }

    public function setId(?string $id): StatsItem
    {
        $this->id = $id;

        return $this;
    }

    public function setValue(?string $value): StatsItem
    {
        $this->value = $value;
        $this->fetch = null;

        return $this;
    }

    public function setFaIcon(?string $faIcon): StatsItem
    {
        $this->faIcon = $faIcon;

        return $this;
    }

    public function setPermission(?string $permission): StatsItem
    {
        $this->permission = $permission;

        return $this;
    }

    public function setUrl(string $url = '#'): StatsItem
    {
        $this->url = str_contains($url, '://')
            ? $url
            : '/' . ltrim($url, '/ ');

        return $this;
    }

    /**
     * The assignable background color on the statistics widget
     *
     * Possible values are:
     * bg-blue
     * bg-red
     * bg-orange
     * bg-light
     * bg-dark
     * bg-inverse
     * bg-indigo
     * bg-purple
     * bg-pink
     * bg-yellow
     * bg-green
     * bg-teal
     * bg-lime
     * bg-cyan
     * bg-white
     * bg-gray
     * bg-gray-dark
     */
    public function setBgColor(string $bgColor = 'bg-blue'): StatsItem
    {
        $this->bgColor = $bgColor;

        return $this;
    }

    public function __get(string $key)
    {
        if (method_exists($this, $key)) {
            return $this->{$key}();
        }
    }

    public function title(): ?string
    {
        return mb_strtoupper((string) $this->title);
    }

    public function id(): ?string
    {
        return $this->id;
    }

    public function value(): ?string
    {
        if ($this->fetch !== null) {
            $this->setValue(($this->fetch)());
        }

        return $this->value;
    }

    public function faIcon(): ?string
    {
        return $this->faIcon;
    }

    public function url(): ?string
    {
        return $this->url;
    }

    public function permission(): ?string
    {
        return $this->permission;
    }

    public function module(): ?string
    {
        return $this->module;
    }

    public function setModule(?string $module): StatsItem
    {
        $this->module = $module;

        return $this;
    }

    public function bgColor(): ?string
    {
        return $this->bgColor;
    }

    public function settings(): ItemSettings
    {
        return new ItemSettings(WidgetKind::Stats, $this->id);
    }

    /**
     * Stores the query, run when the value is first read: only an item the dashboard shows runs it.
     */
    public function addValue(string $tableName, ?string $whereString = null, string $selectMode = 'count'): StatsItem
    {
        $this->fetch = static function () use ($tableName, $whereString, $selectMode): string {
            $query = db_connect()->table($tableName);
            $query->where('deleted_at');
            if ($whereString) {
                $query->where($whereString);
            }

            $value = match ($selectMode) {
                'count' => $query->countAllResults(),
                'avg'   => $query->selectAvg('value')->get()->getRow()->value,
                'max'   => $query->selectMax('value')->get()->getRow()->value,
                'min'   => $query->selectMin('value')->get()->getRow()->value,
                'sum'   => $query->selectSum('value')->get()->getRow()->value,
                default => $query->countAllResults(),
            };

            return self::format($value);
        };

        return $this;
    }

    /**
     * Stores the query, run when the value is first read: only an item the dashboard shows runs it.
     */
    public function addValueByFreeQuery(string $query): StatsItem
    {
        $this->fetch = static function () use ($query): string {
            $result = db_connect()->query($query)->getRowArray() ?? [];

            // Assuming the query returns a single value in the first column
            return self::format(reset($result));
        };

        return $this;
    }

    private static function format(mixed $value): string
    {
        // todo: format the value dynamically based on locale
        return is_float($value) ? number_format($value, 2, '.', '') : (string) $value;
    }
}
