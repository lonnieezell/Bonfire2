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

    /**
     * Shows the entry's blocks, to cache a page that embeds a fragment.
     */
    public function blocks(Entry $entry): string
    {
        return $entry->renderBlocks('sections');
    }

    /**
     * Refuses with a status and a string, the way a controller that checks access might.
     */
    public function refuse(Entry $entry): string
    {
        self::$calls++;

        $this->response->setStatusCode(403);

        return 'Not for you: ' . $entry->slug;
    }
}
