<?php

namespace Tests\Tools;

use Bonfire\Tools\Libraries\Logs;
use CodeIgniter\HTTP\DownloadResponse;
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
        // ...and a page size too small to reach this test's log.
        setting('Site.perPage', 20);

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

    public function testDeleteActionDeletesTheSelectedLogs(): void
    {
        $response = $this->actingAs($this->userWith('superadmin'))->post(route_to('action', 'logs', 'delete-logs'), [
            csrf_token() => csrf_hash(),
            'selects'    => [self::LOG_NAME, '../../index'],
            'confirmed'  => '1',
        ]);

        $response->assertSessionHas('message', lang('Tools.deleteSuccess') . ' Skipped 1.');
        $this->assertFileDoesNotExist($this->logFile);
        $this->assertFileExists(WRITEPATH . 'logs/index.html');
    }

    public function testDeleteActionNeedsLogsManage(): void
    {
        $this->actingAs($this->userWith('admin'))->post(route_to('action', 'logs', 'delete-logs'), [
            csrf_token() => csrf_hash(),
            'selects'    => [self::LOG_NAME],
            'confirmed'  => '1',
        ]);

        $this->assertFileExists($this->logFile);
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

    public function testListRowsShowDateLevelCountsTotalAndFile(): void
    {
        file_put_contents($this->logFile, implode("\n", [
            'ERROR - 2000-01-01 10:00:00 --> one',
            'ERROR - 2000-01-01 10:00:01 --> two',
            'INFO - 2000-01-01 10:00:02 --> three',
        ]));

        $html  = $this->actingAs($this->userWith('superadmin'))->get(route_to('sys-logs'))->getBody();
        $files = count(glob(WRITEPATH . 'logs/log-*.log'));

        $this->assertMatchesRegularExpression('#<h1>Logs</h1>\s*<span class="bf-count">' . $files . '</span>#', $html);
        $this->assertMatchesRegularExpression('#<tr data-levels="error info"#', $html);
        $this->assertStringContainsString('<div class="bf-log-weekday">Saturday</div>', $html);
        $this->assertMatchesRegularExpression('#<span class="bf-level-bar-segment bf-level-danger" style="width: 66\.\d+%"#', $html);
        $this->assertMatchesRegularExpression('#<span class="bf-level-tag bf-level-danger">error 2</span>\s*<span class="bf-level-tag bf-level-info">info 1</span>#', $html);
        $this->assertStringContainsString('<td class="text-end">3</td>', $html);
        $this->assertStringContainsString('<td class="bf-mono bf-muted">' . self::LOG_NAME . '.log</td>', $html);
    }

    public function testListHasFilterChipsSelectionAndATwoStepDeleteAll(): void
    {
        // The raw body: the DOM-parsed getBody() drops Alpine's @-attributes.
        $html = (string) $this->actingAs($this->userWith('superadmin'))->get(route_to('sys-logs'))->response()->getBody();

        foreach (['All files', 'With errors', 'With warnings'] as $chip) {
            $this->assertMatchesRegularExpression('#<button type="button" class="bf-chip[^"]*"[^>]*>\s*' . $chip . '#', $html);
        }
        $this->assertStringContainsString('x-data="bfSelectionBar"', $html);
        $this->assertMatchesRegularExpression('#<button type="submit" formaction="' . preg_quote(url_to('action', 'logs', 'delete-logs'), '#') . '" data-confirm-bulk="1"\s+class="btn btn-danger" data-confirm="#', $html);
        $this->assertStringContainsString('name="selects[]"', $html);
        $this->assertMatchesRegularExpression('#<button type="button"[^>]*@click="confirmAll = true"[^>]*>\s*Delete all log files#', $html);
        $this->assertMatchesRegularExpression('#<button type="submit" name="delete_all" value="1"#', $html);
    }

    public function testListHidesManagementWithoutLogsManage(): void
    {
        $html = $this->actingAs($this->userWith('admin'))->get(route_to('sys-logs'))->getBody();

        $this->assertStringNotContainsString('name="selects[]"', $html);
        $this->assertStringNotContainsString('delete_all', $html);
    }

    public function testViewShowsHeaderEntryCountFiltersAndActions(): void
    {
        file_put_contents($this->logFile, implode("\n", [
            'ERROR - 2000-01-01 10:00:00 --> Log error test',
            '#0 stack frame',
            'INFO - 2000-01-01 10:00:02 --> fine',
        ]));

        $html = $this->actingAs($this->userWith('superadmin'))->get(route_to('view-log', self::LOG_NAME))->getBody();

        $this->assertStringContainsString(self::LOG_NAME . '.log &middot; 2 entries', $html);
        $this->assertStringContainsString('href="' . url_to('log-download', self::LOG_NAME) . '"', $html);
        $this->assertMatchesRegularExpression('#role="radiogroup" aria-label="Level"#', $html);
        $this->assertStringContainsString('x-model="search"', $html);
        $this->assertMatchesRegularExpression('#<details class="bf-log-trace">\s*<summary>#', $html);
        $this->assertMatchesRegularExpression('#<button type="submit" name="delete" value="1"#', $html);
    }

    public function testDownloadSendsTheLogFile(): void
    {
        $response = $this->actingAs($this->userWith('admin'))->get(route_to('log-download', self::LOG_NAME));

        $download = $response->response();
        $this->assertInstanceOf(DownloadResponse::class, $download);
        $download->buildHeaders();
        $this->assertStringContainsString('attachment; filename="' . self::LOG_NAME . '.log"', $download->getHeaderLine('Content-Disposition'));
        $this->assertSame(filesize($this->logFile), $download->getContentLength());
    }

    public function testDownloadNeedsLogsViewAndAKnownLog(): void
    {
        $user = $this->createUser();
        $user->addPermission('admin.access');

        $this->actingAs($user)->get(route_to('log-download', self::LOG_NAME))->assertRedirectTo(ADMIN_AREA);
        $this->actingAs($this->userWith('superadmin'))->get(route_to('log-download', 'log-1999-01-01'))->assertRedirectTo(ADMIN_AREA . '/tools/logs');
    }
}
