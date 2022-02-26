<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Users\Controllers;

use App\Controllers\BaseController;

class UserSettingsController extends BaseController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Modules\Users\Views\\';

    /**
     * Display the Email settings page.
     *
     * @return string
     */
    public function index()
    {
        $rememberOptions = [
            '1 hour'   => 1 * HOUR,
            '4 hours'  => 4 * HOUR,
            '8 hours'  => 8 * HOUR,
            '25 hours' => 24 * HOUR,
            '1 week'   => 1 * WEEK,
            '2 weeks'  => 2 * WEEK,
            '3 weeks'  => 3 * WEEK,
            '1 month'  => 1 * MONTH,
            '2 months' => 2 * MONTH,
            '6 months' => 6 * MONTH,
            '1 year'   => 12 * MONTH,
        ];

        return $this->render($this->viewPrefix . 'user_settings', [
            'rememberOptions' => $rememberOptions,
            'defaultGroup'    => setting('AuthGroups.defaultGroup'),
            'groups'          => setting('AuthGroups.groups'),
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
            'defaultGroup'          => 'required|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('Auth.allowRegistration', (bool) $this->request->getPost('allowRegistration'));
        setting('Auth.minimumPasswordLength', (int) $this->request->getPost('minimumPasswordLength'));
        setting('Auth.passwordValidators', $this->request->getPost('validators'));
        setting('AuthGroups.defaultGroup', $this->request->getPost('defaultGroup'));

        // Actions
        $actions             = setting('Auth.actions');
        $actions['login']    = $this->request->getPost('email2FA') ?? false;
        $actions['register'] = $this->request->getPost('emailActivation') ?? false;
        setting('Auth.actions', $actions);

        // Remember Me
        $sessionConfig                     = setting('Auth.sessionConfig');
        $sessionConfig['allowRemembering'] = $this->request->getPost('allowRemember') ?? false;
        $sessionConfig['rememberLength']   = $this->request->getPost('rememberLength');
        setting('Auth.sessionConfig', $sessionConfig);

        // Avatars
        setting('Users.useGravatar', $this->request->getPost('useGravatar') ?? false);
        setting('Users.gravatarDefault', $this->request->getPost('gravatarDefault'));
        setting('Users.avatarNameBasis', $this->request->getPost('avatarNameBasis'));

        alert('success', lang('Bonfire.resourcesSaved', ['settings']));

        return redirect()->back();
    }
}
