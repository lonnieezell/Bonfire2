<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Tests\Widgets;

use Bonfire\Widgets\Database\Migrations\ForgetWidgetsSettings;
use Config\Database;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ForgetWidgetsSettingsTest extends TestCase
{
    protected $refresh = true;

    public function testItForgetsTheRowsOfTheRemovedWidgetsSettingsAndKeepsTheRest(): void
    {
        foreach (['Bonfire\Widgets\Config\Stats', 'Bonfire\Widgets\Config\BarChart', 'Bonfire\Widgets\Config\LogActivity', 'Bonfire\Dashboard\Config\Dashboard'] as $class) {
            $this->db->table('settings')->insert(['class' => $class, 'key' => 'something', 'value' => '1', 'type' => 'string', 'context' => null, 'created_at' => '2026-10-28 00:00:00', 'updated_at' => '2026-10-28 00:00:00']);
        }

        (new ForgetWidgetsSettings(Database::forge()))->up();

        $this->dontSeeInDatabase('settings', ['class' => 'Bonfire\Widgets\Config\Stats']);
        $this->dontSeeInDatabase('settings', ['class' => 'Bonfire\Widgets\Config\BarChart']);
        $this->dontSeeInDatabase('settings', ['class' => 'Bonfire\Widgets\Config\LogActivity']);
        $this->seeInDatabase('settings', ['class' => 'Bonfire\Dashboard\Config\Dashboard']);
    }
}
