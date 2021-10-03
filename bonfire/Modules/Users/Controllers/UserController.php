<?php

namespace Bonfire\Modules\Users\Controllers;

use App\Controllers\AdminController;
use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DataException;
use Sparks\Shield\Models\LoginModel;
use Sparks\Shield\Models\UserIdentityModel;

class UserController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Users\Views\\';

    /**
     * Display the uses currently in the system.
     *
     * @return string
     */
	public function list()
	{
	    $userModel = model('UserFilter');

        $userModel->filter($this->request->getPost('filters'));

	    $view = $this->request->getMethod() == 'post'
            ? $this->viewPrefix .'_table'
            : $this->viewPrefix .'list';

		return $this->render($view, [
		    'headers' => [
		        'email' => 'Email',
                'username' => 'Username',
                'groups' => 'Groups',
                'last_active' => 'Last Active'
            ],
		    'showSelectAll' => true,
		    'users' => $userModel->paginate(setting('App.perPage')),
            'pager' => $userModel->pager,
        ]);
	}

    /**
     * Display the "new user" form.
     */
    public function create()
    {
        $groups = setting('AuthGroups.groups');
        asort($groups);

        return $this->render($this->viewPrefix .'form', [
            'groups' => $groups,
        ]);
	}

    /**
     * Display the Edit form for a single user.
     *
     * @param int $userId
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

        return $this->render($this->viewPrefix .'form', [
            'user' => $user,
            'groups' => $groups,
        ]);
    }

    /**
     * Creates or saves the basic user details.
     *
     * @param int|null $userId
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     * @throws \ReflectionException
     */
    public function save(int $userId = null)
    {
        if (! auth()->user()->can('users.edit')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = new UserModel();
        $user = $userId !== null
            ? $users->find($userId)
            : new User();

        if ($user === null) {
            return redirect()->back()->withInput()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        // Fill in basic details
        $user->fill($this->request->getPost());

        // Check for an avatar to upload
        if ($file = $this->request->getFile('avatar')) {
            if($file->isValid()) {
                $filename = $user->id .'_avatar.'. $file->getExtension();
                if($file->move(ROOTPATH .'public/uploads/avatars', $filename, true)) {
                    $user->avatar = $filename;
                }
            }
        }

        // Try saving basic details
        try {
            if (! $users->save($user)) {
                log_message($users->errors());

                return redirect()->back()->withInput()->with('error', lang('Bonfire.unknownSaveError', ['user']));
            }
        } catch(DataException $e) {
            // Just log the message for now since it's
            // likely saying the user's data is all the same
            log_message('debug', 'SAVING USER: '. $e->getMessage());
        }

        // We need an ID to on the entity to save groups.
        if ($user->id === null) {
            $user->id = $users->getInsertID();
        }

        // Save the new user's email/password
        $password = $this->request->getPost('password');
        $identity = $user->getEmailIdentity();
        if ($identity === null) {
            helper('text');
            $user->createEmailIdentity([
                'email' => $this->request->getPost('email'),
                'password' => ! empty($password) ? $password : random_string(12),
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
        $user->syncGroups($this->request->getPost('groups'));

        return redirect()->to($user->adminLink())->with('message', lang('Bonfire.resourceSaved', ['user']));
    }

    /**
     * Delete the specified user.
     *
     * @param int $userId
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function delete(int $userId)
    {
        if (! auth()->user()->can('users.delete')) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $users = model(UserModel::class);
        $user = $users->find($userId);

        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        if (! $users->delete($user->id)) {
            log_message('error', $users->error());
            return redirect()->back()->with('error', lang('Bonfire.unknownError'));
        }

        return redirect()->back()->with('message', lang('Bonfire.resourceDeleted', ['user']));
    }

    /**
     * Displays basic security info, like previous login info,
     * and ability to force a password reset, ban, etc.
     *
     * @param int $userId
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|void
     */
    public function security(int $userId)
    {
        $users = model(UserModel::class);
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        $loginModel = model(LoginModel::class);
        $logins = $loginModel->where('email', $user->email)->orderBy('date', 'desc')->limit(20)->findAll();

        return $this->render($this->viewPrefix .'security', [
            'user' => $user,
            'logins' => $logins,
        ]);
    }

    /**
     * Displays basic security info, like previous login info,
     * and ability to force a password reset, ban, etc.
     *
     * @param int $userId
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|string|void
     */
    public function permissions(int $userId)
    {
        $users = model(UserModel::class);
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        $permissions = setting('AuthGroups.permissions');
        if(is_array($permissions)) {
            ksort($permissions);
        }

        return $this->render($this->viewPrefix .'permissions', [
            'user' => $user,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Updates the permissions for a single user.
     *
     * @param int $userId
     *
     * @return \CodeIgniter\HTTP\RedirectResponse
     */
    public function savePermissions(int $userId)
    {
        $users = model(UserModel::class);
        $user = $users->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        $user->syncPermissions($this->request->getPost('permissions') ?? []);

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['permissions']));
    }
}
