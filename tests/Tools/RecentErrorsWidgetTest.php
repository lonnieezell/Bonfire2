<?php

namespace Tests\Tools;

use Bonfire\Dashboard\WidgetValues;
use Bonfire\Tools\Libraries\LogStore;
use Bonfire\Tools\Widgets\RecentErrorsWidget;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class RecentErrorsWidgetTest extends TestCase
{
    protected $refresh = true;
    private string $dir;
    private RecentErrorsWidget $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/bonfire-logs-' . uniqid('', true) . '/';
        mkdir($this->dir);

        $this->widget = new RecentErrorsWidget(new LogStore($this->dir));

        file_put_contents($this->dir . 'log-2024-01-02.log', implode("\n", [
            'ERROR - 2024-01-02 10:00:00 --> older error',
            'WARNING - 2024-01-02 11:00:00 --> a warning',
        ]));
        file_put_contents($this->dir . 'log-2024-01-03.log', implode("\n", [
            'CRITICAL - 2024-01-03 09:00:00 --> critical one',
            'INFO - 2024-01-03 10:00:00 --> just info',
            'ERROR - 2024-01-03 12:00:00 --> newest error',
            '#0 a stack trace line',
        ]));
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->dir . '*'));
        rmdir($this->dir);

        parent::tearDown();
    }

    public function testListsErrorsNewestFirstAcrossLogFiles(): void
    {
        $this->assertSame(
            ['2024-01-03 12:00:00', '2024-01-03 09:00:00', '2024-01-02 10:00:00'],
            array_column($this->widget->entries(5, 'errors'), 'date'),
        );
        $this->assertSame(['log-2024-01-03', 'log-2024-01-03'], array_column($this->widget->entries(2, 'errors'), 'log'));
    }

    public function testIncludesWarningsWhenAsked(): void
    {
        $this->assertSame(
            ['ERROR', 'CRITICAL', 'WARNING', 'ERROR'],
            array_column($this->widget->entries(5, 'warnings'), 'level'),
        );
    }

    public function testEachEntryLinksToItsLogFile(): void
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');
        $this->actingAs($user);

        $html = $this->widget->render(WidgetValues::resolve($this->widget->fields(), ['count' => 2]));

        $this->assertSame(2, substr_count($html, 'href="' . site_url(ADMIN_AREA . '/tools/view-log/log-2024-01-03') . '"'));
        $this->assertStringNotContainsString('log-2024-01-02"', $html);
    }

    public function testNeedsLogsViewAndShowsFiveErrorsByDefault(): void
    {
        $values = WidgetValues::resolve($this->widget->fields());

        $this->assertSame('tools:recentErrors', $this->widget->id());
        $this->assertSame('logs.view', $this->widget->permission());
        $this->assertSame(['count' => 5, 'level' => 'errors'], $values->all());
        $this->assertSame(20, $this->widget->fields()[0]->cast(50));
    }
}
