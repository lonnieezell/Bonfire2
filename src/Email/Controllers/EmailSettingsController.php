<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Email\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Email\MailerDashboard;

class EmailSettingsController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Email\Views\\';

    /**
     * Display the Email settings page.
     *
     * @return string
     */
    public function index()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $dashboard = new MailerDashboard();

        return $this->render($this->viewPrefix . 'email_settings', [
            'mailers'       => $dashboard->mailers(),
            'warnings'      => $dashboard->warnings(),
            'defaultMailer' => config('Mailer')->default,
            'address'       => auth()->user()->email,
            'testResult'    => session('testResult'),
        ]);
    }

    /**
     * Saves the From name and address through our dynamic configuration system.
     */
    public function save()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $rules = [
            'fromName'  => 'required|string|min_length[2]',
            'fromEmail' => 'required|valid_email',
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        setting('Email.fromName', $this->request->getPost('fromName'));
        setting('Email.fromEmail', $this->request->getPost('fromEmail'));

        alert('success', lang('Bonfire.settingsSaved'));

        return redirect()->back();
    }

    /**
     * Sends a fixed test message through the chosen mailer to the typed recipient.
     */
    public function testSend()
    {
        if (! auth()->user()->can('admin.settings')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $rules = [
            'mailer'    => 'required|string',
            'recipient' => 'required|valid_email',
        ];

        if (! $this->validate($rules)) {
            return redirect()->route('email-settings')->withInput()->with('errors', $this->validator->getErrors());
        }

        $mailer    = (string) $this->request->getPost('mailer');
        $recipient = (string) $this->request->getPost('recipient');
        $dashboard = new MailerDashboard();
        $result    = $dashboard->sendTest($mailer, $recipient);
        $outcome   = $result->success ? $dashboard->delivery($mailer) : 'failed';

        return redirect()->route('email-settings')->withInput()->with('testResult', [
            'outcome' => $outcome,
            'message' => match ($outcome) {
                'sent'      => lang('Email.testSent', [$recipient]),
                'discarded' => lang('Email.testDiscarded'),
                'logged'    => lang('Email.testLogged'),
                default     => $result->error ?? lang('Email.testNotSent'),
            },
        ]);
    }
}
