# Routing

Content can serve entries on your front end. It's opt-in, and you decide how each page is rendered. If you'd rather write
your own routes, you can skip the fallback route and use [`content()->findByUri()`](#finding-an-entry-by-uri).

## Turning it on

Set `$autoRoute` in `app/Config/Content.php`:

```php
public bool $autoRoute = true;
```

This adds a fallback route, which Bonfire places after every other route, so your routes always win. With `$autoRoute`
off, the default, Content adds no routes.

The fallback route takes every request that no other route took, and answers a URI with no `GET` entry with a 404. Your
`set404Override()` still handles those. These requests now run your filters and one database query before the 404, where
they used to stop at the router, so a site with a lot of 404 traffic will do more work.

!!! warning

    CodeIgniter only tries auto-routing when no route matches, so turning this on stops any auto-routed controllers from
    being reached. Give them defined routes first.

## Serving a collection

Give the collection a `route`, and a `template` or a `controller`, but not both:

```yaml
title: Blog
dated: true
route: /blog/{slug}
template: blog/show
```

Then run `php spark content:reindex`, because an entry's URI is stored in the index.

A collection with no `route` is never served, and its entries have no URI. A collection with a `route` but no `template`
or `controller` gets URIs that the fallback route answers with a 404, which is what you want if you serve it from your
own routes.

`route` must contain `{slug}`, which is the only placeholder. Two entries can't share a URI: the reindex reports the second
one and doesn't index it. A draft holds its URI too. In a dated collection, this means the slug has to be unique across
dates. URIs that differ only by case count as the same URI.

Keep slugs in a routed collection to letters, numbers, `-` and `_`, because the reindex doesn't check them. CodeIgniter
reads a `+` in a path as a space, so `c++-tips.md` can't be reached at `/blog/c++-tips`, and it refuses characters outside
`$permittedURIChars` in `app/Config/App.php`, such as those in `café.md`.

A [taxonomy](taxonomies.md#term-pages) can have pages for its terms in the same way, with a `route` and a `template`.

## What gets served

Only entries with the `published` status whose `published_at` has passed, or that have none, are served. Anything else
gets a 404 before any template or controller runs.

## Templates

A template is a view, looked up in the App theme first, then in `app/Views`. `template: blog/show` finds
`themes/App/blog/show.php` or `app/Views/blog/show.php`. A template that doesn't exist is an error, not a 404.

It receives `$entry` and `$collection`, and can extend the theme's layout like any other view:

```php
<?= $this->extend('master') ?>

<?= $this->section('main') ?>
    <h1><?= esc($entry->title) ?></h1>
    <p><?= esc($entry->field('tagline')) ?></p>

    <?= $entry->content() ?>
<?= $this->endSection() ?>
```

`$entry` is described under [Entries](query_api.md#entries). `$collection` is a `Bonfire\Content\Collection`, with public
properties such as `$collection->title` and `$collection->handle`.

## Handing off to a controller

Use a controller when you need something between finding the entry and rendering the page, such as extra queries, an
authorization check or a JSON response:

```yaml
title: Blog
route: /blog/{slug}
controller: App\Controllers\Blog::show
```

Bonfire finds the entry, applies the published check and the 404, then calls your method with the entry:

```php
namespace App\Controllers;

use Bonfire\Content\Entry;

class Blog extends BaseController
{
    public function show(Entry $entry)
    {
        return $this->response->setJSON(['title' => $entry->title]);
    }
}
```

What the method returns is the response, the same as for a route of your own. Bonfire adds nothing to it. The class must
extend `CodeIgniter\Controller`. The method doesn't need to be routable itself.

A malformed `controller` value is reported by the reindex, but a class or method that doesn't exist is an error when the
page is requested.

## Finding an entry by URI

`content()->findByUri()` applies the same rules, and works with `$autoRoute` on or off. Use it in a route of your own:

```php
$routes->get('blog/(:segment)', static function (string $slug) {
    $entry = content()->findByUri("/blog/{$slug}");

    if ($entry === null) {
        throw PageNotFoundException::forPageNotFound();
    }

    return view('blog/show', ['entry' => $entry]);
});
```

The collection still needs a `route`, since that's what gives its entries URIs.

## Seeing changes

In production, run `php spark content:reindex` when you deploy. A changed file or `route` doesn't take effect until you do.
If a collection's config is missing or broken by then, its entries get a 404 and the reason is logged.

When `ENVIRONMENT` is `development`, the first `content()` call in a request checks whether any file under
`content/collections/` has changed, or the index has lost rows, and reindexes if so, so you can edit a file and refresh. The
fallback route makes that call for you. A file that can't be indexed is written to the log, and loses its row until you fix it.
