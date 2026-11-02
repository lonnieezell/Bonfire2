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
use Bonfire\Email\Models\EmailLogModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Tools -> Mail -> Outbox. Needs the email.log permission.
 */
class MailOutboxController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Email\Views\\';

    /**
     * Turns away anyone without the permission.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('email.log')) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * Lists the logged sends, newest first.
     */
    public function index(): string
    {
        $model   = new EmailLogModel();
        $filters = array_filter(
            array_intersect_key($this->request->getGet(), array_flip(['status', 'mailer', 'recipient', 'date_from', 'date_to'])),
            static fn (mixed $value): bool => is_string($value) && trim($value) !== '',
        );
        $rows = $model->filter($filters)->orderBy('created_at', 'desc')->orderBy('id', 'desc')->paginate(setting('Site.perPage'));

        // A link needs the permission, and a Mailable that still exists to open.
        $previews = [];

        if (auth()->user()->can('email.preview')) {
            $previews = (new MailablePreviews())->slugsFor(array_column($rows, 'mailable_class'));
        }

        return $this->render($this->viewPrefix . 'mail_outbox', [
            'rows'     => $rows,
            'pager'    => $model->pager,
            'previews' => $previews,
            'filters'  => $filters,
            'mailers'  => $model->mailers(),
        ]);
    }
}
