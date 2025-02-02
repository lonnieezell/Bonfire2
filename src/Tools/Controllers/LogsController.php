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
    protected $ext        = '.log';
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
        $logs = array_reverse(get_filenames($this->logsPath));

        // Define the regular expression pattern for log files
        $logPattern = '/^log-\d{4}-\d{2}-\d{2}\.log$/';
        // Filter the array removing index.html and other files that do not match
        $logs = array_filter($logs, static fn ($filename) => preg_match($logPattern, $filename));

        $result = $this->logsHandler->paginateLogs($logs, $this->logsLimit);
        // Cycle through the $result array and attach the content property
        $counter = count($result['logs']);

        // Cycle through the $result array and attach the content property
        for ($i = 0; $i < $counter; $i++) {
            if ($result['logs'][$i] === 'index.html') {
                unset($result['logs'][$i]);
                continue;
            }
            $logFilePath = $this->logsPath . $result['logs'][$i];
            $result['logs'][$i] = [
                'filename' => $result['logs'][$i],
                'content' => $this->logsHandler->countLogLevels($logFilePath),
            ];
        }

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
        $file = sanitize_filename($file);

        if (empty($file) || ! file_exists($this->logsPath . $file . $this->ext)) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with('danger', lang('Tools.empty'));
        }

        $logs = $this->logsHandler->processFileLogs($this->logsPath . $file . $this->ext);

        $result = $this->logsHandler->paginateLogs($logs, $this->logsLimit);

        $filePagerData = $this->logsHandler->getAdjacentLogFiles($file, $this->logsPath);

        return $this->render($this->viewPrefix . 'view_log', [
            'logFile'       => $file . $this->ext,
            'canDelete'     => 1,
            'logContent'    => $result['logs'],
            'pager'         => $result['pager'],
            'filesPager'    => view($this->viewPrefix . '_pager', $filePagerData),
            'logFilePretty' => app_date(str_replace('log-', '', $file)),
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

        if (! empty($delete) && isset($_POST['checked'])) {
            helper('security');

            $checked    = $_POST['checked'];
            $numChecked = count($checked);

            if (is_array($checked) && $numChecked) {
                foreach ($checked as $file) {
                    @unlink($this->logsPath . sanitize_filename($file . $this->ext));
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
