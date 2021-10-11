<?php

namespace Bonfire\Modules\Search\Controllers;

use App\Controllers\AdminController;
use Bonfire\Search\SearchEngine;

class SearchController extends AdminController
{
    protected $theme = 'Admin';
    protected $viewPrefix = 'Bonfire\Search\Views\\';

    /**
     * @var SearchEngine
     */
    protected $engine;

    public function __construct()
    {
        $this->engine = new SearchEngine();
    }

    /**
     * Displays the search results.
     */
    public function overview()
    {
        return $this->render('Search/index', [
            'results' => $this->engine->overview($this->request->getPost()),
            'searchTerm' => $this->request->getPost('search_term'),
        ]);
    }
}
