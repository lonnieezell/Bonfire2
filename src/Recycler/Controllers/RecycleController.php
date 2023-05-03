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
use CodeIgniter\HTTP\RedirectResponse;
use ReflectionException;

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
        if (! auth()->user()->can('recycler.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $resources    = setting('Recycler.resources');
        $resourceType = $this->request->getVar('r') ?: setting('Recycler.defaultResource');

        if (empty($resourceType) || ! array_key_exists($resourceType, $resources)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]));
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
            ->paginate(setting('Site.perPage'));



        return $this->render($this->viewPrefix . 'listResource', [
            'resources'       => $resources,
            'currentResource' => $this->localizeResource($currentResource),
            'currentAlias'    => $resourceType,
            'items'           => $items,
            'pager'           => $model->pager,
        ]);
    }

    /**
     * Restores a single record.
     *
     * @return RedirectResponse
     *
     * @throws ReflectionException
     */
    public function restore(string $resourceType, int $resourceId)
    {
        if (! auth()->user()->can('recycler.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $resources = setting('Recycler.resources');

        if (! array_key_exists($resourceType, $resources)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]));
        }
        $currentResource = $resources[$resourceType];
        $model           = model($currentResource['model']);

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
     * @return RedirectResponse
     */
    public function purge(string $resourceType, int $resourceId)
    {
        if (! auth()->user()->can('recycler.view')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $resources = setting('Recycler.resources');

        if (! array_key_exists($resourceType, $resources)) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Recycler.resourceType')]));
        }

        $currentResource = $resources[$resourceType];
        $model           = model($currentResource['model']);

        // Is there custom handling?
        $result = method_exists($model, 'recyclerPurge')
            ? $model->recyclerPurge($resourceId)
            : $model->delete($resourceId, true);

        if (! $result) {
            return redirect()->back()->with('error', $model->errors());
        }

        return redirect()->back()->with('message', lang('Bonfire.resourcesDeleted', [$resourceType]));
    }

    /**
     * Checks if there is localization available for resource label and columns
     * and uses them if it finds the strings defined in localization files
     */
    protected function localizeResource(array $resource): array
    {
        foreach ($resource['columns'] as $colKey => $colName) {
            $key = $resource['label'] . '.recycler.columns.' . $colName;
            $value = lang($key);
            $resource['localizedColumns'][$colKey] = $key == $value ? $resource['columns'][$colKey] : $value;
        }
        
        $key = $resource['label'] . '.recycler.label';
        $value = lang($key);
        $resource['label'] = $key == $value ? $resource['label'] : $value;
        
        return $resource;
    }
}
