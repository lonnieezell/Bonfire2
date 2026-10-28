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

use Bonfire\Dashboard\WidgetField;
use CodeIgniter\HTTP\IncomingRequest;

/**
 * The site defaults of one settings class, e.g. BarChart: the fields of the
 * registered widgets keyed to it (BarChart.bar_showTitle, ...), each
 * declared by its widget's fields(). A tab of Settings → Widgets.
 */
final readonly class WidgetOptions
{
    /**
     * @param list<WidgetField> $fields
     */
    private function __construct(public string $alias, public string $title, public array $fields)
    {
    }

    /**
     * One set per settings class the registered widgets' fields are keyed to, by alias.
     *
     * @return array<string, self>
     */
    public static function all(): array
    {
        $fields = [];

        foreach (service('dashboard')->all() as $widget) {
            foreach ($widget->fields() as $field) {
                if ($field->key !== null) {
                    $fields[strstr($field->key, '.', true)][$field->key] ??= $field;
                }
            }
        }

        helper('inflector');
        $sets = [];

        foreach ($fields as $class => $keyed) {
            $sets[strtolower($class)] = new self(strtolower($class), humanize(decamelize($class)), array_values($keyed));
        }

        return $sets;
    }

    /**
     * The set behind a tab of Settings → Widgets: "stats", "barchart", ...
     */
    public static function forAlias(string $alias): ?self
    {
        return self::all()[$alias] ?? null;
    }

    /**
     * Stores each posted value, as its field's type. A toggle left out is
     * off; a value that equals the default is forgotten, and one that is not
     * a value of the field keeps what is stored.
     */
    public function save(IncomingRequest $request): void
    {
        foreach ($this->fields as $field) {
            $value = $field->cast($request->getPost($field->name) ?? ($field->type === 'toggle' ? false : null));

            if ($value === null) {
                continue;
            }

            if ($value === $field->default) {
                setting()->forget($field->key);
            } else {
                setting($field->key, $value);
            }
        }
    }

    public function reset(): void
    {
        foreach ($this->fields as $field) {
            setting()->forget($field->key);
        }
    }
}
