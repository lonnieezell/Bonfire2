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
use Bonfire\Groups\Libraries\GroupPermissions;
use Bonfire\Users\Libraries\AvatarStorage;
use Bonfire\Users\Libraries\SaveUser;
use Bonfire\Users\Libraries\UserAccess;
use Bonfire\Users\Models\UserFilter;
use Bonfire\Users\Models\UserModel;
use Bonfire\Users\User;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;
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
     * @return RedirectResponse|string
     */
    public function list()
    {
        if (! auth()->user()->can('users.list')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        /** @var UserFilter $userModel */
        $userModel = model(UserFilter::class);

        // The toolbar's search box and group chips sit beside the filter panel's filters.
        $filters = $this->request->getGet('filters') ?? [];
        $search  = trim((string) $this->request->getGet('search'));
        $role    = (string) $this->request->getGet('role');

        if ($search !== '') {
            $filters['search'] = $search;
        }
        if ($role !== '') {
            $filters['role'] = [$role];
        }

        $userModel->filter($filters)
            ->withPermissions()
            ->withIdentities()
            ->withGroups();

        $isHtmx = $this->request->hasHeader('HX-Request');

        return $this->render($this->viewPrefix . ($isHtmx ? '_table' : 'list'), [
            'headers' => [
                'user'        => lang('Users.headers.user'),
                'username'    => lang('Users.headers.username'),
                'groups'      => lang('Users.headers.groups'),
                'status'      => lang('Users.headers.status'),
                'last_active' => lang('Users.headers.last_active'),
            ],
            'users'       => $userModel->paginate(setting('Site.perPage')),
            'pager'       => $userModel->pager,
            'search'      => $search,
            'role'        => $role,
            'roles'       => $isHtmx ? [] : $userModel->getRoleFilters(),
            'groupCounts' => $isHtmx ? [] : $userModel->groupCounts(),
            'total'       => $isHtmx ? null : model(UserModel::class)->countAllResults(),
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

        helper('form');

        return $this->render($this->viewPrefix . 'form', [
            'groups'           => $groups,
            'permissionCounts' => GroupPermissions::counts(array_keys($groups)),
            'access'           => UserAccess::forCurrentUser(),
        ]);
    }

    /**
     * Display the Edit form for a single user.
     *
     * @return RedirectResponse|string
     */
    public function edit(int $userId)
    {
        $access = UserAccess::forCurrentUser();
        if (! $access->canOpenEditForm($userId)) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();

        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
        }

        $groups = setting('AuthGroups.groups');
        asort($groups);

        helper('form');

        return $this->render($this->viewPrefix . 'form', [
            'user'             => $user,
            'groups'           => $groups,
            'permissionCounts' => GroupPermissions::counts(array_keys($groups)),
            'access'           => $access,
        ]);
    }

    /**
     * Creates or saves the basic user details.
     *
     * @return RedirectResponse|void
     *
     * @throws ReflectionException
     */
    public function save(?int $userId = null)
    {
        $access = UserAccess::forCurrentUser();
        if (! $access->canSave($userId)) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();
        /** @var User */
        $user = $userId !== null
            ? $users->find($userId)
            : new User();

        /** @phpstan-ignore-next-line */
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
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

        $user = (new SaveUser($access, new AvatarStorage(), $users))
            ->handle($user, $this->request->getPost(), $this->request->getFile('avatar'));

        return redirect()->to($user->adminLink())->with('message', lang('Bonfire.resourceSaved', [lang('Users.user')]));
    }

    /**
     * Change user's password.
     *
     * @return RedirectResponse|void
     *
     * @throws ReflectionException
     */
    public function changePassword(?int $userId = null)
    {
        if (! UserAccess::forCurrentUser()->canManageSecurity($userId)) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();
        /** @var User */
        $user = $userId !== null
            ? $users->find($userId)
            : new User();

        /** @phpstan-ignore-next-line */
        if ($user === null) {
            return redirect()->back()->withInput()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
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

        return redirect()->to($user->adminLink('/security'))->with('message', lang('Bonfire.resourceSaved', [lang('Users.user')]));
    }

    /**
     * Displays basic security info, like previous login info,
     * and ability to force a password reset, ban, etc.
     *
     * @return RedirectResponse|string
     */
    public function security(int $userId)
    {
        if (! UserAccess::forCurrentUser()->canManageSecurity($userId)) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        /** @var User|null $user */
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
        }

        /** @var LoginModel $loginModel */
        $loginModel = model(LoginModel::class);
        $logins     = $loginModel->where('identifier', $user->email)->orderBy('date', 'desc')->findAll(20);

        return $this->render($this->viewPrefix . 'security', [
            'user'   => $user,
            'logins' => $logins,
        ]);
    }

    /**
     * Displays basic security info, like previous login info,
     * and ability to force a password reset, ban, etc.
     *
     * @return RedirectResponse|string
     */
    public function permissions(int $userId)
    {
        if (! auth()->user()->can('users.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        $user  = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
        }

        $permissions = setting('AuthGroups.permissions');
        if (is_array($permissions)) {
            ksort($permissions);
        }

        return $this->render($this->viewPrefix . 'permissions', [
            'user'        => $user,
            'permissions' => $permissions,
            'access'      => UserAccess::forCurrentUser(),
        ]);
    }

    /**
     * Updates the permissions for a single user.
     *
     * @return RedirectResponse
     */
    public function savePermissions(int $userId)
    {
        $access = UserAccess::forCurrentUser();
        if (! $access->canEditUsers()) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        /** @var User|null $user */
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
        }

        // Drop values that aren't defined permissions, like the "0" an older x-switch posts when off.
        $requested   = array_intersect((array) ($this->request->getPost('permissions') ?? []), array_keys(setting('AuthGroups.permissions') ?? []));
        $permissions = $access->allowedPermissions($user, array_values($requested));

        $user->syncPermissions(...$permissions);

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', [lang('Users.permissions')]));
    }

    /**
     * Downloads everything the site holds about the user as JSON.
     *
     * @return RedirectResponse|ResponseInterface
     */
    public function export(int $userId)
    {
        /** @var User|null $user */
        $user = model(UserModel::class)->withDeleted()->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
        }

        if (! UserAccess::forCurrentUser()->canExport($user)) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        log_message('notice', 'User {admin} exported the personal data of user {user}.', ['admin' => auth()->id(), 'user' => $user->id]);

        $json = json_encode(service('personalData')->export($user), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        return $this->response
            ->setContentType('application/json')
            ->setHeader('Content-Disposition', "attachment; filename=\"user-{$user->id}-data.json\"")
            ->setBody($json);
    }

    /**
     * Deletes user avatar on HTMX ajax request
     *
     * @return string
     */
    public function deleteAvatar(int $userId)
    {
        $users = new UserModel();
        /** @var User */
        $user = $users->find($userId);

        if (UserAccess::forCurrentUser()->canSave($userId)) {
            if ((new AvatarStorage())->delete($user->avatar)) {
                $user->avatar = null;
                $users->save($user);
            }

            return $this->render($this->viewPrefix . '_avatar', ['user' => $user]);
        }

        // TODO: will have to find a way to return error message via ajax fragment later
        return '';
    }
}
