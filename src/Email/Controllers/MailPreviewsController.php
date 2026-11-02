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
use Bonfire\Email\MailablePreviews;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Tools -> Mail -> Previews. Admin login and the email.preview permission protect
 * these pages, so unlike Postal's own preview route they work in production too.
 */
class MailPreviewsController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Email\Views\\';

    /**
     * Turns away anyone without the permission, whichever page they asked for.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('email.preview')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * Lists the Mailables by module.
     */
    public function index(): string
    {
        return $this->render($this->viewPrefix . 'mail_previews', [
            'groups' => (new MailablePreviews())->grouped(),
        ]);
    }

    /**
     * Shows one Mailable, and the form that sends it to the signed-in admin.
     */
    public function show(string $slug): string
    {
        $previews = new MailablePreviews();
        $class    = $previews->find($slug) ?? throw PageNotFoundException::forPageNotFound();
        $config   = config('Mailer');

        return $this->render($this->viewPrefix . 'mail_preview', [
            'slug'          => $slug,
            'preview'       => $previews->render($class),
            'mailers'       => array_keys($config->mailers),
            'defaultMailer' => $config->default,
            'address'       => auth()->user()->email,
        ]);
    }

    /**
     * Sends the Mailable's preview to the signed-in admin's own address. The
     * recipient never comes from the request.
     */
    public function send(string $slug): RedirectResponse
    {
        $previews = new MailablePreviews();
        $class    = $previews->find($slug) ?? throw PageNotFoundException::forPageNotFound();
        $address  = (string) auth()->user()->email;

        $result = $previews->send($class, $address, (string) $this->request->getPost('mailer'));

        // Alerts print their content as is.
        if ($result->success) {
            alert('success', esc(lang('Email.previewSent', [$address])));
        } else {
            alert('danger', esc($result->error ?? lang('Email.previewNotSent')));
        }

        return redirect()->route('mail-preview', [$slug]);
    }
}
