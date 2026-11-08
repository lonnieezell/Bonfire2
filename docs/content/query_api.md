# Query API

`content()` reads entries from the index. You can call it anywhere, and it works without routing or themes. It won't see
a file until that file is indexed, so run `php spark content:reindex` after you add files by hand. See
[The index and deploys](the_index.md).

```php
$posts = content()->collection('blog')->published()->orderBy('title')->get();
```

## What `content()` offers

| Method | Returns | See |
|---|---|---|
| `collection($handle)` | A query on one collection's entries | [Querying a collection](#querying-a-collection) |
| `search($term, $collection = null)` | A query on the published entries that contain the term | [Searching](#searching) |
| `find($id)` | One entry by its UUID, or `null` | [Finding entries](#finding-entries) |
| `findMany($ids)` | Entries by their UUIDs, in that order | [Finding entries](#finding-entries) |
| `findByUri($uri)` | The published entry at a URI, or `null` | [Finding entries](#finding-entries) |
| `fragment($name)` | A published fragment by its slug or `collection/slug`, or `null` | [Fragments](fragments.md#finding-a-fragment) |
| `redirectFor($uri)` | The current URI of an entry that had this URI, or `null` | [Redirects](routing.md#redirects) |
| `terms($taxonomy)` | A taxonomy's terms | [Taxonomies](#taxonomies) |
| `term($taxonomy, $slug)` | One term, or `null` | [Taxonomies](#taxonomies) |
| `navigation($handle)` | A navigation menu, the same as the `navigation()` helper | [Navigation menus](navigation.md#showing-a-menu) |
| `feed($collection)` | A response with the collection's RSS feed | [Feeds](feeds_and_sitemaps.md#feeds) |
| `sitemap()` | A response with the site's sitemap | [Sitemap](feeds_and_sitemaps.md#sitemap) |

[Globals](globals.md) aren't part of `content()`, because they are read from their files and aren't queried. Read them
with `globals('site')->field('name')`.

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

!!! warning "Call `published()` on anything public"

    Without `published()` or `whereTerm()`, a query includes drafts and scheduled entries.

Front matter fields can't be used in `orderBy()`, because they aren't indexed. Sort those in PHP.

### Pagination

Keep the query to reach its pager:

```php
$query   = content()->collection('blog')->published();
$entries = $query->paginate(10);
$pager   = $query->pager();
```

`paginate()` returns a plain list of entries, so you can loop over it like the result of `get()`. The links come from the
pager, which is CodeIgniter's [`Pager`](https://codeigniter.com/user_guide/libraries/pagination.html). `$page` comes from
the `?page=` query string unless you pass it. For a whole page built this way, see
[Listing a collection](#listing-a-collection).

## Searching

`content()->search($term)` starts a query on the published entries that have the term in their title or Markdown body,
across every collection. Pass a collection's handle as the second argument to search only that one, which throws
`Bonfire\Content\ContentException` if the collection doesn't exist.

```php
$query   = content()->search($term);            // every collection
$query   = content()->search($term, 'blog');    // only the blog
$results = $query->paginate(10);
$pager   = $query->pager();
```

It returns the same query as `collection()`, so `get()`, `paginate()` and `pager()` work as they do there, and
`orderBy()` changes the order. Results are ordered by title, whichever collections they come from.

- It only returns what `published()` keeps. Drafts, scheduled entries and entries in the trash are never found.
- It's a plain `LIKE` match on the title and the raw Markdown body, so the term is found inside words and in Markdown
  syntax. There are no wildcards: `*`, `%` and `_` each match only themselves, and a quote is safe.
- Spaces around the term are ignored, and a blank term finds nothing.
- `whereTerm()` needs a query on one collection, so it throws unless you narrowed the search to one.

Bonfire adds no search page. See [A search page](#a-search-page) for one.

## Finding entries

`content()->find($id)` returns an entry from any collection by its UUID, or `null`.

!!! warning

    `find()` finds drafts and scheduled entries too. To find only live entries, use
    `content()->collection('blog')->published()->find($id)`.

`content()->findMany($ids)` takes a list of UUIDs and returns their entries in that order. An id with no entry, or whose
entry is in the trash, is left out, so the list can be shorter than the one you gave. Like `find()`, it returns drafts
and scheduled entries too.

`content()->findByUri('/blog/hello-world')` returns the published entry at a URI, or `null` for a draft, a scheduled entry
or an unknown URI. It works with `$autoRoute` on or off, but the collection needs a `route`. See
[Finding an entry by URI](routing.md#finding-an-entry-by-uri).

An old URI of an entry that was renamed or moved is unknown to `findByUri()`. `content()->redirectFor('/blog/old-slug')`
gives the entry's current URI, or `null`. See [Redirects](routing.md#redirects).

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

$entry->content();                   // the body as HTML, with components expanded

$entry->entries('related');          // the entries an `entries` field links to

$entry->blocks('sections');          // the blocks of a `blocks` field, as a list
$entry->block('sections');           // the first of them, or null
$entry->renderBlocks('sections');    // every block through its partial

$entry->seo();                       // title, description, canonical URL and Open Graph values

$entry->isPublished();               // whether published() would keep it
```

`entries()` returns the entries in the order the file lists them, and skips a link whose entry is missing or in the
trash. It also returns drafts and scheduled entries, so check `$linked->isPublished()` before you show one. See
[Field types](field_types.md#entries).

`blocks()` returns a `BlockList` that you can loop over, count, filter with `ofType()` and render. Each `Block` has a
`type`, its `entry`, a `field($name, $default)` cast by the block's blueprint, and `render()`. A field the entry doesn't
have gives an empty list. See [Blocks](blocks.md).

`seo()` is described under [SEO](seo.md#getting-the-values).

`content()` renders the body as GitHub Flavored Markdown, with tables and the rest. Raw HTML in the body passes through,
apart from the tags GitHub disallows, such as `<script>`, so treat a body as code you trust. Links to `javascript:` and
similar are dropped. Use `$entry->body` to render it your own way.

`content()` also expands any `<x-…>` View Components in the body. Pass `components: false` to skip that. See
[Markdown and components](markdown.md).

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

$term->field('color');        // one value from the file, or null
$term->field('color', 'red'); // or a default

$term->entries();             // its published entries, most recent first
```

`count` and `entries()` only include entries that are published and whose `published_at` has passed.

## Recipes

These follow the same conventions as the views [`content:scaffold`](scaffolding.md) writes: views in `app/Views`,
rendered in the App theme with `render()`, extending its `master` layout.

### Listing a collection

Bonfire doesn't register an index route for a collection, because the page is yours to design. This recipe lists a
collection's published entries a page at a time. It assumes the collection has a `route`, so that its entries have a
`uri`. See [Routing](routing.md#serving-a-collection).

Add the route in `app/Config/Routes.php`:

```php
$routes->get('blog', static function (): string {
    $query = content()->collection('blog')->published();

    return render('App', 'blog/index', [
        'entries' => $query->paginate(10),
        'pager'   => $query->pager(),
    ]);
});
```

Then add the view, `app/Views/blog/index.php`:

```php
<?= $this->extend('master') ?>

<?= $this->section('main') ?>
    <h1>Blog</h1>

    <?php foreach ($entries as $entry): ?>
        <article>
            <h2><a href="<?= esc($entry->uri) ?>"><?= esc($entry->title) ?></a></h2>
            <p><?= esc($entry->field('tagline')) ?></p>
        </article>
    <?php endforeach ?>

    <?= $pager->links() ?>
<?= $this->endSection() ?>
```

`/blog` shows the first ten entries and `/blog?page=2` the next ten, with links between the pages. The collection's
`sort` sets the order, or call `orderBy()` before `paginate()`. To list the entries of a term, use `whereTerm()` in place
of `published()`.

Your route wins over the [fallback route](routing.md#turning-it-on), so `/blog` and `/blog/{slug}` can both exist.

To change how the links look, pass `links()` the name of a
[pager template](https://codeigniter.com/user_guide/libraries/pagination.html#customizing-the-pagination-links) as its
second argument: `$pager->links('default', 'my_pager')`.

### A search page

This route and view list the results of `?q=` a page at a time. The pager links keep `?q=`:

```php
$routes->get('search', static function (): string {
    $term  = (string) service('request')->getGet('q');
    $query = content()->search($term);

    return render('App', 'search', [
        'term'    => $term,
        'results' => $query->paginate(10),
        'pager'   => $query->pager(),
    ]);
});
```

`app/Views/search.php`:

```php
<?= $this->extend('master') ?>

<?= $this->section('main') ?>
    <h1>Results for “<?= esc($term) ?>”</h1>

    <?php foreach ($results as $entry): ?>
        <h2><a href="<?= esc($entry->uri) ?>"><?= esc($entry->title) ?></a></h2>
    <?php endforeach ?>

    <?= $pager->links() ?>
<?= $this->endSection() ?>
```
