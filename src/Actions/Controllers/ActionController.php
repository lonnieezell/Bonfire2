<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Actions\Controllers;

use Bonfire\Actions\Action;
use Bonfire\Actions\Confirm;
use Bonfire\Core\AdminController;
use Bonfire\Fields\Form;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Runs every row and bulk action: checks the permission, asks for
 * confirmation and the action's input when it wants them, then runs the
 * action on the selected records or queues the run.
 */
class ActionController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Actions\Views\\';

    /**
     * Runs the action, or asks first. The dialog posts with AJAX, and is answered with the fields again (status 422)
     * when its input is invalid, and otherwise with the page to go to, which the flash message waits on.
     *
     * @return RedirectResponse|ResponseInterface|string
     */
    public function run(string $list, string $name)
    {
        $action = service('actions')->find($list, $name);

        if (! $action instanceof Action) {
            throw PageNotFoundException::forPageNotFound();
        }

        $user = auth()->user();

        if (! $this->mayRun($action)) {
            return $this->back('error', lang('Bonfire.notAuthorized'));
        }

        $ids = array_values(array_unique(array_filter(
            (array) $this->request->getPost('selects'),
            static fn ($id) => is_scalar($id) && $id !== '',
        )));

        if ($ids === []) {
            return $this->back('error', lang('Bonfire.resourcesNotSelected', [lang('Actions.records')]));
        }

        $confirm = $action->question();
        $form    = $action->form();
        $input   = $form->prepare($this->request->getPost());
        $errors  = $this->request->getPost('confirmed') === '1' ? $form->validate($input) : [];

        if ($confirm instanceof Confirm && ($errors !== [] || ! $this->confirmed($confirm, count($ids)))) {
            return $this->ask($action, $confirm, $form, $ids, $input, $errors);
        }

        $outcome = service('actionRuns')->dispatch($list, $action, $user, $ids, $input);

        if ($outcome->runId !== null) {
            return $this->back('actionRun', $outcome->runId);
        }

        return $this->back($outcome->succeeded ? 'message' : 'error', $outcome->message);
    }

    /**
     * The action's fields with nothing in them, for the confirm dialog to show. They don't hold any record.
     */
    public function fields(string $list, string $name): string
    {
        $action = service('actions')->find($list, $name);

        if (! $action instanceof Action || ! $this->mayRun($action)) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view($this->viewPrefix . '_fields', ['sections' => $action->form()->sections([], [])], ['saveData' => false]);
    }

    /**
     * A queued run's progress toast, which polls until the run ends. Opened
     * as a link rather than polled, it shows inside the admin page.
     */
    public function progress(int $id): string
    {
        $run = service('actionRuns')->progress($id, auth()->user());

        if ($run === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        if (! $this->request->hasHeader('HX-Request')) {
            return $this->render($this->viewPrefix . 'run', [
                'run'   => $run,
                'label' => service('actionRuns')->label($run),
            ]);
        }

        return view($this->viewPrefix . '_run', ['run' => $run]);
    }

    private function mayRun(Action $action): bool
    {
        return $action->permission === '' || auth()->user()->can($action->permission);
    }

    /**
     * Back to the page the admin came from with a message. The dialog posts with AJAX, so it is told to go there.
     */
    private function back(string $key, int|string $value): RedirectResponse|ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            return redirect()->back()->with($key, $value);
        }

        session()->setFlashdata($key, $value);

        return $this->response->setStatusCode(204)->setHeader('HX-Redirect', previous_url());
    }

    /**
     * The confirmation, with the action's fields: as a page, or as just the fields for the dialog.
     *
     * @param list<int|string>      $ids
     * @param array<string, string> $input
     * @param array<string, string> $errors
     */
    private function ask(Action $action, Confirm $confirm, Form $form, array $ids, array $input, array $errors): ResponseInterface|string
    {
        $sections = $action->hasFields() ? $form->sections($input, $errors) : [];

        if ($this->request->isAJAX()) {
            return $this->response->setStatusCode(422)
                ->setBody(view($this->viewPrefix . '_fields', ['sections' => $sections], ['saveData' => false]));
        }

        return $this->render($this->viewPrefix . 'confirm', [
            'action'   => $action,
            'confirm'  => $confirm,
            'ids'      => $ids,
            'sections' => $sections,
            'miscount' => $this->request->getPost('confirm_count') !== null,
        ]);
    }

    /**
     * Whether the admin has answered the confirmation, typing the record count if it asks for it.
     */
    private function confirmed(Confirm $confirm, int $count): bool
    {
        return $this->request->getPost('confirmed') === '1'
            && (! $confirm->asksToType() || $this->request->getPost('confirm_count') === (string) $count);
    }
}
