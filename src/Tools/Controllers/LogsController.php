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
use Bonfire\Tools\Libraries\LogStore;
use CodeIgniter\HTTP\RedirectResponse;

class LogsController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Tools\Views\\';
    protected LogStore $store;
    protected Logs $logsHandler;

    public function __construct()
    {
        $this->logsHandler = new Logs();
        $this->store       = new LogStore(parser: $this->logsHandler);
    }

    /**
     * Displays all logs.
     *
     * @return RedirectResponse|string
     */
    public function index()
    {
        if (! auth()->user()->can('logs.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $result = $this->paginate($this->store->names());

        return $this->render($this->viewPrefix . 'logs', [
            'logs' => array_map(
                fn (string $name) => ['name' => $name, 'content' => $this->store->summary($name)],
                $result['logs'],
            ),
            'pager' => $result['pager'],
        ]);
    }

    /**
     * Show the contents of a single log file.
     *
     * @param string $file The name of the file to view, without extension.
     *
     * @return RedirectResponse|string
     */
    public function view(string $file = '')
    {
        if (! auth()->user()->can('logs.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        if (! $this->store->has($file)) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with('danger', lang('Tools.empty'));
        }

        $result = $this->paginate($this->store->entries($file));

        return $this->render($this->viewPrefix . 'view_log', [
            'logFile'       => $file,
            'canDelete'     => auth()->user()->can('logs.manage'),
            'logContent'    => $result['logs'],
            'pager'         => $result['pager'],
            'filesPager'    => view($this->viewPrefix . '_pager', $this->store->neighbours($file)),
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
        if (! auth()->user()->can('logs.manage')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $checked   = $this->request->getPost('checked');
        $deleteAll = $this->request->getPost('delete_all') !== null;

        if ($checked === null && ! $deleteAll) {
            return redirect()->to(ADMIN_AREA . '/tools/logs')->with('error', lang('Tools.noLogsSelected'));
        }

        if ($this->request->getPost('delete') !== null && is_array($checked)) {
            $deleted = $this->store->delete($checked);

            return $this->deleteResult($deleted, 'Tools.deleteSuccess');
        }

        if ($deleteAll) {
            return $this->deleteResult($this->store->deleteAll(), 'Tools.deleteAllSuccess');
        }

        return redirect()->to(ADMIN_AREA . '/tools/logs')->with('error', lang('Bonfire.unknownAction'));
    }

    private function deleteResult(int $deleted, string $successMessage): RedirectResponse
    {
        $redirect = redirect()->to(ADMIN_AREA . '/tools/logs');

        return $deleted > 0
            ? $redirect->with('message', lang($successMessage))
            : $redirect->with('error', lang('Tools.deleteError'));
    }

    /**
     * @return array{pager: mixed, logs: array}
     */
    private function paginate(array $items): array
    {
        $page = max(1, (int) $this->request->getGet('page'));

        return $this->logsHandler->paginateLogs($items, (int) setting('Site.perPage'), $page);
    }
}
