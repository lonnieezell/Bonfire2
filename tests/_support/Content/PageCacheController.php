<?php

namespace Tests\Support\Content;

use Bonfire\Content\Entry;
use CodeIgniter\Controller;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * A developer's controller that answers with a 200, to show that a controller's response is cached.
 */
class PageCacheController extends Controller
{
    public static int $calls = 0;

    public function show(Entry $entry): ResponseInterface
    {
        self::$calls++;

        return $this->response->setJSON(['slug' => $entry->slug, 'call' => self::$calls]);
    }
}
