<?php

namespace Bonfire\Modules\Groups\Controllers;

use App\Controllers\AdminController;
use CodeIgniter\HTTP\RedirectResponse;
use Sparks\Shield\Authorization\Groups;

class GroupSettingsController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Groups\Views\\';

    /**
     * Displays a list of all Roles in the system.
     *
     * @return string
     */
    public function list()
    {
        $groups = setting('AuthGroups.groups');
        asort($groups);

        // Find the number of users in this group
        foreach ($groups as $alias => &$group) {
            $group['user_count'] = db_connect()
                ->table('auth_groups_users')
                ->where('group', $alias)
                ->countAllResults(true);
        }

        return $this->render($this->viewPrefix .'list', [
            'groups' => $groups,
        ]);
    }

    /**
     * Allows the user to choose the permissions for a group.
     *
     * @param string $alias
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string
     */
    public function show(string $alias)
    {
        $group = setting('AuthGroups.groups')[$alias];

        if (empty($group)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        return $this->render($this->viewPrefix .'form', [
            'group' => $group,
            'groupAlias' => $alias,
        ]);
    }

    /**
     * Save the group settings
     *
     * @param string $alias
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function save(string $alias)
    {
        if (! auth()->user()->can('groups.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $group = setting('AuthGroups.groups')[$alias];

        if (empty($group)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        // Validate
        $rules = [
            'title' => 'required|string',
            'description' => 'permit_empty|string',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Save the settings
        $groupConfig = setting('AuthGroups.groups');
        $groupConfig[$alias] = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
        ];

        setting('AuthGroups.groups', $groupConfig);

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['group']));
    }

    /**
     * Displays a list of all Permissions for a single group
     *
     * @return string|RedirectResponse
     */
    public function permissions(string $groupName)
    {
        if (! auth()->user()->can('groups.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $groups = new Groups();
        $group = $groups->info($groupName);
        if ($group === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        $permissions = setting('AuthGroups.permissions');
        if (is_array($permissions)) {
            ksort($permissions);
        }

        return $this->render($this->viewPrefix .'permissions', [
            'group' => $group,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Updates the permissions for a single group.
     *
     * @param string $group
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function savePermissions(string $group)
    {
        $groups = new Groups();
        $group = $groups->info($group);
        if ($group === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user group']));
        }

        $group->setPermissions($this->request->getPost('permissions') ?? []);

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['permissions']));
    }
}
