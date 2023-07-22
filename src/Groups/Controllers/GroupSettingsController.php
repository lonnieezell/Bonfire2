<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Groups\Controllers;

use Bonfire\Core\AdminController;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\Shield\Authorization\Groups;

class GroupSettingsController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Groups\Views\\';

    /**
     * Displays a list of all Roles in the system.
     */
    public function list()
    {
        if (! auth()->user()->can('groups.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $groups = setting('AuthGroups.groups');
        asort($groups);

        // Find the number of users in this group
        foreach ($groups as $alias => &$group) {
            $group['user_count'] = db_connect()
                ->table('auth_groups_users')
                ->where('group', $alias)
                ->countAllResults(true);
        }

        return $this->render($this->viewPrefix . 'list', [
            'groups' => $groups,
        ]);
    }

    /**
     * Allows the user to choose the permissions for a group.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string
     */
    public function show(string $alias)
    {
        if (! auth()->user()->can('groups.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $group = setting('AuthGroups.groups')[$alias];

        if (empty($group)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        return $this->render($this->viewPrefix . 'form', [
            'group'      => $group,
            'groupAlias' => $alias,
        ]);
    }

    /**
     * Save the group settings
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function save(string $alias)
    {
        if (! auth()->user()->can('groups.settings')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $group = setting('AuthGroups.groups')[$alias];

        if (empty($group)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        // Validate
        $rules = [
            'title'       => 'required|string',
            'description' => 'permit_empty|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Save the settings
        $groupConfig         = setting('AuthGroups.groups');
        $groupConfig[$alias] = [
            'title'       => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
        ];

        setting('AuthGroups.groups', $groupConfig);

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['group']));
    }

    /**
     * Displays a list of all Permissions for a single group
     *
     * @return RedirectResponse|string
     */
    public function permissions(string $groupName)
    {
        if (! auth()->user()->can('groups.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $groups = new Groups();
        $group  = $groups->info($groupName);
        if ($group === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        $permissions = setting('AuthGroups.permissions');
        if (is_array($permissions)) {
            ksort($permissions);
        }

        return $this->render($this->viewPrefix . 'permissions', [
            'group'       => $group,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Updates the permissions for a single group.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function savePermissions(string $group)
    {
        if (! auth()->user()->can('groups.edit')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $groups = new Groups();
        $group  = $groups->info($group);
        if ($group === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        $permissions = $this->request->getPost('permissions');
        $group->setPermissions($permissions ?? []);

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['permissions']));
    }
}
