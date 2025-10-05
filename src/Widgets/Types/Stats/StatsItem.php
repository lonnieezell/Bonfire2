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
     * @var bool
     */
    protected $dashboardRoute = false;

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

        // true if we are on Dashboard page
        $this->dashboardRoute = current_url() === config('App')->baseURL . '/' . ADMIN_AREA;
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

        return $this;
    }

    public function setFaIcon(?string $faIcon): StatsItem
    {
        $this->faIcon = $faIcon;

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

    public function bgColor(): ?string
    {
        return $this->bgColor;
    }

    public function addValue(string $tableName, ?string $whereString = null, string $selectMode = 'count'): StatsItem
    {
        // Check if we are on Dashboard page and the chart is enabled
        if (! $this->dashboardRoute || setting('Stats.Stats_' . $this->id) !== 'on') {
            return $this;
        }

        // Chart Section Begin
        $query = db_connect()->table($tableName);
        $query->where('deleted_at', null);
        if ($whereString) {
            $query->where($whereString);
        }

        $query = match ($selectMode) {
            'count' => $query->countAllResults(),
            'avg'   => $query->selectAvg('value')->get()->getRow()->value,
            'max'   => $query->selectMax('value')->get()->getRow()->value,
            'min'   => $query->selectMin('value')->get()->getRow()->value,
            'sum'   => $query->selectSum('value')->get()->getRow()->value,
            default => $query->countAllResults(),
        };

        // Check if the result is a float and format accordingly
        if (is_float($query)) {
            // todo: format the value dynamically based on locale
            $this->setValue(number_format($query, 2, '.', ''));
        } else {
            $this->setValue((string) $query);
        }

        return $this;
    }

    public function addValueByFreeQuery(string $query): StatsItem
    {
        // Check if we are on Dashboard page and the chart is enabled
        if (! $this->dashboardRoute || setting('Stats.Stats_' . $this->id) !== 'on') {
            return $this;
        }

        // Execute the query
        $result = db_connect()->query($query)->getRow();

        // Assuming the query returns a single value in the first column
        $value = reset($result);

        // Check if the result is a float and format accordingly
        if (is_float($value)) {
            $this->setValue(number_format($value, 2, '.', ''));
        } else {
            $this->setValue((string) $value);
        }

        return $this;
    }
}
