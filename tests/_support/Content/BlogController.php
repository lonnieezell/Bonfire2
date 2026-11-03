<?php

namespace Tests\Support\Content;

use Bonfire\Content\Entry;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A developer's controller that a collection hands off to.
 */
class BlogController extends Controller
{
    public static int $calls = 0;

    public function show(Entry $entry): ResponseInterface
    {
        self::$calls++;

        return $this->response->setStatusCode(201)->setJSON(['slug' => $entry->slug]);
    }
}
