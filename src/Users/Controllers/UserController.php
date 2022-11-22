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

use Bonfire\Core\AdminController;
use Bonfire\Users\Models\UserFilter;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\Database\Exceptions\DataException;
use CodeIgniter\Shield\Models\LoginModel;
use CodeIgniter\Shield\Models\UserIdentityModel;
use ReflectionException;

class UserController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Users\Views\\';

    /**
     * Display the uses currently in the system.
     *
     * @return string
     */
    public function list()
    {
        if (! auth()->user()->can('users.list')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        /** @var UserFilter $userModel */
        $userModel = model(UserFilter::class);

        $userModel->filter($this->request->getPost('filters'));

        $view = $this->request->getMethod() === 'post'
            ? $this->viewPrefix . '_table'
            : $this->viewPrefix . 'list';

        return $this->render($view, [
            'headers' => [
                'email'       => 'Email',
                'username'    => 'Username',
                'groups'      => 'Groups',
                'last_active' => 'Last Active',
            ],
            'showSelectAll' => true,
            'users'         => $userModel->paginate(setting('Site.perPage')),
            'pager'         => $userModel->pager,
        ]);
    }

    /**
     * Display the "new user" form.
     */
    public function create()
    {
        if (! auth()->user()->can('users.create')) {
            return redirect()->to(ADMIN_AREA . '/users')->with('error', lang('Bonfire.notAuthorized'));
        }

        $groups = setting('AuthGroups.groups');
        asort($groups);

        return $this->render($this->viewPrefix . 'form', [
            'groups' => $groups,
        ]);
    }

    /**
     * Display the Edit form for a single user.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string
     */
    public function edit(int $userId)
    {
        if (! auth()->user()->can('users.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();

        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        $groups = setting('AuthGroups.groups');
        asort($groups);

        return $this->render($this->viewPrefix . 'form', [
            'user'   => $user,
            'groups' => $groups,
        ]);
    }

    /**
     * Creates or saves the basic user details.
     *
     * @throws ReflectionException
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function save(?int $userId = null)
    {
        if (! auth()->user()->can('users.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();
        /**
         * @var User
         */
        $user = $userId !== null
            ? $users->find($userId)
            : new User();

        /** @phpstan-ignore-next-line */
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        /**
         * Perform validation here so we can merge the
         * basic model validation rules with the meta info rules.
         *
         * @var array
         */
        $rules = config('Users')->validation;
        $rules = array_merge($rules, $user->validationRules('meta'));

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        // Fill in basic details
        $user->fill($this->request->getPost());

        // Try saving basic details
        try {
            if (! $users->save($user)) {
                log_message('error', 'User errors', $users->errors());

                return redirect()->back()->withInput()->with('error', lang('Bonfire.unknownSaveError', ['user']));
            }
        } catch (DataException $e) {
            // Just log the message for now since it's
            // likely saying the user's data is all the same
            log_message('debug', 'SAVING USER: ' . $e->getMessage());
        }

        // We need an ID to on the entity to save groups.
        if ($user->id === null) {
            $user->id = $users->getInsertID();
        }

        // Check for an avatar to upload
        if ($file = $this->request->getFile('avatar')) {
            if ($file->isValid()) {
                $avatarDir = ROOTPATH . 'public/uploads/avatars';
                $filename  = $user->id . '_avatar.' . $file->getExtension();

                // Create if uploads/avatar directories not exist
                if (! is_dir($avatarDir)) {
                    mkdir($avatarDir, 0755, true);
                }

                if ($file->move($avatarDir, $filename, true)) {
                    $users->update($user->id, ['avatar' => $filename]);
                }
            }
        }

        // Save the new user's email/password
        $password = $this->request->getPost('password');
        $identity = $user->getEmailIdentity();
        if ($identity === null) {
            helper('text');
            $user->createEmailIdentity([
                'email'    => $this->request->getPost('email'),
                'password' => ! empty($password) ? $password : random_string('alnum', 12),
            ]);
        }
        // Update existing user's email identity
        else {
            $identity->secret = $this->request->getPost('email');
            if ($password !== null) {
                $identity->secret2 = service('passwords')->hash($password);
            }
            if ($identity->hasChanged()) {
                model(UserIdentityModel::class)->save($identity);
            }
        }

        // Save the user's groups
        $user->syncGroups(...($this->request->getPost('groups') ?? []));

        // Save the user's meta fields
        $user->syncMeta($this->request->getPost('meta') ?? []);

        return redirect()->to($user->adminLink())->with('message', lang('Bonfire.resourceSaved', ['user']));
    }

    /**
     * Change user's password.
     *
     * @throws ReflectionException
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function changePassword(?int $userId = null)
    {
        if (! auth()->user()->can('users.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();
        /**
         * @var User
         */
        $user = $userId !== null
            ? $users->find($userId)
            : new User();

        /** @phpstan-ignore-next-line */
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        if (! $this->validate(['password' => 'required|strong_password', 'pass_confirm' => 'required|matches[password]'])) {
            return redirect()->back()->withInput()->with('errors', service('validation')->getErrors());
        }

        // Save the new user's email/password
        $password = $this->request->getPost('password');
        $identity = $user->getEmailIdentity();

        if ($password !== null) {
            $identity->secret2 = service('passwords')->hash($password);
        }

        if ($identity->hasChanged()) {
            model(UserIdentityModel::class)->save($identity);
        }

        return redirect()->to($user->adminLink('/security'))->with('message', lang('Bonfire.resourceSaved', ['user']));
    }

    /**
     * Delete the specified user.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function delete(int $userId)
    {
        if (! auth()->user()->can('users.delete')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        /** @var User|null $user */
        $user = $users->find($userId);

        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        if (! $users->delete($user->id)) {
            log_message('error', implode(' ', $users->errors()));

            return redirect()->back()->with('error', lang('Bonfire.unknownError'));
        }

        return redirect()->back()->with('message', lang('Bonfire.resourceDeleted', ['user']));
    }

    /**
     * Deletes multiple users from the database.
     * Called via the checked() records in the table.
     */
    public function deleteBatch()
    {
        if (! auth()->user()->can('users.delete')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $ids = $this->request->getPost('selects');

        if (empty($ids)) {
            return redirect()->back()->with('error', lang('Bonfire.resourcesNotSelected', ['users']));
        }
        $ids = array_keys($ids);

        $users = model(UserModel::class);

        if (! $users->delete($ids)) {
            log_message('error', implode(' ', $users->errors()));

            return redirect()->back()->with('error', lang('Bonfire.unknownError'));
        }

        return redirect()->back()->with('message', lang('Bonfire.resourcesDeleted', ['users']));
    }

    /**
     * Displays basic security info, like previous login info,
     * and ability to force a password reset, ban, etc.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function security(int $userId)
    {
        if (! auth()->user()->can('users.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        /** @var User|null $user */
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        /** @var LoginModel $loginModel */
        $loginModel = model(LoginModel::class);
        $logins     = $loginModel->where('identifier', $user->email)->orderBy('date', 'desc')->limit(20)->findAll();

        return $this->render($this->viewPrefix . 'security', [
            'user'   => $user,
            'logins' => $logins,
        ]);
    }

    /**
     * Displays basic security info, like previous login info,
     * and ability to force a password reset, ban, etc.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string|void
     */
    public function permissions(int $userId)
    {
        if (! auth()->user()->can('users.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        $user  = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        $permissions = setting('AuthGroups.permissions');
        if (is_array($permissions)) {
            ksort($permissions);
        }

        return $this->render($this->viewPrefix . 'permissions', [
            'user'        => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Updates the permissions for a single user.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function savePermissions(int $userId)
    {
        if (! auth()->user()->can('users.edit')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        /** @var User|null $user */
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        $user->syncPermissions(...($this->request->getPost('permissions') ?? []));

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['permissions']));
    }
}
