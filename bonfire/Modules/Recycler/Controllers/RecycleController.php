<?php

namespace Bonfire\Recycler\Controllers;

use App\Controllers\AdminController;
use CodeIgniter\HTTP\RedirectResponse;

class RecycleController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Recycler\Views\\';

    /**
     * Displays the deleted items for a single resource.
     *
     * @return string|RedirectResponse
     */
    public function viewResource()
    {
        $resources = setting('Recycler.resources');

        $resourceType = $this->request->getVar('r')
            ? $this->request->getVar('r') ?? null
            : setting('Recycler.defaultResource');

        if (empty($resourceType) || ! array_key_exists($resourceType, $resources)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['resource type']));
        }

        $currentResource = $resources[$resourceType];

        $model = model($currentResource['model']);

        // Any special setup for this model?
        if (method_exists($model, 'setupRecycler')) {
            $model = $model->setupRecycler();
        }

        $items = $model
            ->asArray()
            ->onlyDeleted()
            ->orderBy('deleted_at', 'desc')
            ->paginate(setting('App.perPage'));

        return $this->render($this->viewPrefix .'listResource', [
            'resources' => $resources,
            'currentResource'=> $currentResource,
            'currentAlias' => $resourceType,
            'items' => $items,
            'pager' => $model->pager,
        ]);
    }

    /**
     * Restores a single record.
     *
     * @param string $resourceType
     * @param int    $resourceId
     *
     * @return RedirectResponse
     * @throws \ReflectionException
     */
    public function restore(string $resourceType, int $resourceId)
    {
        $resources = setting('Recycler.resources');

        if (! array_key_exists($resourceType, $resources)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['resource type']));
        }

        $currentResource = $resources[$resourceType];
        $model = model($currentResource['model']);

        // Is there custom handling?
        $result = method_exists($model, 'recyclerRestore')
            ? $model->recyclerRestore($resourceId)
            : $model->where('id', $resourceId)
                ->set(['deleted_at' => null])
                ->update();

        if (! $result) {
            return redirect()->back()->with('error', $model->errors());
        }

        return redirect()->back()->with('message', lang('Bonfire.resourceRestored', [$resourceType]));
    }

    /**
     * Purges a single record.
     *
     * @param string $resourceType
     * @param int    $resourceId
     *
     * @return RedirectResponse
     */
    public function purge(string $resourceType, int $resourceId)
    {
        $resources = setting('Recycler.resources');

        if (! array_key_exists($resourceType, $resources)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', ['resource type']));
        }

        $currentResource = $resources[$resourceType];
        $model = model($currentResource['model']);

        // Is there custom handling?
        $result = method_exists($model, 'recyclerPurge')
            ? $model->recyclerPurge($resourceId)
            : $model->delete($resourceId, true);

        if (! $result) {
            return redirect()->back()->with('error', $model->errors());
        }

        return redirect()->back()->with('message', lang('Bonfire.resourcesDeleted', [$resourceType]));
    }
}
