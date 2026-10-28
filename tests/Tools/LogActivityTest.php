<?php

namespace Tests\Tools;

use Bonfire\Tools\Libraries\LogStore;
use Bonfire\Tools\Widgets\LogActivity;
use Bonfire\Widgets\WidgetKind;
use Bonfire\Widgets\WidgetOptions;
use CodeIgniter\I18n\Time;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LogActivityTest extends TestCase
{
    protected $refresh = true;
    private string $dir;
    private LogActivity $widget;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dir = sys_get_temp_dir() . '/bonfire-logs-' . uniqid('', true) . '/';
        mkdir($this->dir);

        $this->widget = new LogActivity(new LogStore($this->dir));

        // Options stored by an earlier test stay in the shared settings cache
        WidgetOptions::forAlias('logactivity')->reset();

        Time::setTestNow('2024-01-03');
    }

    protected function tearDown(): void
    {
        array_map(unlink(...), glob($this->dir . '*'));
        rmdir($this->dir);
        Time::setTestNow();

        parent::tearDown();
    }

    /**
     * @param list<string> $levels
     */
    private function writeLog(string $name, array $levels = []): void
    {
        $lines = array_map(static fn (string $level) => "{$level} - 2024-01-01 10:00:00 --> message", $levels);

        file_put_contents($this->dir . $name . '.log', implode("\n", $lines));
    }

    public function testIsAnEnableableLogsWidget()
    {
        $this->assertSame(WidgetKind::Logs, $this->widget->settings()->kind);
        $this->assertSame('Stats.Logs_logActivity', $this->widget->settings()->key());
        $this->assertSame('Log activity', $this->widget->title());
    }

    public function testDaysAreTheLastOnesToTodayOldestFirst()
    {
        setting('LogActivity.logActivity_fileCount', '2');

        $this->writeLog('log-2024-01-01');
        $this->writeLog('log-2024-01-03');
        $this->writeLog('log-2024-01-02');

        $this->assertSame(
            ['log-2024-01-02', 'log-2024-01-03'],
            array_column($this->widget->days(), 'name'),
        );
    }

    public function testDaysWithoutALogFileHaveNoNameAndNoCounts()
    {
        setting('LogActivity.logActivity_fileCount', '3');

        $this->writeLog('log-2024-01-02', ['ERROR']);

        $days = $this->widget->days();

        $this->assertSame([null, 'log-2024-01-02', null], array_column($days, 'name'));
        $this->assertSame([], $days[0]['counts']);
        $this->assertSame([], $days[2]['counts']);
    }

    public function testDaysAreLabelledByMonthAndDay()
    {
        Time::setTestNow('2024-09-10');

        $this->assertSame('9/10', $this->widget->days()[6]['label']);
    }

    public function testLevelsAreGroupedIntoErrorWarningInfoAndDebug()
    {
        $this->writeLog('log-2024-01-03', ['ERROR', 'CRITICAL', 'ALERT', 'EMERGENCY', 'WARNING', 'NOTICE', 'INFO', 'DEBUG']);

        $this->assertSame(['error', 'warning', 'info', 'debug'], $this->widget->levels());
        $this->assertSame(
            ['error' => 4, 'warning' => 1, 'info' => 2, 'debug' => 1],
            $this->widget->days()[6]['counts'],
        );
    }

    public function testHidingDebugLeavesItOutOfLevelsAndCounts()
    {
        setting('LogActivity.logActivity_hideDebug', 'on');

        $this->writeLog('log-2024-01-03', ['ERROR', 'DEBUG']);

        $this->assertSame(['error', 'warning', 'info'], $this->widget->levels());
        $this->assertSame(['error' => 1, 'warning' => 0, 'info' => 0], $this->widget->days()[6]['counts']);
    }

    public function testDayCountIsKeptBetweenOneAndThirtyOne()
    {
        setting('LogActivity.logActivity_fileCount', '0');
        $this->assertCount(1, $this->widget->days());

        setting('LogActivity.logActivity_fileCount', '100');
        $this->assertCount(31, $this->widget->days());
    }

    public function testDefaultsToSevenDays()
    {
        $this->assertCount(7, $this->widget->days());
    }
}
