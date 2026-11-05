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

    /**
     * Whether to ask for the page to be cached.
     */
    public static bool $cache = false;

    public function show(Entry $entry): ResponseInterface
    {
        self::$calls++;

        if (self::$cache) {
            $this->cachePage(60);
        }

        return $this->response->setStatusCode(201)->setJSON(['slug' => $entry->slug]);
    }
}
