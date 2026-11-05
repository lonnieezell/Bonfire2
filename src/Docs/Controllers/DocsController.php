<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Docs\Controllers;

use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;

class DocsController extends AdminController
{
    protected $viewPrefix = 'Bonfire\Docs\Views\\';

    /**
     * Shows a page of the guide. A URI with several segments arrives as several arguments.
     */
    public function show(string ...$segments): string
    {
        $page = service('docs')->page(implode('/', $segments));

        if ($page === null) {
            throw PageNotFoundException::forPageNotFound();
        }

        return $this->render($this->viewPrefix . 'page', ['page' => $page]);
    }
}
