<?php

namespace Bonfire\Modules\Email\Controllers;

use App\Controllers\BaseController;

class EmailSettingsController extends BaseController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Email\Views\\';

    /**
     * Display the Email settings page.
     *
     * @return string
     */
	public function index()
	{
	    $tabs = [
            'mail' => 1,
            'sendmail' => 2,
            'smtp' => 3
        ];

		return $this->render($this->viewPrefix .'email_settings', [
            'config' => config('Email'),
            'activeTab' => $tabs[setting('Email.protocol') ?? 'smtp'],
        ]);
	}

    /**
     * Saves the email settings to the config file, where it
     * is automatically saved by our dynamic configuration system.
     */
    public function save()
    {
        $rules = [
            'fromName' => 'required|string|min_length[2]',
            'fromEmail' => 'required|valid_email',
            'protocol' => 'required|in_list[mail,sendmail,smtp]',
            'mailPath' => 'permit_empty|string',
            'SMTPHost' => 'permit_empty|string',
            'SMTPPort' => 'permit_empty|in_list[25,587,465,2525,other]',
            'SMTPPortOther' => 'permit_empty|string',
            'SMTPUser' => 'permit_empty|string',
            'SMTPPass' => 'permit_empty|string',
            'SMTPCrypto' => 'permit_empty|in_list[ssl,tls]',
            'SMTPTimeout' => 'permit_empty|integer|greater_than_equal_to[0]',
            'SMTPKeepAlive' => 'permit_empty|in_list[0,1]',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $port = $this->request->getPost('SMTPPort') === 'other'
            ? $this->request->getPost('SMTPPortOther')
            : $this->request->getPost('SMTPPort');

        setting('Email.fromName', $this->request->getPost('fromName'));
        setting('Email.fromEmail', $this->request->getPost('fromEmail'));
        setting('Email.protocol', $this->request->getPost('protocol'));
        setting('Email.mailPath', $this->request->getPost('mailPath'));
        setting('Email.SMTPHost', $this->request->getPost('SMTPHost'));
        setting('Email.SMTPPort', $port);
        setting('Email.SMTPUser', $this->request->getPost('SMTPUser'));
        setting('Email.SMTPPass', $this->request->getPost('SMTPPass'));
        setting('Email.SMTPCrypto', $this->request->getPost('SMTPCrypto'));
        setting('Email.SMTPTimeout', $this->request->getPost('SMTPTimeout'));
        setting('Email.SMTPKeepAlive', $this->request->getPost('SMTPKeepAlive'));

        alert('success', 'The settings have been saved.');

        return redirect()->back();
	}
}
