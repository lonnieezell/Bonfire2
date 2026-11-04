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
