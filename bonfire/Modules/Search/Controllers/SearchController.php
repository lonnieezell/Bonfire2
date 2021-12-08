<?php

/**
 * This file is part of CodeIgniter 4 framework.
 *
 * (c) CodeIgniter Foundation <admin@codeigniter.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Modules\Search\Controllers;

use App\Controllers\AdminController;
use Bonfire\Search\SearchEngine;

class SearchController extends AdminController
{
    protected $theme      = 'Admin';
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
            'results'    => $this->engine->overview($this->request->getPost()),
            'searchTerm' => $this->request->getPost('search_term'),
        ]);
    }
}
