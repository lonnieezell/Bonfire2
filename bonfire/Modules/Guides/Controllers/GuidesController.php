<?php

namespace Bonfire\Guides\Controllers;

use App\Controllers\AdminController;
use Bonfire\Guides\Exceptions\GuideException;
use Bonfire\Modules\Guides\Libraries\GuideCollection;

class GuidesController extends AdminController
{
    protected $theme = 'Admin';

    protected $viewPrefix = 'Bonfire\Modules\Guides\Views\\';

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
        foreach(setting('Guides.collections') as $alias => $info) {
            if (isset($info['permission']) && ! $user->can($info['permission'])) {
                continue;
            }

            $collections[$alias] = $info;
        }

        if (count($collections) === 1) {
            return redirect()->route('view-guide', [key($collections)]);
        }

        return $this->render($this->viewPrefix .'list', [
            'collections' => $collections,
        ]);
    }

    /**
     * Displays the TOC for a single collection of guides.
     *
     * @param string $alias
     */
    public function viewCollection(string $alias)
    {
        try {
            $collection = $this->loadCollection($alias);

            return $this->render($this->viewPrefix .'index', [
                'collection' => $collection,
            ]);
        } catch(GuideException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Displays a single page of a TOC.
     *
     * @param string $collectionAlias
     * @param string $pageAlias
     */
    public function viewSingle(string $collectionAlias, string $pageAlias)
    {
        try {
            $collection = $this->loadCollection($collectionAlias);
            $page = $collection->loadPage($pageAlias);

            return $this->render($this->viewPrefix .'single', [
                'collection' => $collection,
                'page' => $page,
            ]);
        } catch(GuideException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Loads and validates the given collection.
     *
     * @param string $alias
     *
     * @return GuideCollection|\CodeIgniter\HTTP\RedirectResponse
     */
    protected function loadCollection(string $alias)
    {
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
