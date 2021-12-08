<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Guides\Controllers;

use App\Controllers\AdminController;
use Bonfire\Guides\Exceptions\GuideException;
use Bonfire\Modules\Guides\Libraries\GuideCollection;

class GuidesController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Modules\Guides\Views\\';
    protected $helpers    = ['toc'];

    /**
     * Displays the available collections of documentation
     * that the current user has access to.
     *
     * If only one collection is available, will redirect
     * the user to that one.
     */
    public function viewCollections()
    {
        $user = auth()->user();

        $collections = [];

        foreach (setting('Guides.collections') as $alias => $info) {
            if (isset($info['permission']) && ! $user->can($info['permission'])) {
                continue;
            }

            $collections[$alias] = $info;
        }

        if (count($collections) === 1) {
            return redirect()->route('view-guide', [key($collections)]);
        }

        return $this->render($this->viewPrefix . 'list', [
            'collections' => $collections,
        ]);
    }

    /**
     * Displays the TOC for a single collection of guides.
     */
    public function viewCollection(string $alias)
    {
        try {
            $collection = $this->loadCollection($alias);

            return $this->render($this->viewPrefix . 'index', [
                'collection' => $collection,
            ]);
        } catch (GuideException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Displays a single page of a TOC.
     */
    public function viewSingle(string $collectionAlias, string $pageAlias)
    {
        /** check if $collectionAlias contain "-"
         * if true split to retrieve the subfolder
         * if false, not contain subfolders
         */
        $folders = explode('-', $collectionAlias);
        $is_file = count($folders) === 1;
        if (! $is_file) {
            $alias = '';

            for ($x = 1; $x < count($folders); $x++) {
                $alias .= $folders[$x] . '/';
            }
            $pageAlias = $alias . $pageAlias;
        }

        try {
            $collection = $this->loadCollection($collectionAlias);
            $page       = $collection->loadPage($pageAlias);

            return $this->render($this->viewPrefix . 'single', [
                'collection' => $collection,
                'page'       => $page,
            ]);
        } catch (GuideException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Loads and validates the given collection.
     *
     * @return \CodeIgniter\HTTP\RedirectResponse|GuideCollection
     */
    protected function loadCollection(string $alias)
    {
        //If route contain "-"
        $alias = explode('-', $alias)[0];

        $settings = config('Guides')->collections;

        if (! isset($settings[$alias])) {
            throw GuideException::forCollectionNotFound();
        }

        $settings = $settings[$alias];

        $user = auth()->user();

        if (isset($settings['permission']) && ! $user->can($settings['permission'])) {
            throw GuideException::forNotAuthorized();
        }

        $collection = new GuideCollection($alias, $settings);

        if (! $collection->isValid()) {
            throw GuideException::forInvalidCollection();
        }

        return $collection;
    }
}
