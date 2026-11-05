# Routing

Content can serve entries on your front end through a **fallback route**. It's opt-in, and you decide how each page is
rendered. If you'd rather write your own routes, leave the fallback route off and use
[`content()->findByUri()`](#finding-an-entry-by-uri).

## Turning it on

Set `$autoRoute` in `app/Config/Content.php`:

```php
public bool $autoRoute = true;
```

This adds the fallback route, which Bonfire places after every other route, so your routes always win. With
`$autoRoute` off, the default, Content adds no routes for entries.

The fallback route takes every request that no other route took. If no entry has the requested URI, or the request
isn't a `GET`, it answers with a 404, which your `set404Override()` still handles. Those requests now run your filters
and one database query before the 404, where they used to stop at the router, so a site with a lot of 404 traffic will
do more work.

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

`route` must contain `{slug}`, which is the only placeholder. Two entries can't share a URI: the reindex reports the
second one and doesn't index it. A draft holds its URI too. In a dated collection, this means the slug has to be unique
across dates. URIs that differ only by case count as the same URI.

Keep slugs in a routed collection to letters, numbers, `-` and `_`, because the reindex doesn't check them. CodeIgniter
reads a `+` in a path as a space, so `c++-tips.md` can't be reached at `/blog/c++-tips`, and it refuses characters
outside `$permittedURIChars` in `app/Config/App.php`, such as those in `café.md`.

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

Bonfire adds no `<head>` tags for the entry unless you set `$autoSeo`. See [SEO](seo.md).

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

What the method returns is the response, the same as for a route of your own. Bonfire adds nothing to it, except to a
[draft preview](#previews). The class must extend `CodeIgniter\Controller`. The method doesn't need to be routable
itself.

A malformed `controller` value is reported by the reindex, but a class or method that doesn't exist is an error when the
page is requested.

## Finding an entry by URI

`content()->findByUri()` applies the same rules as the fallback route, and works with `$autoRoute` on or off. Use it in a
route of your own:

```php
$routes->get('blog/(:segment)', static function (string $slug) {
    $entry = content()->findByUri("/blog/{$slug}");

    if ($entry === null) {
        throw PageNotFoundException::forPageNotFound();
    }

    return render('App', 'blog/show', ['entry' => $entry]);
});
```

The collection still needs a `route`, since that's what gives its entries URIs.

## Redirects

When an entry's URI changes, its old URI keeps working. With `$autoRoute` on, a request for the old URI gets a `301` to
the entry's current one. A URI changes when you move a page in the [tree editor](structure.md#the-tree-editor), and when
a reindex finds that a file was renamed, a page moved in its tree file, or a `route` changed. Moving a page covers the
pages below it too. The admin has no way to change an entry's slug, because the slug is the file's name, so a rename is
a rename of the file.

The old URIs are kept in `redirects.yaml` at the content root, next to `collections/`:

```yaml
/about: 5c1e9a7b-3d24-4f68-8a0e-71b2c9d4e6f3
/about/team: 9a3d7e51-2b86-4c0f-a1d9-4e8f6b2c7a15
```

Each line is an old URI and the id of the entry that now has it. Because it names the entry and not its new URI, a
second move changes nothing in the file: after `/a` becomes `/b` and then `/c`, both `/a` and `/b` lead to `/c`. Bonfire
writes the file, sorted by URI, and you can edit it by hand. Comments in it don't survive the next write, and a line
whose id isn't a UUID is dropped. The file is read only when a URI isn't found, so a request that has an entry doesn't
pay for it.

The redirects aren't in the index, because the index can be rebuilt from your files but an old URI can't. A table would
lose every redirect on a rebuild or on a new environment. `php spark content:reindex --fresh` leaves the file alone.

!!! warning "Commit `redirects.yaml` with your content"

    It is part of the site like an entry is, and a server that has no copy has no redirects. A reindex on production
    that finds a changed URI writes to it too, so, as for the
    [entry files](constraints.md#the-content-directory-must-be-on-one-machine), production needs the content directory
    on one server or on storage that all servers share. See [Git isn't automated](constraints.md#git-isnt-automated).

A redirect never overrides or points at something that isn't there:

- An entry published at a URI always wins over a redirect from it. Move a page back to an old URI, or create an entry
  there, even a draft, and the redirect from that URI is dropped from the file, so there are no loops.
- If the entry is in the trash, a draft, or scheduled, or has been deleted for good, the old URI is a `404`. Restore or
  publish it and the redirect works again.
- Only a `GET` request is redirected.

A file that can't be read is logged and gives no redirects. A reindex then reports it and doesn't touch it, and a move in
the admin is saved but says its old URIs weren't kept. The URIs that change while the file is broken get no redirects,
even after you fix it.

For a route of your own, `content()->redirectFor()` gives the same answer as the fallback route:

```php
$routes->get('blog/(:segment)', static function (string $slug) {
    $uri   = "/blog/{$slug}";
    $entry = content()->findByUri($uri);

    if ($entry === null) {
        if (($current = content()->redirectFor($uri)) !== null) {
            return redirect()->to($current, 301);
        }

        throw PageNotFoundException::forPageNotFound();
    }

    return render('App', 'blog/show', ['entry' => $entry]);
});
```

It returns the entry's current URI, or `null`. `findByUri()` still treats an old URI as missing.

## Caching

A collection can have its pages served from the cache. It's off unless the collection sets `cache`, the number of seconds
a page is kept:

```yaml
title: Blog
route: /blog/{slug}
template: blog/show
cache: 3600
```

A guest's repeat `GET` of one of its pages then comes from your app's cache, without rendering the template or running
your controller. It's the response body that is kept, with the `Content-Type` of a controller's response, so a
controller that sets a cookie or other headers doesn't have them replayed. `cache` must be a whole number of seconds, at
least 1, or the collection's config is invalid.

!!! warning "Only cache pages that are the same for every guest"

    Every guest gets the first guest's copy. Don't cache a collection whose pages hold a form with a CSRF token, show
    something from the session, or change with a request header such as `Accept` or `HX-Request`, or with the host. A
    page is kept by its path alone.

Only some requests are cached:

- Only pages that the fallback route serves, so a route of your own is never cached, even at an entry's URI.
- Only `GET` requests with no query string. A page may depend on the query string, for example to paginate, so those
  are rendered every time.
- Only requests from guests. A logged-in user always gets a fresh page, so an editor sees a change at once, and the page
  they get is not kept for guests.
- Only pages that rendered with a 200 status. A 404, a redirect or an error is never cached.
- Not [term pages](taxonomies.md#term-pages), since they belong to a taxonomy and not to a collection.
- Not [draft previews](#previews), or the admin's Markdown preview.

### What clears it

Anything that changes Content clears **all** of its cached pages, not just the one that changed, because a renamed page
changes the menus and `entries` links on other pages. These all clear it:

- Saving an entry, creating one, or moving pages in the structure editor.
- Moving an entry to the trash, restoring it and purging it.
- Saving a term or a navigation menu.
- `content:reindex`, and the [automatic reindex](the_index.md#in-development) in development.

A changed file that isn't reindexed doesn't, because the index is what's served. Nor does time passing: a scheduled entry
whose `published_at` arrives shows on cached pages that list it only once they expire, after `cache` seconds at most.

Clearing is done by changing a generation number in the cache, so the old pages are left to expire and the cache is never
emptied. With the `dummy` cache handler nothing is kept, so every request renders.

## Previews

[Previewing a draft](admin.md#previewing-a-draft) in the admin gives a signed link to `/content-preview/{id}`, which runs
the same code as the fallback route once the entry is found: the collection's controller gets the entry, or its template
is rendered with `$entry` and `$collection`. The difference is how the entry is found. The link names the entry by its
id, so a draft or scheduled entry is shown, and only a link with a valid signature that hasn't expired is served. Any
other request still gets a 404 for a draft or scheduled entry.

Bonfire marks the response `X-Robots-Tag: noindex` and `Cache-Control: no-store`, and turns page caching off for it, even
if your controller asks for it with `cachePage()`. It does that after your controller returns, so a controller or
template that sets those itself is overridden.
