<?php

namespace Tests\Tools;

use Bonfire\Tools\Libraries\Logs;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LogsTest extends TestCase
{
    private const LOG_NAME = 'log-2000-01-01';

    protected $refresh      = true;
    private string $logFile = WRITEPATH . 'logs/' . self::LOG_NAME . '.log';

    protected function setUp(): void
    {
        parent::setUp();

        // Other tests may leave the site in offline mode, which redirects users without site.viewOffline
        setting('Site.siteOnline', true);

        file_put_contents($this->logFile, "ERROR - 2000-01-01 10:00:00 --> Log error test\n");
    }

    protected function tearDown(): void
    {
        @unlink($this->logFile);

        parent::tearDown();
    }

    private function userWith(string $group)
    {
        $user = $this->createUser();
        $user->addGroup($group);

        return $user;
    }

    public function testListShowsLogFiles()
    {
        $response = $this->actingAs($this->userWith('superadmin'))
            ->get(route_to('sys-logs'));

        $response->assertOK();
        $response->assertSee(self::LOG_NAME . '.log');
    }

    public function testViewLogFile()
    {
        $response = $this->actingAs($this->userWith('superadmin'))
            ->get(route_to('view-log', self::LOG_NAME));

        $response->assertOK();
        $response->assertSee(lang('Tools.log') . ': ' . app_date('2000-01-01'));
        $response->assertSee('Log error test');
    }

    public function testViewUnknownLogFileRedirectsToList()
    {
        $response = $this->actingAs($this->userWith('superadmin'))
            ->get(route_to('view-log', 'log-1999-01-01'));

        $response->assertRedirectTo(ADMIN_AREA . '/tools/logs');
    }

    public function testListNeedsLogsViewPermission()
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $response = $this->actingAs($user)->get(route_to('sys-logs'));

        $response->assertRedirectTo(ADMIN_AREA);
        $response->assertDontSee(self::LOG_NAME);
    }

    public function testViewNeedsLogsViewPermission()
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $response = $this->actingAs($user)->get(route_to('view-log', self::LOG_NAME));

        $response->assertRedirectTo(ADMIN_AREA);
        $response->assertDontSee('Log error test');
    }

    public function testDeleteNeedsLogsManagePermission()
    {
        // The admin group may view logs but not manage them
        $response = $this->actingAs($this->userWith('admin'))
            ->post(route_to('log-delete'), ['delete' => '1', 'checked' => [self::LOG_NAME]]);

        $response->assertRedirectTo(ADMIN_AREA);
        $this->assertFileExists($this->logFile);
    }

    public function testDeleteSelectedLogs()
    {
        $response = $this->actingAs($this->userWith('superadmin'))
            ->post(route_to('log-delete'), ['delete' => '1', 'checked' => [self::LOG_NAME]]);

        $response->assertRedirectTo(ADMIN_AREA . '/tools/logs');
        $this->assertFileDoesNotExist($this->logFile);
    }

    public function testDeleteAllLogs()
    {
        $response = $this->actingAs($this->userWith('superadmin'))
            ->post(route_to('log-delete'), ['delete_all' => '1']);

        $response->assertRedirectTo(ADMIN_AREA . '/tools/logs');
        $this->assertFileDoesNotExist($this->logFile);
    }

    public function testDeleteWithNothingSelectedChangesNothing()
    {
        $response = $this->actingAs($this->userWith('superadmin'))
            ->post(route_to('log-delete'), ['delete' => '1']);

        $response->assertRedirectTo(ADMIN_AREA . '/tools/logs');
        $this->assertFileExists($this->logFile);
    }

    public function testDeleteIgnoresNamesThatAreNotLogs()
    {
        $this->actingAs($this->userWith('superadmin'))
            ->post(route_to('log-delete'), ['delete' => '1', 'checked' => ['../../index']]);

        $this->assertFileExists(WRITEPATH . 'logs/index.html');
    }

    public function testParserIgnoresLogLevelWordsInsideMessages()
    {
        file_put_contents($this->logFile, "INFO - 2000-01-01 10:00:00 --> an ERROR is only mentioned\n");

        $this->assertSame(
            '<span class="text-info">info</span>: 1',
            (new Logs())->countLogLevels($this->logFile),
        );
    }

    public function testParserHandlesAnEmptyFile()
    {
        file_put_contents($this->logFile, '');

        $logs = new Logs();

        $this->assertSame([], $logs->processFileLogs($this->logFile));
        $this->assertSame('', $logs->countLogLevels($this->logFile));
    }
}
