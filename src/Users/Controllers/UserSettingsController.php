<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Users\Controllers;

use Bonfire\Core\BaseController;

class UserSettingsController extends BaseController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Users\Views\\';

    /**
     * Display the Email settings page.
     *
     * @return string
     */
    public function index()
    {
        if (! auth()->user()->can('users.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $rememberOptions = [
            lang('Users.rememberHours', [1])  => HOUR,
            lang('Users.rememberHours', [4])  => 4 * HOUR,
            lang('Users.rememberHours', [8])  => 8 * HOUR,
            lang('Users.rememberHours', [24]) => 24 * HOUR,
            lang('Users.rememberWeeks', [1])  => WEEK,
            lang('Users.rememberWeeks', [2])  => 2 * WEEK,
            lang('Users.rememberWeeks', [3])  => 3 * WEEK,
            lang('Users.rememberMonths', [1]) => MONTH,
            lang('Users.rememberMonths', [2]) => 2 * MONTH,
            lang('Users.rememberMonths', [6]) => 6 * MONTH,
            lang('Users.rememberYears', [1])  => 12 * MONTH,
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
        if (! auth()->user()->can('users.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $rules = [
            'minimumPasswordLength' => 'required|integer|greater_than[6]',
            'defaultGroup'          => 'required|string',
            'avatarResize'          => 'required|in_list[0,1]',
            'avatarSize'            => 'required_with[avatarResize]|integer|greater_than_equal_to[' . (setting('Users.avatarResizeFloor') ?? '32') . ']',
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
        $actions['login']    = $this->request->getPost('email2FA') ?: null;
        $actions['register'] = $this->request->getPost('emailActivation') ?: null;
        setting('Auth.actions', $actions);

        // Remember Me
        $sessionConfig                     = setting('Auth.sessionConfig');
        $sessionConfig['allowRemembering'] = (bool) $this->request->getPost('allowRemember');

        // The select posts a string, but Shield hands this value to
        // Time::addSeconds(), which requires an int. Leave the stored
        // length alone when "How long to remember..." (0) was posted.
        $rememberLength = (int) $this->request->getPost('rememberLength');
        if ($rememberLength > 0) {
            $sessionConfig['rememberLength'] = $rememberLength;
        }

        setting('Auth.sessionConfig', $sessionConfig);

        // Avatars
        setting('Users.useGravatar', $this->request->getPost('useGravatar') ?? false);
        setting('Users.gravatarDefault', $this->request->getPost('gravatarDefault'));
        setting('Users.avatarNameBasis', $this->request->getPost('avatarNameBasis'));
        setting('Users.avatarResize', (bool) $this->request->getPost('avatarResize'));
        if ((int) $this->request->getPost('avatarResize') === 1) {
            setting('Users.avatarSize', (int) $this->request->getPost('avatarSize'));
        }

        alert('success', lang('Bonfire.settingsSaved'));

        return redirect()->back();
    }
}
