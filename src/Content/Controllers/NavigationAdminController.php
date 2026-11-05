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
use CodeIgniter\I18n\Time;
use Psr\Log\LoggerInterface;

/**
 * The editor of a menu's tree. Menus are part of the structure of the content, so it needs content.structure.
 */
class NavigationAdminController extends AdminController
{
    use LoadsSortable;

    private const ENTRY_COLUMNS = 'id, title, uri, status, published_at, deleted_at';

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

    public function edit(string $handle): string
    {
        $navigation             = $this->navigation($handle);
        [$tree, $hash, $notice] = $this->readTree($navigation);

        return $this->renderEditor($navigation, $tree, $hash, $notice);
    }

    /**
     * Saves the posted tree, unless the tree file has changed since the editor was opened.
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
            return $this->renderEditor($navigation, $tree ?? $this->readTree($navigation)[0], $hash, lang('Content.structureConflict'), 409);
        } catch (ContentException $e) {
            return $this->renderEditor($navigation, $tree ?? $this->readTree($navigation)[0], $hash, $e->getMessage(), 422);
        }

        return redirect()->to(route_to('content-navigation', $navigation->handle))->with('message', lang('Bonfire.resourceSaved', [lang('Content.navigation')]));
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
        $raw = NavigationTree::raw(config('Content')->contentPath, $navigation->handle);

        try {
            return [NavigationTree::parse($raw), NavigationTree::hash($raw), null];
        } catch (ContentException $e) {
            return [new NavigationTree([]), NavigationTree::hash($raw), $e->getMessage()];
        }
    }

    private function renderEditor(Navigation $navigation, NavigationTree $tree, string $hash, ?string $notice, int $httpStatus = 200): string
    {
        $ids     = $tree->entryIds();
        $entries = $ids === [] ? [] : $this->withWhyHidden((new ContentEntryModel())->withDeleted()->select(self::ENTRY_COLUMNS)->whereIn('id', $ids)->findAll());

        $this->addSortableScript();

        $this->response->setStatusCode($httpStatus);

        return $this->render($this->viewPrefix . 'navigation_tree', [
            'navigation' => $navigation,
            'nodes'      => $tree->nodes,
            'entries'    => array_column($entries, null, 'id'),
            'picker'     => $this->picker($navigation),
            'hash'       => $hash,
            'notice'     => $notice,
        ]);
    }

    /**
     * What the menu can link to: the entries with a URI of each of its collections that has any.
     *
     * @return list<array{title: string, entries: list<array<string, mixed>>}>
     */
    private function picker(Navigation $navigation): array
    {
        $picker = [];

        foreach (Collection::all(config('Content')->contentPath) as $collection) {
            if (! in_array($collection->handle, $navigation->collections, true)) {
                continue;
            }

            $entries = (new ContentEntryModel())
                ->select(self::ENTRY_COLUMNS)
                ->where('collection', $collection->handle)
                ->where('uri IS NOT NULL')
                ->orderBy('title')
                ->findAll();

            if ($entries !== []) {
                $picker[] = ['title' => $collection->title, 'entries' => $this->withWhyHidden($entries)];
            }
        }

        return $picker;
    }

    /**
     * Each row with `hidden`: why navigation() would skip it, or null if it wouldn't.
     *
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function withWhyHidden(array $rows): array
    {
        $now = Time::now()->toDateTimeString();

        return array_map(static fn (array $row): array => $row + ['hidden' => match (true) {
            $row['deleted_at'] !== null                                  => lang('Content.navigationTrashed'),
            $row['status'] !== 'published'                               => lang('Content.statusDraft'),
            $row['published_at'] !== null && $row['published_at'] > $now => lang('Content.navigationScheduled'),
            $row['uri'] === null                                         => lang('Content.navigationNoUri'),
            default                                                      => null,
        }], $rows);
    }
}
