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
use Bonfire\Tools\Libraries\SystemHealth;
use CodeIgniter\CodeIgniter;
use CodeIgniter\I18n\Time;

class SystemInfoController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Tools\Views\\';

    /**
     * Displays basic information about the site.
     *
     * @return string
     */
    public function index()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $db = db_connect();

        helper('number');

        $health     = new SystemHealth();
        $serverLoad = $health->serverLoad();
        $files      = $health->files();
        $lastPruned = service('personalData')->lastPruned();

        return $this->render($this->viewPrefix . 'index', [
            'server' => [
                lang('Tools.phpVersion')  => PHP_VERSION,
                lang('Tools.ciVersion')   => CodeIgniter::CI_VERSION,
                lang('Tools.sqlEngine')   => $db->DBDriver . ' ' . $db->getVersion(),
                lang('Tools.serverOs')    => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
                lang('Tools.serverLoad')  => $serverLoad !== null ? number_format($serverLoad, 1) : lang('Tools.unknown'),
                lang('Tools.maxUpload')   => ini_get('upload_max_filesize'),
                lang('Tools.maxPost')     => ini_get('post_max_size'),
                lang('Tools.memoryLimit') => ini_get('memory_limit'),
            ],
            'envPresent'  => $health->envPresent(),
            'files'       => $files,
            'allWritable' => ! in_array(false, array_column($files, 'writable'), true),
            // Pruning runs daily, so a week without it means the scheduler has stopped
            'pruneStale' => $lastPruned === null || $lastPruned->isBefore(Time::now()->subDays(7)),
            'lastPruned' => $lastPruned,
        ]);
    }

    /**
     * Displays Detailed PHP Info
     */
    public function phpInfo()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        echo phpinfo();
    }
}
