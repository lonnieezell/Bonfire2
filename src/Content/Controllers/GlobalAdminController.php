<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Controllers;

use Bonfire\Content\Blueprint;
use Bonfire\Content\ContentException;
use Bonfire\Content\EntryForm;
use Bonfire\Content\GlobalSet;
use Bonfire\Content\GlobalStore;
use Bonfire\Content\SaveConflictException;
use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * The form of a global set in the admin, built from the set's blueprint.
 */
class GlobalAdminController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Content\Views\\';

    /**
     * Turns away anyone without content.access and content.edit.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('content.access') || ! auth()->user()->can('content.edit')) {
            throw new RedirectException(redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized')));
        }
    }

    /**
     * The form, with what the file holds now.
     */
    public function edit(string $handle): string
    {
        [$set, $blueprint] = $this->load($handle);

        $form = new EntryForm($blueprint, false);

        return $this->renderForm($set, $blueprint, $form, ['values' => $form->valuesOf($set->data(), '', ''), 'hash' => $set->hash]);
    }

    /**
     * Saves the set, unless its file has changed since the form was opened.
     *
     * @return RedirectResponse|string
     */
    public function save(string $handle)
    {
        [$set, $blueprint] = $this->load($handle);

        $post   = $this->request->getPost();
        $form   = new EntryForm($blueprint, false);
        $values = $form->prepare($post);
        $errors = $form->validate($values);
        $hash   = is_string($post['hash'] ?? null) ? $post['hash'] : '';

        // What the form can't change is shown as it is.
        $shown = ['values' => $values + $form->valuesOf($set->data(), '', ''), 'hash' => $hash, 'errors' => $errors];

        if ($errors !== []) {
            return $this->renderForm($set, $blueprint, $form, $shown);
        }

        [$data] = $form->apply($values, $set->data(), '');

        try {
            (new GlobalStore(config('Content')->contentPath))->save($set, $data, $hash);
        } catch (SaveConflictException) {
            return $this->renderForm($set, $blueprint, $form, $shown + ['notice' => lang('Content.conflict'), 'conflict' => true], 409);
        } catch (ContentException $e) {
            return $this->renderForm($set, $blueprint, $form, $shown + ['notice' => $e->getMessage()]);
        }

        return redirect()->to(route_to('content-global', $set->handle))->with('message', lang('Bonfire.resourceSaved', [lang('Content.global')]));
    }

    /**
     * The set as its file holds it now, and its blueprint.
     *
     * @return array{GlobalSet, Blueprint}
     *
     * @throws PageNotFoundException when there's no such set, or nothing describes it, so there's no form to build
     */
    private function load(string $handle): array
    {
        try {
            $set = GlobalSet::load(config('Content')->contentPath, config('Content')->blueprintPath, $handle);
        } catch (ContentException) {
            throw PageNotFoundException::forPageNotFound();
        }

        if ($set->blueprint === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return [$set, $set->blueprint];
    }

    /**
     * @param array{values: array<string, string>, hash: string, errors?: array<string, string>, notice?: string, conflict?: bool} $shown
     */
    private function renderForm(GlobalSet $set, Blueprint $blueprint, EntryForm $form, array $shown, int $httpStatus = 200): string
    {
        $this->response->setStatusCode($httpStatus);

        return $this->render($this->viewPrefix . 'global_form', [
            'set'       => $set,
            'blueprint' => $blueprint,
            'rows'      => $form->rows($shown['values'], $shown['errors'] ?? []),
            'hash'      => $shown['hash'],
            'notice'    => $shown['notice'] ?? null,
            'conflict'  => $shown['conflict'] ?? false,
        ]);
    }
}
