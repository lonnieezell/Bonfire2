<?php

namespace Bonfire\Modules\Settings\Controllers;

use App\Controllers\AdminController;

/**
 * General Site Settings
 */
class GeneralSettingsController extends AdminController
{
    /**
     * The theme to use.
     *
     * @var string
     */
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Settings\Views\\';

    /**
     * Displays the site's general settings.
     */
    public function general()
    {
        echo $this->render($this->viewPrefix .'general');
    }

    public function saveGeneral()
    {
        $rules = [
            'siteName' => 'required|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('App.siteName', $this->request->getPost('siteName', FILTER_SANITIZE_STRING));
        setting('App.siteOnline', $this->request->getPost('siteOnline') === '1');

        return redirect()->to(ADMIN_AREA .'/settings/general')->with('message', lang('Bonfire.resourcesSaved', ['settings']));
    }
}
