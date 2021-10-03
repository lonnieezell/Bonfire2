<?php

namespace Bonfire\Tools\Controllers;

use App\Controllers\AdminController;
use CodeIgniter\CodeIgniter;

class SystemInfoController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Tools\Views\\';

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

        return $this->render($this->viewPrefix .'index', [
            'ciVersion' => CodeIgniter::CI_VERSION,
            'dbDriver' => $db->DBDriver,
            'dbVersion' => $db->getVersion(),
            'serverLoad' => current(sys_getloadavg()),
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
