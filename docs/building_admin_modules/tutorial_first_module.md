# Tutorial: Build Your First Admin Module

In this tutorial you will build a Blog module that adds a **Posts** screen to the admin area. By the end you will
have:

- a "Posts" item in the sidebar, shown only to groups that may use it
- a paginated list of posts built from the admin theme's components
- a search box and a status filter that update the list without reloading the page
- posts in the admin's global search results
- deleted posts in the Recycler, ready to be restored

Every step ends with something to check in your browser before you move on.

## Before you start

You need:

- a CodeIgniter 4 app with Bonfire installed (see [Installation](../intro/installation.md))
- the app running, for example with `php spark serve`, and a superadmin account you can log in with
- some familiarity with CodeIgniter routes, controllers, models and migrations

All paths below are relative to your project root.

## Step 1: Turn on module discovery

Bonfire only finds modules in folders listed in `Config\Bonfire::$appModules`. The entry for `app/Modules` ships
commented out. Open `app/Config/Bonfire.php` and uncomment it:

```php
public $appModules = [
    'App\Modules' => APPPATH . 'Modules',
];
```

Now create the folders for the Blog module:

```
app/
    Modules/
        Blog/
            Config/
            Controllers/
            Database/
                Migrations/
                Seeds/
            Models/
            Views/
```

Every class in the module lives in the `App\Modules\Blog` namespace.

**Check it:** nothing changes in the browser yet. Bonfire only loads a module once it has a `Module.php`, which you
will add in step 3. Bonfire caches the module folders it finds for a minute, so if Blog doesn't show up then, run
`php spark cache:clear`.

## Step 2: Create the posts table

The list needs data. Create a migration at
`app/Modules/Blog/Database/Migrations/2026-10-29-120000_CreatePostsTable.php`:

```php
<?php

namespace App\Modules\Blog\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePostsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'         => ['type' => 'int', 'unsigned' => true, 'auto_increment' => true],
            'title'      => ['type' => 'varchar', 'constraint' => 200],
            'status'     => ['type' => 'varchar', 'constraint' => 20, 'default' => 'draft'],
            'created_at' => ['type' => 'datetime', 'null' => true],
            'updated_at' => ['type' => 'datetime', 'null' => true],
            'deleted_at' => ['type' => 'datetime', 'null' => true],
        ]);
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('posts');
    }

    public function down()
    {
        $this->forge->dropTable('posts');
    }
}
```

The `deleted_at` column lets the model soft-delete posts, which the Recycler needs in step 9.

Then a seeder at `app/Modules/Blog/Database/Seeds/PostSeeder.php` that adds a few posts. The last one is already
deleted, so you will have something to find in the Recycler later:

```php
<?php

namespace App\Modules\Blog\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class PostSeeder extends Seeder
{
    public function run()
    {
        $now   = Time::now()->toDateTimeString();
        $posts = [
            ['Hello, world', 'published'],
            ['Why we moved to CodeIgniter 4', 'published'],
            ['Ten tips for faster pages', 'published'],
            ['Our roadmap for next year', 'draft'],
            ['Behind the scenes', 'draft'],
            ['Meet the team', 'published'],
            ['A post nobody needs', 'draft', $now],
        ];

        foreach ($posts as $post) {
            $this->db->table('posts')->insert([
                'title'      => $post[0],
                'status'     => $post[1],
                'created_at' => $now,
                'updated_at' => $now,
                'deleted_at' => $post[2] ?? null,
            ]);
        }
    }
}
```

Run both:

```
php spark migrate --all
php spark db:seed "App\Modules\Blog\Database\Seeds\PostSeeder"
```

**Check it:** `php spark db:table posts` shows seven posts, the last with a `deleted_at` date.

## Step 3: Add a menu item

A module joins the admin through its `Module.php`. Bonfire calls its `initAdmin()` method on every admin page, and
that is where you add the module's menu items. Create `app/Modules/Blog/Module.php`:

