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

use Bonfire\Widgets\ItemSettings;
use Bonfire\Widgets\WidgetKind;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class ItemSettingsTest extends TestCase
{
    protected $refresh = true;

    protected function setUp(): void
    {
        parent::setUp();

        setting()->flush();
    }

    public function testNamesTheStoredSettingAfterKindAndId()
    {
        $stats  = new ItemSettings(WidgetKind::Stats, 'users1');
        $charts = new ItemSettings(WidgetKind::Charts, 'users1');

        $this->assertSame('Stats.Stats_users1', $stats->key());
        $this->assertSame('Stats.Charts_users1', $charts->key());
        $this->assertSame('Stats_users1', $stats->fieldName());
        $this->assertSame('Charts_users1', $charts->fieldName());
    }

    public function testIsEnabledUntilSwitchedOff()
    {
        $settings = new ItemSettings(WidgetKind::Stats, 'users1');

        $this->assertTrue($settings->enabled());

        $settings->store(true);
        $this->assertTrue($settings->enabled());
        $this->assertSame('on', setting('Stats.Stats_users1'));

        $settings->store(false);
        $this->assertFalse($settings->enabled());
    }

    public function testForgetEnablesAgain()
    {
        $settings = new ItemSettings(WidgetKind::Charts, 'users1');
        $settings->store(false);

        $settings->forget();

        $this->assertTrue($settings->enabled());
    }

    public function testItemsAreIndependent()
    {
        (new ItemSettings(WidgetKind::Stats, 'a'))->store(false);

        $this->assertTrue((new ItemSettings(WidgetKind::Stats, 'b'))->enabled());
        $this->assertTrue((new ItemSettings(WidgetKind::Charts, 'a'))->enabled());
    }
}
