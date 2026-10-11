<?php

namespace Tests\View;

use Bonfire\View\Theme;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ThemeTest extends TestCase
{
    public function testAvailableListsEachThemeFolder(): void
    {
        $themes = array_column(Theme::available(), 'path', 'name');

        foreach (['Admin', 'App', 'Auth'] as $name) {
            $this->assertArrayHasKey($name, $themes);
            $this->assertDirectoryExists($themes[$name]);
        }
    }
}
