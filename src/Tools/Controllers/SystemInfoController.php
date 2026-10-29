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

        return $this->render($this->viewPrefix . 'index', [
            'server' => [
                'PHP Version'         => PHP_VERSION,
                'CodeIgniter Version' => CodeIgniter::CI_VERSION,
                'SQL Engine'          => $db->DBDriver . ' ' . $db->getVersion(),
                'Server OS'           => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
                'Server Load'         => $serverLoad !== null ? number_format($serverLoad, 1) : 'Unknown',
                'Max Upload'          => ini_get('upload_max_filesize'),
                'Max POST'            => ini_get('post_max_size'),
                'Memory Limit'        => ini_get('memory_limit'),
            ],
            'envPresent'  => $health->envPresent(),
            'files'       => $files,
            'allWritable' => ! in_array(false, array_column($files, 'writable'), true),
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
