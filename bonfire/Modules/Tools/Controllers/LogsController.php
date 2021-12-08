<?php

namespace Bonfire\Tools\Controllers;

use App\Controllers\AdminController;
use Bonfire\Modules\Tools\Libraries\Logs;
use CodeIgniter\HTTP\RedirectResponse;

class LogsController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Tools\Views\\';

    protected $logsPath = WRITEPATH . 'logs/';

    protected $logsLimit;

    protected $logsHandler;

    public function __construct()
    {
        helper('filesystem');
        $this->logsLimit = service('settings')->get('Logs.paginationLimit');
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
        $file = sanitize_filename($file);

        if (empty($file) or ! file_exists($this->logsPath . $file)) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with('danger', lang('Logs.empty'));
        }

        $logs = $this->logsHandler->processFileLogs($this->logsPath . $file);

        $result = $this->logsHandler->paginateLogs($logs, $this->logsLimit);

        return $this->render($this->viewPrefix . 'view_log', [
            'logFile' => $file,
            'canDelete' => 1,
            'logContent' => $result['logs'],
            'pager' => $result['pager'],
            'logFilePretty' => date('F j, Y', strtotime(str_replace('.log', '', str_replace('log-', '', $file)))),
        ]);
    }

    /**
     * Delete the specified log file or all.
     *
     * @return RedirectResponse
     */
    public function delete()
    {
        $delete = $this->request->getPost('delete');
        $deleteAll = $this->request->getPost('delete_all');

        if (empty($delete) && empty($deleteAll)) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with(
                'error',
                lang('Bonfire.resourcesNotFound', ['logs'])
            );
        }

        if (! empty($delete)) {
            helper('security');

            $checked = $_POST['checked'];
            $numChecked = count($checked);

            if (is_array($checked) && $numChecked) {
                foreach ($checked as $file) {
                    @unlink($this->logsPath . sanitize_filename($file));
                }

                return redirect()->to(ADMIN_AREA . '/tools/logs')->with('message', lang('Logs.delete_success'));
            }
        }

        if (! empty($deleteAll)) {
            if (delete_files($this->logsPath)) {
                // Restore the index.html file.
                @copy(APPPATH . '/index.html', "{$this->logsPath}index.html");

                return redirect()->to(ADMIN_AREA . '/tools/logs')->with('message', lang('Logs.delete_all_success'));
            } else {
                return redirect()->to(ADMIN_AREA . '/tools/logs')->with('error', lang('Logs.delete_error'));
            }
        }

        return redirect()->to(ADMIN_AREA . '/tools/logs')->with('error', lang('Bonfire.unknownAction'));
    }
}