```php
<?php

namespace App\Modules\Blog;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

class Module extends BaseModule
{
    public function initAdmin()
    {
        $item = new MenuItem([
            'title'           => 'Posts',
            'namedRoute'      => 'posts-list',
            'fontAwesomeIcon' => 'fas fa-newspaper',
        ]);
        service('menus')->menu('sidebar')->collection('content')->addItem($item);
    }
}
```

The item goes in the sidebar's `content` collection, next to Users. A menu item links to a named route, so define
`posts-list` in `app/Modules/Blog/Config/Routes.php`:

```php
<?php

$routes->group(ADMIN_AREA, ['namespace' => 'App\Modules\Blog\Controllers'], static function ($routes) {
    $routes->get('posts', 'PostController::list', ['as' => 'posts-list']);
});
```

`ADMIN_AREA` is the admin's URL prefix, `admin` by default, so the list lives at `/admin/posts`.

**Check it:** reload the admin. **Posts** appears in the sidebar, and in the ⌘K / Ctrl+K command palette. Clicking it
shows a 404 page saying the controller is not found. You'll write it next.

## Step 4: Show a page

Admin controllers extend `Bonfire\Core\AdminController`, which renders views inside the Admin theme. Create
`app/Modules/Blog/Controllers/PostController.php`:

```php
<?php

namespace App\Modules\Blog\Controllers;

use Bonfire\Core\AdminController;

class PostController extends AdminController
{
    public function list()
    {
        return $this->render('App\Modules\Blog\Views\list');
    }
}
```

Create the view at `app/Modules/Blog/Views/list.php`. It extends the theme's `master` layout and fills its `main`
section, using two [view components](view_components.md): `<x-page-head>` for the page title and `<x-admin-box>` for
a card to hold the content.

```php
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="Posts" />

<x-admin-box>
    <p>Your posts will be listed here.</p>
</x-admin-box>
<?php $this->endSection() ?>
```

**Check it:** click **Posts** in the sidebar. You get a page titled "Posts" inside the admin layout, and the menu
item is marked as the current page.

## Step 5: Protect it with a permission

