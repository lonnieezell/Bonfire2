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
use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RedirectResponse;

/**
 * Runs every row and bulk action: checks the permission, asks for
 * confirmation when the action wants it, then runs the action on the
 * selected records or queues the run.
 */
class ActionController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Actions\Views\\';

    /**
     * @return RedirectResponse|string
     */
    public function run(string $list, string $name)
    {
        $action = service('actions')->find($list, $name);

        if (! $action instanceof Action) {
            throw PageNotFoundException::forPageNotFound();
        }

        $user = auth()->user();

        if ($action->permission !== '' && ! $user->can($action->permission)) {
            return redirect()->back()->with('error', lang('Bonfire.notAuthorized'));
        }

        $ids = array_values(array_unique(array_filter(
            (array) $this->request->getPost('selects'),
            static fn ($id) => is_scalar($id) && $id !== '',
        )));

        if ($ids === []) {
            return redirect()->back()->with('error', lang('Bonfire.resourcesNotSelected', [lang('Actions.records')]));
        }

        if (! $this->confirmed($action, count($ids))) {
            return $this->render($this->viewPrefix . 'confirm', [
                'action'   => $action,
                'confirm'  => $action->confirm(),
                'ids'      => $ids,
                'miscount' => $this->request->getPost('confirm_count') !== null,
            ]);
        }

        $outcome = service('actionRuns')->dispatch($list, $action, $user, $ids);

        if ($outcome->runId !== null) {
            return redirect()->back()->with('actionRun', $outcome->runId);
        }

        return redirect()->back()->with($outcome->succeeded ? 'message' : 'error', $outcome->message);
    }

    /**
     * A queued run's progress toast, which polls until the run ends.
     */
    public function progress(int $id): string
    {
        $run = service('actionRuns')->progress($id, auth()->user());

        if ($run === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return view($this->viewPrefix . '_run', ['run' => $run]);
    }

    /**
     * Whether the admin has answered the action's confirmation, typing the record count if it asks for it.
     */
    private function confirmed(Action $action, int $count): bool
    {
        $confirm = $action->confirm();

        if ($confirm === null) {
            return true;
        }

        return $this->request->getPost('confirmed') === '1'
            && (! $confirm->asksToType() || $this->request->getPost('confirm_count') === (string) $count);
    }
}
