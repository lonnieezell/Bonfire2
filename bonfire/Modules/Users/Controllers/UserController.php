<?php

namespace Bonfire\Modules\Users\Controllers;

use App\Controllers\AdminController;
use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\Database\Exceptions\DataException;
use Sparks\Shield\Models\LoginModel;

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
                'last_login' => 'Last Login'
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
        return $this->render($this->viewPrefix .'form');
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
        $users = new UserModel();
        $user = $userId !== null
            ? $users->find($userId)
            : new User();

        if ($user === null) {
            return redirect()->back()->withInput()->with('error', lang('Bonfire.resourceNotFound', ['user']));
        }

        // Save basic details
        $user->fill($this->request->getPost());

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

        // Save the user's groups
        $user->syncGroups($this->request->getPost('groups'));

        return redirect()->back()->with('message', lang('Bonfire.resourceSaved', ['user']));
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
}
