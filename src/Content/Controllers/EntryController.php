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

use Bonfire\Content\Collection;
use Bonfire\Content\ContentException;
use Bonfire\Content\Entry;
use Bonfire\Content\Taxonomy;
use Bonfire\Core\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;

/**
 * The fallback route: resolves a request to its published entry, then hands it to
 * the collection's template or controller.
 */
class EntryController extends BaseController
{
    /**
     * @return mixed whatever the collection's controller returns, or the rendered template
     */
    public function show()
    {
        // Routed for any verb to come after every other route, but only GET is served.
        $uri        = $this->request->getMethod() === 'GET' ? rawurldecode($this->request->getPath()) : null;
        $entry      = $uri === null ? null : content()->findByUri($uri);
        $collection = $entry === null ? null : $this->collectionOf($entry);

        if ($uri !== null && $entry === null && ($page = $this->termPage($uri)) !== null) {
            return $page;
        }

        if ($uri !== null && $entry === null && ($target = content()->redirectFor($uri)) !== null) {
            return redirect()->to($target, 301);
        }

        if ($collection?->controller !== null) {
            return $this->handOff($collection->controller, $entry);
        }

        if ($collection?->template !== null) {
            return render($this->theme, $collection->template, ['entry' => $entry, 'collection' => $collection]);
        }

        throw PageNotFoundException::forPageNotFound();
    }

    /**
     * The page of the term at this URI, if a taxonomy with a route and a template has one.
     * The template gets the `term`, its `taxonomy` and its published `entries`.
     */
    private function termPage(string $uri): ?string
    {
        foreach (Taxonomy::all(config('Content')->contentPath) as $taxonomy) {
            $slug = $taxonomy->template === null ? null : $taxonomy->slugAt($uri);
            $term = $slug === null ? null : content()->term($taxonomy->handle, $slug);

            if ($term !== null) {
                return render($this->theme, (string) $taxonomy->template, ['term' => $term, 'taxonomy' => $taxonomy, 'entries' => $term->entries()]);
            }
        }

        return null;
    }

    /**
     * The index keeps an entry until the next reindex, and its collection's config may be gone or broken by then.
     */
    private function collectionOf(Entry $entry): ?Collection
    {
        try {
            return Collection::load(config('Content')->contentPath, $entry->collection);
        } catch (ContentException $e) {
            log_message('error', "Content: collection {$entry->collection}: {$e->getMessage()}");

            return null;
        }
    }

    /**
     * Starts the developer's controller the way CodeIgniter would have.
     *
     * @param string $controller e.g. App\Controllers\Blog::show
     *
     * @return mixed
     */
    private function handOff(string $controller, Entry $entry)
    {
        [$class, $method] = explode('::', $controller);

        $instance = new $class();
        $instance->initController($this->request, $this->response, $this->logger);

        return $instance->{$method}($entry);
    }
}
