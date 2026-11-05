<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content\Controllers;

use Bonfire\Content\ContentException;
use Bonfire\Core\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * The optional /feed/{collection} route.
 */
class FeedController extends BaseController
{
    public function show(string $handle): ResponseInterface
    {
        try {
            return content()->feed($handle);
        } catch (ContentException $e) {
            throw PageNotFoundException::forPageNotFound($e->getMessage());
        }
    }
}