Right now anyone who can reach the admin can open this page. Bonfire uses [Shield](https://shield.codeigniter.com/)
permissions to control access. You'll require a `posts.list` permission for the page.

First, require it. Add a `permission` to the menu item in `Module.php`, so users without it don't see the link:

```php hl_lines="5"
$item = new MenuItem([
    'title'           => 'Posts',
    'namedRoute'      => 'posts-list',
    'fontAwesomeIcon' => 'fas fa-newspaper',
    'permission'      => 'posts.list',
]);
```

Hiding the link doesn't stop anyone typing the URL, so check the permission in the controller too:

```php hl_lines="3-5"
public function list()
{
    if (! auth()->user()->can('posts.list')) {
        return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
    }

    return $this->render('App\Modules\Blog\Views\list');
}
```

Reload the admin. **Posts** has disappeared from the sidebar, and `/admin/posts` sends you back to the dashboard:
the permission doesn't exist yet, so no one has it, not even a superadmin.

Now declare the permission, and grant it to groups, from the module itself. Create
`app/Modules/Blog/Config/Registrar.php`:

```php
<?php

namespace App\Modules\Blog\Config;

class Registrar
{
    public static function AuthGroups(): array
    {
        return [
            'permissions' => [
                'posts.list' => 'Can view the list of blog posts',
            ],
            'moduleGrants' => [
                'blog' => [
                    'superadmin' => ['posts.*'],
                    'admin'      => ['posts.list'],
                ],
            ],
        ];
    }
}
```

CodeIgniter only reads a `Config/Registrar.php` at the root of a namespace it knows about. Tell it about the module's
namespace in `app/Config/Autoload.php`:

```php
public $psr4 = [
    APP_NAMESPACE      => APPPATH,
    'App\Modules\Blog' => APPPATH . 'Modules/Blog',
];
```

The permission now exists. Apply the default grants to the groups:

```
php spark bonfire:permissions
```

It reports `Added 2 permission grant(s).`

**Check it:** reload the admin. **Posts** is back in the sidebar, and the page opens again. In
**Settings › User Groups**, open the Superadmin group's permissions: `posts.list` is ticked.

[Permissions](permissions.md) explains the registrar and the grants command in more depth.

## Step 6: List the posts

Now put real data on the page. Create a model at `app/Modules/Blog/Models/PostModel.php`. Soft deletes mean a
deleted post keeps its row with a `deleted_at` date, and the model leaves it out of results:

```php
<?php

namespace App\Modules\Blog\Models;

use CodeIgniter\Model;

class PostModel extends Model
{
    protected $table          = 'posts';
    protected $returnType     = 'object';
    protected $useSoftDeletes = true;
    protected $useTimestamps  = true;
    protected $allowedFields  = ['title', 'status'];
}
```

Update the controller to load a page of posts. `setting('Site.perPage')` is the page size, from `app/Config/Site.php`:

```php
<?php

namespace App\Modules\Blog\Controllers;

use App\Modules\Blog\Models\PostModel;
use Bonfire\Core\AdminController;

class PostController extends AdminController
{
    public function list()
    {
        if (! auth()->user()->can('posts.list')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $model = model(PostModel::class);

        return $this->render('App\Modules\Blog\Views\list', [
            'posts' => $model->orderBy('created_at', 'desc')->paginate(setting('Site.perPage')),
            'pager' => $model->pager,
            'total' => $model->pager->getTotal(),
        ]);
    }
}
```

Put the table in its own view, `app/Modules/Blog/Views/_table.php`. In step 7 the search and filters will reload just
this part of the page. It uses three more components and helpers:

- `<x-status>` shows each post's status as a coloured dot and label.
- `<x-empty-state>` takes the table's place when nothing matches.
- The `bonfire_list_hx` pager template adds the "Showing X of Y · Previous / Next" footer.

```php
<?php if ($posts === []) : ?>
    <x-empty-state title="No posts match" hint="Try a different search or filter." />
<?php else : ?>
    <div class="table-responsive">
        <table class="table table-hover bf-table">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($posts as $post) : ?>
                <tr>
                    <td><?= esc($post->title) ?></td>
                    <td>
                        <x-status tone="<?= $post->status === 'published' ? 'ok' : 'muted' ?>" label="<?= esc(ucfirst($post->status)) ?>" />
                    </td>
                    <td><?= app_date($post->created_at) ?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>

    <?= $pager->links('default', 'bonfire_list_hx') ?>
<?php endif ?>
```

Then include it from `list.php`, and show the total beside the title with the page head's `count`:

```php
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="Posts" count="<?= $total ?>" />

<x-admin-box>
    <div id="content-list" hx-target="#content-list">
        <?= $this->include('App\Modules\Blog\Views\_table') ?>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
```

The pager's Previous / Next links load the next page with [htmx](https://htmx.org/) and swap it into the element
named by the nearest `hx-target`, here `#content-list`.

**Check it:** the page lists six posts with green "Published" and grey "Draft" labels, and a count of 6
beside the title. "A post nobody needs" is missing because it is deleted. The footer reads "Showing 6 of 6".

## Step 7: Add search and filters

Bonfire draws a filter panel from a list of filters declared on a model. Add them to a model of their own, so
`PostModel` stays plain. Create `app/Modules/Blog/Models/PostFilter.php`:

```php
<?php

namespace App\Modules\Blog\Models;

use Bonfire\Core\Traits\Filterable;

class PostFilter extends PostModel
{
    use Filterable;

    protected $filters = [
        'status' => [
            'title'   => 'Status',
            'options' => ['draft' => 'Draft', 'published' => 'Published'],
        ],
    ];
}
```

Each key of `$filters` is a column. Its `options` become checkboxes, and the `Filterable` trait's `filter()` method
turns the checked ones into a `WHERE status IN (…)`. [Filters](filters.md) covers radio buttons and options computed
by a method.

Update the controller. It applies the filters and the search term from the query string. When htmx asks for the list
(the request has an `HX-Request` header), it returns only `_table`, which htmx swaps into the page:

```php
<?php

namespace App\Modules\Blog\Controllers;

use App\Modules\Blog\Models\PostFilter;
use Bonfire\Core\AdminController;

class PostController extends AdminController
{
    public function list()
    {
        if (! auth()->user()->can('posts.list')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $model  = model(PostFilter::class);
        $search = trim((string) $this->request->getGet('search'));

        $model->filter($this->request->getGet('filters'));

        if ($search !== '') {
            $model->like('title', $search);
        }

        $isHtmx = $this->request->hasHeader('HX-Request');

        return $this->render('App\Modules\Blog\Views\\' . ($isHtmx ? '_table' : 'list'), [
            'posts'  => $model->orderBy('created_at', 'desc')->paginate(setting('Site.perPage')),
            'pager'  => $model->pager,
            'total'  => $model->pager->getTotal(),
            'search' => $search,
        ]);
    }
}
```

Finally, add the toolbar and filter panel to `list.php`:

```php
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<x-page-head title="Posts" count="<?= $total ?>" />

<x-admin-box>
    <div x-data="{filtered: false}" hx-target="#content-list" hx-include=".bf-toolbar-form, .filters .filter-check">
        <div class="bf-toolbar">
            <form class="bf-toolbar-form" action="<?= current_url() ?>" method="get"
                hx-get="<?= current_url() ?>"
                hx-trigger="input delay:300ms from:[name=search]">
                <div class="bf-toolbar-search">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <input type="search" name="search" class="form-control" value="<?= esc($search) ?>"
                        placeholder="Search posts" aria-label="Search posts">
                </div>
            </form>
            <x-filter-link />
        </div>

        <div class="bf-list-with-filters">
            <div id="content-list">
                <?= $this->include('App\Modules\Blog\Views\_table') ?>
            </div>

            <div class="bf-filter-panel" x-cloak x-show="filtered" x-transition.duration.240ms>
                <?= view_cell('Bonfire\Core\Cells\Filters::renderList', 'model=App\Modules\Blog\Models\PostFilter target=#content-list') ?>
            </div>
        </div>
    </div>
</x-admin-box>
<?php $this->endSection() ?>
```

Here is what each part does:

- `x-data="{filtered: false}"` is [Alpine.js](https://alpinejs.dev/) state for whether the filter panel is open.
  `<x-filter-link>` toggles it, and `x-show="filtered"` shows or hides the panel.
- The search form sends a request 300 ms after you stop typing, with `hx-get` and `hx-trigger`.
- The `Filters::renderList` view cell draws the checkboxes from `PostFilter::$filters`, and sends a request whenever one
  changes.
- `hx-include` on the wrapper sends the search box and the checked filters with every request, so the search, the
  filters and the pager all keep each other's values.
- `hx-target` puts each response into `#content-list`.

**Check it:** type `team` in the search box. Without a page reload, the list narrows to "Meet the team" and the footer
reads "Showing 1 of 1". Clear the search, open the filters, and tick **Draft**: two drafts remain. Tick **Published**
and search for `the`: only "Meet the team" is left.

## Step 8: Appear in the global search

The search box in the admin header searches every module that provides a `SearchProvider` class at its root. Create
`app/Modules/Blog/SearchProvider.php`:

```php
<?php

namespace App\Modules\Blog;

use App\Modules\Blog\Models\PostModel;
use Bonfire\Search\Interfaces\SearchProviderInterface;

class SearchProvider extends PostModel implements SearchProviderInterface
{
    public function search(string $term, int $limit = 10, ?array $post = null): array
    {
        if (! auth()->user()->can('posts.list')) {
            return [];
        }

        return $this->like('title', $term)
            ->orderBy('created_at', 'desc')
            ->findAll($limit);
    }

    public function resourceName(): string
    {
        return 'posts';
    }

    public function resourceUrl(): string
    {
        return ADMIN_AREA . '/posts';
    }

    public function resultView(): string
    {
        return 'Search/posts';
    }
}
```

`search()` returns the matching posts. Bonfire asks every provider, whoever is searching, so the provider checks the
permission itself. `resourceName()` heads this module's results, `resourceUrl()` is the "See all" link, and
`resultView()` names the view that shows the results.

That view must be in the Admin theme. Create `themes/Admin/Search/posts.php`. The results arrive as `$rows`:

```php
<div class="table-responsive">
    <table class="table table-hover bf-table">
        <tbody>
        <?php foreach ($rows as $post) : ?>
            <tr>
                <td><?= esc($post->title) ?></td>
                <td><x-status tone="<?= $post->status === 'published' ? 'ok' : 'muted' ?>" label="<?= esc(ucfirst($post->status)) ?>" /></td>
            </tr>
        <?php endforeach ?>
        </tbody>
    </table>
</div>
```

**Check it:** type `tips` in the search box in the admin header and press Enter. The results page has a **Posts**
section listing "Ten tips for faster pages", with a "See all posts" link back to your list.

[Search](search.md) shows how to search related tables and meta fields.

## Step 9: Send deleted posts to the Recycler

The Recycler under **Tools** lists soft-deleted records so an admin can restore or purge them. It only shows the
models it has been told about. Register `PostModel` in `initAdmin()`, with the columns that help someone recognise a
post:

```php hl_lines="5 21"
<?php

namespace App\Modules\Blog;

use App\Modules\Blog\Models\PostModel;
use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

class Module extends BaseModule
{
    public function initAdmin()
    {
        $item = new MenuItem([
            'title'           => 'Posts',
            'namedRoute'      => 'posts-list',
            'fontAwesomeIcon' => 'fas fa-newspaper',
            'permission'      => 'posts.list',
        ]);
        service('menus')->menu('sidebar')->collection('content')->addItem($item);

        service('recycler')->register('posts', 'Posts', PostModel::class, ['title', 'status']);
    }
}
```

To restore a post, the Recycler sets its `deleted_at` back to `null` through the model, and a model only writes the
fields listed in `$allowedFields`. Add `deleted_at` to them in `PostModel`:

```php
protected $allowedFields  = ['title', 'status', 'deleted_at'];
```

**Check it:** open **Tools › Recycler** and pick **Posts** from the list of resources. "A post nobody needs" is there.
Click **Restore**, then go back to **Posts**: the list now shows seven posts.

## What you built

Your Blog module is a complete, if small, admin screen:

- `Module.php` adds the sidebar item and registers posts with the Recycler.
- `Config/Routes.php` maps `/admin/posts` to the controller.
- `Config/Registrar.php` declares the `posts.list` permission and grants it to superadmins and admins.
- `PostController` checks the permission and renders the full page, or only the table for htmx.
- `PostModel` soft-deletes posts, and `PostFilter` adds the status filter.
- `list.php` and `_table.php` build the page from the admin theme's components.
- `SearchProvider` and `themes/Admin/Search/posts.php` add posts to the global search.

## Next steps

- Add row and bulk actions, such as deleting posts into the Recycler: [Actions](actions.md)
- Browse the other admin components, like `<x-save-bar>` for edit forms: [View Components](view_components.md)
- Add a settings page under **Settings**: [Menus](menus.md)
- Add tabs to an edit page: [Resource Tabs](resource_tabs.md)
- Put post stats on the dashboard: [Dashboard Widgets](dashboard_widgets.md)
- Change how the Recycler lists, restores or purges posts: [Recycler](recycler.md)
