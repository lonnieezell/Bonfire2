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
use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Content\Navigation;
use Bonfire\Content\NavigationStore;
use Bonfire\Content\NavigationTree;
use Bonfire\Content\SaveConflictException;
use Bonfire\Core\AdminController;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\Exceptions\RedirectException;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * The editor of a navigation menu. A menu is part of the structure of the content, so it needs content.structure.
 */
class NavigationAdminController extends AdminController
{
    protected $theme      = 'Admin';
    protected $viewPrefix = 'Bonfire\Content\Views\\';

    /**
     * Turns away anyone without content.structure.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        if (! auth()->user()->can('content.structure')) {
            throw new RedirectException(redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized')));
        }
    }

    /**
     * The editor of a menu.
     */
    public function edit(string $handle): string
    {
        $navigation             = $this->navigation($handle);
        [$tree, $hash, $notice] = $this->readTree($navigation);

        return $this->renderEditor($navigation, $tree, $hash, $notice);
    }

    /**
     * Saves the posted menu, unless the tree file has changed since the editor was opened.
     *
     * @return RedirectResponse|string
     */
    public function save(string $handle)
    {
        $navigation = $this->navigation($handle);
        $post       = $this->request->getPost();
        $hash       = is_string($post['hash'] ?? null) ? $post['hash'] : '';
        $tree       = null;

        try {
            $tree = NavigationTree::fromJson(is_string($post['tree'] ?? null) ? $post['tree'] : '');
            (new NavigationStore(config('Content')->contentPath))->save($navigation, $tree, $hash);
        } catch (SaveConflictException) {
            return $this->renderEditor($navigation, $tree ?? $this->readTree($navigation)[0], $hash, lang('Content.navigationConflict'), true, 409);
        } catch (ContentException $e) {
            return $this->renderEditor($navigation, $tree ?? $this->readTree($navigation)[0], $hash, $e->getMessage(), false, 422);
        }

        return redirect()->to(route_to('content-navigation', $navigation->handle))->with('message', lang('Bonfire.resourceSaved', [lang('Content.navigationMenu')]));
    }

    /**
     * @throws PageNotFoundException
     */
    private function navigation(string $handle): Navigation
    {
        try {
            return Navigation::load(config('Content')->contentPath, $handle);
        } catch (ContentException) {
            throw PageNotFoundException::forPageNotFound();
        }
    }

    /**
     * The tree file as it is now. One that can't be read shows as an empty menu, with the reason, and saving replaces it.
     *
     * @return array{NavigationTree, string, string|null} the tree, the hash of the file and what is wrong with it
     */
    private function readTree(Navigation $navigation): array
    {
        $raw  = $navigation->rawTree(config('Content')->contentPath);
        $hash = NavigationTree::hash($raw);

        try {
            return [NavigationTree::parse($raw), $hash, null];
        } catch (ContentException $e) {
            return [new NavigationTree([]), $hash, $e->getMessage()];
        }
    }

    private function renderEditor(Navigation $navigation, NavigationTree $tree, string $hash, ?string $notice, bool $conflict = false, int $httpStatus = 200): string
    {
        $handles = $navigation->collections ?: array_map(static fn (Collection $collection): string => $collection->handle, Collection::all(config('Content')->contentPath));
        $offered = $handles === [] ? [] : (new ContentEntryModel())->select('id, collection, title, status')->whereIn('collection', $handles)->orderBy('collection')->orderBy('title')->findAll();

        // An item for an entry that has gone, such as one in the trash, can't be edited or saved, so the editor drops it.
        $named = $tree->entryIds();
        $known = $named === [] ? [] : array_column((new ContentEntryModel())->select('id, title, status')->whereIn('id', $named)->findAll(), null, 'id');

        $this->response->setStatusCode($httpStatus);

        service('viewMeta')->addScript([
            'src'            => 'https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js',
            'integrity'      => 'sha256-bQqDH8GbS66FF5etM5MVfoYa+3hiRZwRImNZsn4sQzc=',
            'crossorigin'    => 'anonymous',
            'referrerpolicy' => 'no-referrer',
        ]);

        return $this->render($this->viewPrefix . 'navigation_editor', [
            'navigation' => $navigation,
            'nodes'      => $tree->onlyEntries(array_keys($known))->nodes,
            'known'      => $known,
            'offered'    => $offered,
            'hash'       => $hash,
            'notice'     => $notice,
            'conflict'   => $conflict,
        ]);
    }
}
