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
use Bonfire\Content\PreviewLink;
use Bonfire\Content\Taxonomy;
use Bonfire\Core\BaseController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;

/**
 * The fallback route: resolves a request to its published entry, then hands it to
 * the collection's template or controller. The signed draft preview ends the same way.
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

        if ($entry !== null && $collection !== null) {
            return $this->serve($entry, $collection);
        }

        throw PageNotFoundException::forPageNotFound();
    }

    /**
     * Shows an entry whatever its status, to anyone with a link that Bonfire signed for it, and that hasn't expired.
     * The response is marked noindex and is never cached.
     *
     * @return mixed whatever the collection's controller returns, or the rendered template
     */
    public function preview(string $id)
    {
        $valid      = PreviewLink::isValid($id, $this->request->getGet('expires'), $this->request->getGet('signature'), Time::now()->getTimestamp());
        $entry      = $valid ? content()->find($id) : null;
        $collection = $entry === null ? null : $this->collectionOf($entry);

        if ($entry === null || $collection === null || ! $collection->previewable()) {
            throw PageNotFoundException::forPageNotFound();
        }

        try {
            $result = $this->serve($entry, $collection);
        } finally {
            // A controller of the developer's may have asked for the page to be cached.
            service('responsecache')->setTtl(0);
        }

        // A controller's own response, or else the one CodeIgniter makes of a string. The Referrer-Policy keeps the
        // link, which is the only thing guarding the page, from going to the sites that the page links to or loads from.
        ($result instanceof ResponseInterface ? $result : $this->response)
            ->noCache()
            ->setHeader('X-Robots-Tag', 'noindex')
            ->setHeader('Referrer-Policy', 'no-referrer');

        return $result;
    }

    /**
     * Hands the entry to the collection's controller, or renders it with the collection's template.
     *
     * @return mixed
     *
     * @throws PageNotFoundException when the collection has neither
     */
    private function serve(Entry $entry, Collection $collection)
    {
        if ($collection->controller !== null) {
            return $this->handOff($collection->controller, $entry);
        }

        if ($collection->template !== null) {
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
