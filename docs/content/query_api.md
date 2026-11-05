# Query API

`content()` reads entries from the index. You can call it anywhere, and it works without routing or themes. Run
`php spark content:reindex` first, because it won't see a file until that file is indexed.

```php
$posts = content()->collection('blog')->published()->orderBy('title')->get();
```

## Querying a collection

`content()->collection($handle)` starts a query. It throws `Bonfire\Content\ContentException` if the collection doesn't
exist or its config is invalid.

| Method | What it does |
|---|---|
| `published()` | Keeps entries with the `published` status, and leaves out those with a future `published_at`. |
| `whereTerm($taxonomy, $slug)` | Keeps published entries that have the term. See [Taxonomies](#taxonomies). |
| `orderBy($field, $direction = 'asc')` | Replaces the collection's sort. `$field` is `title`, `slug` or `published_at`. |
| `get()` | Returns every matching entry. |
| `paginate($perPage = 20, $group = 'default', $page = null)` | Returns one page of entries. |
| `pager()` | Returns the pager after `paginate()`. |
| `find($id)` | Returns the entry with this UUID if it matches the query, or `null`. |

Without `published()`, a query includes drafts and scheduled entries, so call it on anything public.

Front matter fields can't be used in `orderBy()`, because they aren't indexed. Sort those in PHP.

### Pagination

Keep the query to reach its pager:

```php
$query = content()->collection('blog')->published();
$posts = $query->paginate(10);
$pager = $query->pager();
```

`paginate()` returns a plain list of entries, so you can loop over it like the result of `get()`. The links come from the
pager, which is CodeIgniter's [`Pager`](https://codeigniter.com/user_guide/libraries/pagination.html). `$page` comes from
the `?page=` query string unless you pass it.

### Listing a collection

Bonfire doesn't register an index route for a collection, because the page is yours to design. This recipe lists a
collection's published entries a page at a time. It assumes the collection has a `route`, so that its entries have a
`uri`. See [Routing](routing.md#serving-a-collection).

Add the route in `app/Config/Routes.php`:

```php
$routes->get('blog', static function (): string {
    $query = content()->collection('blog')->published();

    return view('blog/index', [
        'posts' => $query->paginate(10),
        'pager' => $query->pager(),
    ]);
});
```

Then add the view, `app/Views/blog/index.php`:

```php
<h1>Blog</h1>

<?php foreach ($posts as $post): ?>
    <article>
        <h2><a href="<?= esc($post->uri) ?>"><?= esc($post->title) ?></a></h2>
        <p><?= esc($post->field('tagline')) ?></p>
    </article>
<?php endforeach ?>

<?= $pager->links() ?>
```

`/blog` shows the first ten entries and `/blog?page=2` the next ten, with links between the pages. The collection's
`sort` sets the order, or call `orderBy()` before `paginate()`. To list the entries of a term, use `whereTerm()` in place
of `published()`.

Your route wins over the [fallback route](routing.md#turning-it-on), so `/blog` and `/blog/{slug}` can both exist.

To change how the links look, pass `links()` the name of a [pager template](https://codeigniter.com/user_guide/libraries/pagination.html#customizing-the-pagination-links)
as its second argument: `$pager->links('default', 'my_pager')`.

### Feeds

`content()->feed($handle)` returns a response with the collection's published entries as an RSS feed, for you to return
from a route. See [Feeds](routing.md#feeds).

## Searching

`content()->search($term)` starts a query on the published entries that have the term in their title or Markdown body,
across every collection. Pass a collection's handle as the second argument to search only that one, which throws
`Bonfire\Content\ContentException` if the collection doesn't exist. Bonfire adds no search route or page, because the
page is yours to design.

```php
$query   = content()->search($term);            // every collection
$query   = content()->search($term, 'blog');    // only the blog
$results = $query->paginate(10);
$pager   = $query->pager();
```

It returns the same query as `collection()`, so `get()`, `paginate()` and `pager()` work as they do there, and
`orderBy()` changes the order. Results are ordered by title, whichever collections they come from.

- It only returns what `published()` keeps. Drafts, scheduled entries and entries in the trash are never found.
- It's a `LIKE` match on the title and the raw Markdown body, so the term is found inside words, and a `*` in the term
  matches a `*` in the body. A `%` or `_` in the term matches itself, and a quote is safe.
- Spaces around the term are ignored, and a blank term finds nothing.
- `whereTerm()` needs a query on one collection, so it throws unless you narrowed the search to one.

This route and view list the results a page at a time. The pager links keep `?q=`:

```php
$routes->get('search', static function (): string {
    $term  = (string) service('request')->getGet('q');
    $query = content()->search($term);

    return view('search', [
        'term'    => $term,
        'results' => $query->paginate(10),
        'pager'   => $query->pager(),
    ]);
});
```

```php
<?php foreach ($results as $entry): ?>
    <h2><a href="<?= esc($entry->uri) ?>"><?= esc($entry->title) ?></a></h2>
<?php endforeach ?>

<?= $pager->links() ?>
```

## Finding an entry

`content()->find($id)` returns an entry from any collection by its UUID, or `null`.

!!! warning

    It finds drafts and scheduled entries too. To find only live entries, use
    `content()->collection('blog')->published()->find($id)`.

## Finding several entries

`content()->findMany($ids)` takes a list of UUIDs and returns their entries in that order. An id with no entry, or whose
entry is in the trash, is left out, so the list can be shorter than the one you gave. Like `find()`, it returns drafts
and scheduled entries too.

## Finding an entry by URI

`content()->findByUri('/blog/hello-world')` returns the published entry at a URI, or `null` for a draft, a scheduled entry
or an unknown URI. It works without [auto-routing](routing.md#finding-an-entry-by-uri), but the collection needs a `route`.

An old URI of an entry that was renamed or moved is unknown to it. `content()->redirectFor('/blog/old-slug')` gives the
entry's current URI, or `null`. See [Redirects](routing.md#redirects).

## Sitemap

`content()->sitemap()` returns a response with a sitemap of every collection's published entries that have a URI. See
[Sitemap](routing.md#sitemap).

## Navigation menus

`navigation('main')` returns a [menu](navigation.md#showing-a-menu) built in the admin as a tree of items, each with a
`title`, `url`, `active` and `children`. It's the same as `content()->navigation('main')`.

## Globals

`globals('site')->field('name')` reads a value from a [set of globals](globals.md), cast by its blueprint. It isn't part
of `content()`, because globals are read from their files and aren't queried.

## Taxonomies

`whereTerm()` keeps the published entries that have a [term](taxonomies.md), the same as `published()` does, and it
composes with the rest of the query:

```php
$posts = content()->collection('blog')->whereTerm('tags', 'php')->orderBy('title')->get();
```

Calling it again keeps only entries that have every term asked for. A term that doesn't exist matches nothing. It throws
`ContentException` if the collection doesn't list the taxonomy in its `taxonomies`.

`content()->terms($taxonomy)` returns a taxonomy's terms by title, and `content()->term($taxonomy, $slug)` returns one
term or `null`. Both throw `ContentException` if the taxonomy doesn't exist. Each is a `Bonfire\Content\Term`:

```php
$term->id;        // the UUID
$term->taxonomy;  // 'tags'
$term->slug;      // 'php'
$term->title;     // 'PHP'
$term->data;      // all the keys of the term's file, as an array
$term->count;     // how many published entries have it, in any collection

$term->field('color');       // one value from the file, or null
$term->field('color', 'red'); // or a default

$term->entries();            // its published entries, most recent first
```

`count` and `entries()` only include entries that are published and whose `published_at` has passed.

## Entries

Queries return `Bonfire\Content\Entry` objects:

```php
$entry->id;           // the UUID
$entry->collection;   // 'blog'
$entry->blueprint;    // 'post', or null
$entry->slug;         // 'hello-world'
$entry->uri;          // '/blog/hello-world', or null if the collection has no route
$entry->status;       // 'published' or 'draft'
$entry->publishedAt;  // '2026-10-27 00:00:00', or null
$entry->title;        // 'Hello world'
$entry->body;         // the raw Markdown
$entry->data;         // all the front matter, as an array

$entry->field('tagline');            // one front matter value, or null
$entry->field('tagline', 'Welcome'); // or a default

$entry->content();                   // the body as HTML

$entry->entries('related');          // the entries an `entries` field links to
```

`entries()` returns the entries in the order the file lists them, and skips a link whose entry is missing or in the
trash. It also returns drafts, so check `$linked->status` before you show one. See [Field types](field_types.md#entries).

`content()` renders the body as GitHub Flavored Markdown, with tables and the rest. Raw HTML in the body passes through,
apart from the tags GitHub disallows, such as `<script>`, so treat a body as code you trust. Links to `javascript:` and
similar are dropped. Use `$entry->body` to render it your own way.
