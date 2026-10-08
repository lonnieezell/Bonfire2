<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Recycler\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Recycler\Libraries\RecyclableResource;
use CodeIgniter\HTTP\RedirectResponse;

class RecycleController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Recycler\Views\\';

    /**
     * Displays the deleted items for a single resource.
     *
     * @return RedirectResponse|string
     */
    public function viewResource()
    {
        $resource = $this->authorizedResource($this->request->getVar('r'));

        if ($resource instanceof RedirectResponse) {
            return $resource;
        }

        $deleted = $resource->deleted((int) setting('Site.perPage'));

        return $this->render($this->viewPrefix . 'listResource', [
            'resources'       => service('recycler')->resources(),
            'currentResource' => $resource,
            'items'           => $deleted['items'],
            'pager'           => $deleted['pager'],
        ]);
    }

    /**
     * Restores a single record.
     *
     * @return RedirectResponse
     */
    public function restore(string $resourceType, int $resourceId)
    {
        $resource = $this->authorizedResource($resourceType);

        if ($resource instanceof RedirectResponse) {
            return $resource;
        }

        if (! $resource->restore($resourceId)) {
            return $this->failed($resource);
        }

        return redirect()->back()->with('message', lang('Bonfire.resourceRestored', [$resourceType]));
    }

    /**
     * Purges a single record.
     *
     * @return RedirectResponse
     */
    public function purge(string $resourceType, int $resourceId)
    {
        $resource = $this->authorizedResource($resourceType);

        if ($resource instanceof RedirectResponse) {
            return $resource;
        }

        if (! $resource->purge($resourceId)) {
            return $this->failed($resource);
        }

        return redirect()->back()->with('message', lang('Bonfire.resourcesDeleted', [$resourceType]));
    }

    /**
     * The resource to act on, or the redirect to send when the user
     * may not use the Recycler or the resource does not exist.
     */
    private function authorizedResource(?string $alias): RecyclableResource|RedirectResponse
    {
        if (! auth()->user()->can('recycler.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        return service('recycler')->find($alias)
            ?? redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]));
    }

    private function failed(RecyclableResource $resource): RedirectResponse
    {
        return redirect()->back()->with(
            'error',
            $resource->errors() ?: lang('Bonfire.resourceNotFound', [$resource->label()]),
        );
    }
}
