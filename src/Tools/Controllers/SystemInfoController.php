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
        global $app;
        $db = db_connect();

        helper('filesystem');
        helper('number');

        return $this->render($this->viewPrefix . 'index', [
            'ciVersion'  => CodeIgniter::CI_VERSION,
            'dbDriver'   => $db->DBDriver,
            'dbVersion'  => $db->getVersion(),
            'serverLoad' => function_exists('sys_getloadavg')?current(sys_getloadavg()):null,
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
