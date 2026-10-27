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
        $db = db_connect();

        helper('filesystem');
        helper('number');

        $serverLoad = function_exists('sys_getloadavg') ? current(sys_getloadavg()) : null;

        $files = [['name' => '/writable', 'dir' => true, 'writable' => is_really_writable(WRITEPATH)]];

        foreach (get_dir_file_info(WRITEPATH, true) as $name => $info) {
            $isDir   = is_dir(WRITEPATH . $name);
            $files[] = [
                'name'     => trim($name, ' /'),
                'dir'      => $isDir,
                'writable' => is_really_writable(WRITEPATH . $name),
                'modified' => $isDir ? null : $info['date'],
                'size'     => $isDir ? null : $info['size'],
            ];
        }

        return $this->render($this->viewPrefix . 'index', [
            'server' => [
                'PHP Version'         => PHP_VERSION,
                'CodeIgniter Version' => CodeIgniter::CI_VERSION,
                'SQL Engine'          => $db->DBDriver . ' ' . $db->getVersion(),
                'Server OS'           => php_uname('s') . ' ' . php_uname('r') . ' (' . php_uname('m') . ')',
                'Server Load'         => $serverLoad !== false && $serverLoad !== null ? number_format($serverLoad, 1) : 'Unknown',
                'Max Upload'          => ini_get('upload_max_filesize'),
                'Max POST'            => ini_get('post_max_size'),
                'Memory Limit'        => ini_get('memory_limit'),
            ],
            'envPresent'  => is_file(ROOTPATH . '.env'),
            'files'       => $files,
            'allWritable' => ! in_array(false, array_column($files, 'writable'), true),
        ]);
    }

    /**
     * Displays Detailed PHP Info
     */
    public function phpInfo()
    {
        echo phpinfo();
    }
}
