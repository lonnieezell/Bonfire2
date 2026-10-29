# Admin Controllers, Views and Themes

This page shows how to render an admin page inside the Admin theme, lay out its view, and change the theme's own
files without losing your changes when Bonfire updates.

## Render an admin page

Extend `Bonfire\Core\AdminController` and return `$this->render()` with the full, namespaced name of your view:

```php
namespace App\Modules\Blog\Controllers;

use App\Modules\Blog\Models\PostModel;
use Bonfire\Core\AdminController;

class PostController extends AdminController
{
    public function list()
    {
        return $this->render('App\Modules\Blog\Views\list', [
            'posts' => model(PostModel::class)->findAll(),
        ]);
    }
}
```

`AdminController` uses the `Bonfire\View\Themeable` trait with its `$theme` property set to `Admin`. `render()`
points the view renderer at the `themes/Admin` folder, adds a `$viewMeta` variable to your data (the layout uses it
for the page title and meta tags, see [Metadata](../building_sites/metadata.md)), and renders the view.

`render()` takes the same `$data` and `$options` arguments as CodeIgniter's `view()`. A view name with a namespace
loads from your module. A name without one, like `Search/index`, loads from the theme folder.

## Lay out the view

Start the view by extending the theme's `master` layout. Because the renderer points at the theme folder, `master`
means `themes/Admin/master.php`:

```php
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
    <x-page-head title="Posts"></x-page-head>

    <x-admin-box title="All posts">
        <?php foreach ($posts as $post) : ?>
            <p><?= esc($post->title) ?></p>
        <?php endforeach ?>
    </x-admin-box>
<?php $this->endSection() ?>

<?php $this->section('styles') ?>
    <style>.post-draft { opacity: .6; }</style>
<?php $this->endSection() ?>

<?php $this->section('scripts') ?>
    <script>console.log('Posts loaded');</script>
<?php $this->endSection() ?>
```

The Admin layout renders these sections:

| Section       | Where it goes                                                                 |
|---------------|-------------------------------------------------------------------------------|
| `main`        | The page content, between the header and the footer.                          |
| `styles`      | The end of `<head>`, after the admin CSS.                                     |
| `scripts`     | The end of `<body>`, after Bootstrap, Alpine.js and htmx have loaded.         |
| `crumb`       | The page's name in the header breadcrumb.                                     |
| `crumbParent` | The breadcrumb's parent, when the page's URL doesn't sit under a menu item.   |

Only `main` is required. See [Naming a page in the breadcrumb](creating_an_admin_module.md#naming-a-page-in-the-breadcrumb)
for `crumb` and `crumbParent`, and [View Components](view_components.md) for the `<x-…>` tags.

## Use a different theme

Each folder in `themes/` is a theme, named after the folder. To render a controller with another theme, set its
`$theme` property:

```php
use Bonfire\Core\AdminController;

class ReportController extends AdminController
{
    protected $theme = 'Reports';
}
```

Controllers that extend `Bonfire\Core\BaseController` use the `App` theme by default. Any controller can use the
`Bonfire\View\Themeable` trait to get `render()` and `$theme` without extending a Bonfire controller.

To pick the theme at runtime, change `$this->theme` before the first `render()` call. The trait keeps the renderer it
creates on that first call, so later changes have no effect. If the theme folder doesn't exist, `render()` throws a
`RuntimeException`.

## Render without a controller

The `render()` helper does the same work where there is no controller, such as a route closure. Pass the theme name
first:

```php
$routes->get('admin/blog/preview', static fn () => render('Admin', 'App\Modules\Blog\Views\preview', [
    'title' => 'Preview',
]));
```

The theme name must match the folder name exactly, including case. Unlike the trait, the helper doesn't check that
the folder exists.

## Override a theme view

`php spark bf:install` copies Bonfire's themes into a `themes/` folder in your project root, and publishes
`app/Config/Themes.php` pointing at it. Your app renders only this copy, so to change any theme file, edit it in
place:

- `themes/Admin/master.php`: the page layout
- `themes/Admin/_header.php`: the header, breadcrumb and user menu
- `themes/Admin/Components/`: the `<x-…>` view components
- `themes/Admin/Search/`: the global search result views
- `themes/Admin/css/` and `themes/Admin/js/`: the admin styles and scripts

To keep Bonfire's version alongside your own, copy the theme to a new folder, for example `themes/MyAdmin`, edit the
copy, and point your controllers at it with `$theme`. Bonfire's own admin pages keep using `Admin`.

## When Bonfire updates

`composer update` changes the themes in `vendor/lonnieezell/bonfire/themes/`, never your copy in `themes/`. Your
edits are safe, but your copy also doesn't get Bonfire's fixes and new features.

When an update changes a theme in a way your copy needs, the [changelog](../intro/changelog.md) marks it
`(breaking change)` and says what to change, and `php spark notify:breaking-changes` warns you after
`composer update` (see [Updating Bonfire](../intro/installation.md#updating-bonfire)). To find every difference
between the two versions, compare the folders:

```console
diff -ru vendor/lonnieezell/bonfire/themes/Admin themes/Admin
```
