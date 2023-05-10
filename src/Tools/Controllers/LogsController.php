<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Tools\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Tools\Libraries\Logs;
use CodeIgniter\HTTP\RedirectResponse;

class LogsController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Tools\Views\\';
    protected $logsPath   = WRITEPATH . 'logs/';
    protected $logsLimit;
    protected $logsHandler;

    public function __construct()
    {
        helper('filesystem');
        $this->logsLimit   = setting('Site.perPage');
        $this->logsHandler = new Logs();
    }

    /**
     * Displays all logs.
     *
     * @return string
     */
    public function index()
    {
        // Load the Log Files.
        $logs = get_filenames($this->logsPath);

        unset($logs[0]);

        $result = $this->logsHandler->paginateLogs($logs, $this->logsLimit);

        return $this->render($this->viewPrefix . 'logs', [
            'logs'  => $result['logs'],
            'pager' => $result['pager'],
        ]);
    }

    /**
     * Show the contents of a single log file.
     *
     * @param string $file The full name of the file to view (including extension).
     *
     * @return RedirectResponse|string
     */
    public function view(string $file = '')
    {
        helper('security');
        $file = sanitize_filename($file) . '.log';

        if (empty($file) || ! file_exists($this->logsPath . $file)) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with('danger', lang('Tools.empty'));
        }

        $logs = $this->logsHandler->processFileLogs($this->logsPath . $file);

        $result = $this->logsHandler->paginateLogs($logs, $this->logsLimit);

        return $this->render($this->viewPrefix . 'view_log', [
            'logFile'       => str_replace('.log', '', $file),
            'canDelete'     => 1,
            'logContent'    => $result['logs'],
            'pager'         => $result['pager'],
            'logFilePretty' => app_date(str_replace('.log', '', str_replace('log-', '', $file))),
        ]);
    }

    /**
     * Delete the specified log file or all.
     *
     * @return RedirectResponse
     */
    public function delete()
    {
        $delete    = $this->request->getPost('delete');
        $deleteAll = $this->request->getPost('delete_all');

        if (empty($delete) && empty($deleteAll)) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with(
                'error',
                lang('Bonfire.resourcesNotFound', ['logs'])
            );
        }

        if (! empty($delete)) {
            helper('security');

            $checked    = $_POST['checked'];
            $numChecked = count($checked);

            if (is_array($checked) && $numChecked) {
                foreach ($checked as $file) {
                    @unlink($this->logsPath . sanitize_filename($file) . '.log');
                }

                return redirect()->to(ADMIN_AREA . '/tools/logs')->with('message', lang('Tools.deleteSuccess'));
            }
        }

        if (! empty($deleteAll)) {
            if (delete_files($this->logsPath)) {
                // Restore the index.html file.
                @copy(APPPATH . '/index.html', "{$this->logsPath}index.html");

                return redirect()->to(ADMIN_AREA . '/tools/logs')->with('message', lang('Tools.deleteAllSuccess'));
            }

            return redirect()->to(ADMIN_AREA . '/tools/logs')->with('error', lang('Tools.deleteError'));
        }

        return redirect()->to(ADMIN_AREA . '/tools/logs')->with('error', lang('Bonfire.unknownAction'));
    }
}
