<?php

namespace Tests\Bonfire\Logs;

use Bonfire\Tools\Libraries\Logs;
use Tests\Support\TestCase;

/**
 * @internal
 */
final class LogsTest extends TestCase
{
    /**
     * @var string
     */
    protected $logFileName;

    /**
     * @var string
     */
    protected $logsPath = WRITEPATH . 'logs/';

    protected function setUp(): void
    {
        parent::setUp();

        log_message('error', 'Log error test');

        $this->logFileName = 'log-' . date('Y-m-d') . '.log';
    }

    public function testViewLogFile()
    {
        $user = $this->createUser();
        $user->addGroup('superadmin');

        $response = $this->actingAs($user)
            ->get(ADMIN_AREA . '/tools/view-log/' . $this->logFileName);

        $response->assertOK();
        $response->assertSee('Logs : ' . date('F j, Y'));
    }

    public function testListLogsFiles()
    {
        $logs = get_filenames($this->logsPath . $this->logFileName);

        unset($logs[0]);

        $this->assertIsArray($logs);
        $this->assertEmpty($logs);
    }

    public function testListFileLogs()
    {
        $logHandler = new Logs();

        $logs = $logHandler->processFileLogs($this->logsPath . $this->logFileName);

        $this->assertIsArray($logs);
    }

    public function testDeleteLog()
    {
        $this->assertTrue(@unlink($this->logsPath . $this->logFileName));
    }

    public function testDeleteAllLogs()
    {
        log_message('error', 'Log error test1');
        log_message('error', 'Log error test2');

        helper('filesystem');

        $this->assertTrue(delete_files($this->logsPath));

        @copy(APPPATH . '/index.html', "{$this->logsPath}index.html");
    }
}
