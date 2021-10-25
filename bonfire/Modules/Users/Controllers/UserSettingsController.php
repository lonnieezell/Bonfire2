<?php

namespace Bonfire\Modules\Users\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Debug\Timer;

class UserSettingsController extends BaseController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Users\Views\\';

    /**
     * Display the Email settings page.
     *
     * @return string
     */
    public function index()
    {
        $rememberOptions = [
            '1 hour' => 1*HOUR,
            '4 hours' => 4*HOUR,
            '8 hours' => 8*HOUR,
            '25 hours' => 24*HOUR,
            '1 week' => 1*WEEK,
            '2 weeks' => 2*WEEK,
            '3 weeks' => 3*WEEK,
            '1 month' => 1*MONTH,
            '2 months' => 2*MONTH,
            '6 months' => 6*MONTH,
            '1 year' => 12*MONTH,
        ];

        return $this->render($this->viewPrefix .'user_settings', [
            'rememberOptions' => $rememberOptions,
            'defaultGroup' => setting('AuthGroups.defaultGroup'),
            'groups' => setting('AuthGroups.groups'),
        ]);
    }

    /**
     * Saves the email settings to the config file, where it
     * is automatically saved by our dynamic configuration system.
     */
    public function save()
    {
        $rules = [
            'minimumPasswordLength' => 'required|integer|greater_than[6]',
            'defaultGroup' => 'required|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('Auth.allowRegistration', $this->request->getPost('allowRegistration') == 1);
        setting('Auth.minimumPasswordLength', (int)$this->request->getPost('minimumPasswordLength'));
        setting('Auth.passwordValidators', $this->request->getPost('validators'));
        setting('AuthGroups.defaultGroup', $this->request->getPost('defaultGroup'));

        // Actions
        $actions = setting('Auth.actions');
        $actions['login'] = (bool)$this->request->getPost('email2FA');
        $actions['register'] = (bool)$this->request->getPost('emailActivation');
        setting('Auth.actions', $actions);

        // Remember Me
        $sessionConfig = setting('Auth.sessionConfig');
        $sessionConfig['allowRemembering'] = $this->request->getPost('allowRemember') == 1;
        $sessionConfig['rememberLength'] = $this->request->getPost('rememberLength');
        setting('Auth.sessionConfig', $sessionConfig);

        // Avatars
        setting('Users.useGravatar', $this->request->getPost('useGravatar') == 1);
        setting('Users.gravatarDefault', $this->request->getPost('gravatarDefault'));

        alert('success', lang('Bonfire.resourcesSaved', ['settings']));

        return redirect()->back();
    }
}
