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

use Bonfire\Widgets\Interfaces\Widgets;

/**
 * Class Manager
 *
 * The main class used to work with widgets in the system.
 */
class Manager
{
    /**
     * A collection of widgets currently known about.
     */
    public array $widgets = [];

    /**
     * Creates a new widget in the system.
     *
     * @param Widgets $widget
     *
     * @return $this
     */
    public function createWidget($widget, string $name): Manager
    {
        $this->widgets[$name] = new $widget();

        return $this;
    }

    /**
     * Returns the specified widget instance
     *
     * @return mixed
     */
    public function widget(string $name)
    {
        return $this->widgets[$name];
    }

    public function manager(): array
    {
        $results = [];

        $widgets = service('widgets');

        foreach ($widgets as $widget) {
            foreach ($widget as $element) {
                $items = $element->items()[0]->items();

                if ($pos = strrpos(get_class($items[0]), '\\')) {
                    $pos = substr(get_class($items[0]), $pos + 1);
                }
                $pos = str_replace('Item', '', $pos);
                $i   = 0;

                switch ($pos) {
                    case 'Stats':
                        foreach ($items as $item) {
                            $results[] = [
                                'widget' => $pos,
                                'title'  => $item->title(),
                                'index'  => $i,
                            ];
                            $i++;
                        }
                        break;

                    case 'Cards':
                        foreach ($items as $item) {
                            $results[] = [
                                'widget' => $pos,
                                'title'  => $item->title(),
                                'index'  => $i,
                            ];
                            $i++;
                        }
                        break;

                    case 'Charts':
                        foreach ($items as $item) {
                            $results[] = [
                                'widget' => $pos,
                                'type'   => $item->type(),
                                'title'  => $item->title(),
                                'index'  => $i,
                            ];
                            $i++;
                        }
                        break;
                }
            }
        }

        return $results;
    }
}
