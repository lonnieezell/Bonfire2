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

use Bonfire\Actions\ResultFile;
use Bonfire\Actions\RunOutcome;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Answers a dispatched action run, for a controller that runs actions: back to the page with the queued run's
 * toast or the run's message, or the run's file as a download.
 */
trait RespondsToRuns
{
    protected function respondTo(RunOutcome $outcome): RedirectResponse|ResponseInterface
    {
        if ($outcome->runId !== null) {
            return $this->back('actionRun', $outcome->runId);
        }

        if ($outcome->file instanceof ResultFile) {
            return $this->response->download($outcome->file->name, $outcome->file->take());
        }

        return $this->back($outcome->succeeded ? 'message' : 'error', $outcome->message);
    }

    /**
     * Back to the page the admin came from with a message. The dialog posts with AJAX, so it is told to go there.
     */
    protected function back(string $key, int|string $value): RedirectResponse|ResponseInterface
    {
        if (! $this->request->isAJAX()) {
            return redirect()->back()->with($key, $value);
        }

        session()->setFlashdata($key, $value);

        return $this->response->setStatusCode(204)->setHeader('HX-Redirect', previous_url());
    }
}
