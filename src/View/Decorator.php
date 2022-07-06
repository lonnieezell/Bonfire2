<?php

namespace Bonfire\View;

use CodeIgniter\View\ViewDecoratorInterface;
use Bonfire\View\ComponentRenderer;

/**
 * Class Decorator
 *
 * Enables rendering of View Components into the views.
 */
class Decorator implements ViewDecoratorInterface
{
    private static ?ComponentRenderer $components = null;

    public static function decorate(string $html): string
    {
        $components = self::factory();

        $html = $components->render($html);

        return $html;
    }

    /**
     *  Factory method to create a new instance of ComponentRenderer
     */
    private static function factory(): ComponentRenderer
    {
        if (self::$components === null) {
            self::$components = new ComponentRenderer();
        }
        return self::$components;
    }
}
