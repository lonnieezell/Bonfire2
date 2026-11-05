<?php

/**
 * This file is part of Bonfire.
 *
 * (c) Lonnie Ezell <lonnieje@gmail.com>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Bonfire\Content;

use Bonfire\Content\Models\ContentEntryModel;
use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

/**
 * Content Module setup
 */
class Module extends BaseModule
{
    /**
     * Adds a Content menu with an item for each collection, for each taxonomy and navigation menu to those who have content.structure,
     * and for each set of globals to those who have content.edit.
     * The sidebar shows it to those who have content.access.
     */
    public function initAdmin()
    {
        // Before the check below: the Recycler only asks for recycler.view.
        service('recycler')->register('entries', 'Content', ContentEntryModel::class, ['title', 'collection', 'slug']);

        // Reading every collection's config is for those who'll see the menu.
        $user = auth()->user();

        if (! $user?->can('content.access')) {
            return;
        }

        $collections = Collection::all(config('Content')->contentPath);
        $canArrange  = $user->can('content.structure');
        $taxonomies  = $canArrange ? Taxonomy::all(config('Content')->contentPath) : [];
        $navigations = $canArrange ? Navigation::all(config('Content')->contentPath) : [];

        // A set nothing describes has no form to open.
        $globals = $user->can('content.edit') ? array_filter(GlobalSet::all(config('Content')->contentPath, config('Content')->blueprintPath), static fn (GlobalSet $set): bool => $set->blueprint !== null) : [];

        if ($collections === [] && $taxonomies === [] && $navigations === [] && $globals === []) {
            return;
        }

        $menu = service('menus')->menu('sidebar')
            ->createCollection('entries', lang('Content.modTitle'))
            ->setFontAwesomeIcon('fas fa-newspaper')
            ->setCollapsible();

        foreach ($collections as $collection) {
            $menu->addItem(new MenuItem([
                'title'      => esc($collection->title),
                'url'        => route_to('content-list', $collection->handle),
                'permission' => 'content.access',
            ]));
        }

        foreach ($taxonomies as $taxonomy) {
            $menu->addItem(new MenuItem([
                'title'      => esc($taxonomy->title),
                'url'        => route_to('content-terms', $taxonomy->handle),
                'permission' => 'content.structure',
            ]));
        }

        foreach ($navigations as $navigation) {
            $menu->addItem(new MenuItem([
                'title'      => esc($navigation->title),
                'url'        => route_to('content-navigation', $navigation->handle),
                'permission' => 'content.structure',
            ]));
        }

        foreach ($globals as $set) {
            $menu->addItem(new MenuItem([
                'title'      => esc($set->blueprint->title),
                'url'        => route_to('content-global', $set->handle),
                'permission' => 'content.edit',
            ]));
        }
    }
}
