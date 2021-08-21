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
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('Auth.allowRegistration', $this->request->getPost('allowRegistration') == 1);
        setting('Auth.minimumPasswordLength', (int)$this->request->getPost('minimumPasswordLength'));
        setting('Auth.passwordValidators', $this->request->getPost('validators'));

        // Actions
        $actions = setting('Auth.actions');
        $actions['login'] = $this->request->getPost('email2FA');
        $actions['register'] = $this->request->getPost('emailActivation');
        setting('Auth.actions', $actions);

        // Remember Me
        $sessionConfig = setting('Auth.sessionConfig');
        $sessionConfig['allowRemembering'] = $this->request->getPost('allowRemember') == 1;
        $sessionConfig['rememberLength'] = $this->request->getPost('rememberLength');
        setting('Auth.sessionConfig', $sessionConfig);

        alert('success', 'The settings have been saved.');

        return redirect()->back();
	}
}
