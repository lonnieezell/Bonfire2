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

## Entries

Queries return `Bonfire\Content\Entry` objects:

```php
$entry->id;           // the UUID
$entry->collection;   // 'blog'
$entry->blueprint;    // 'post', or null
$entry->slug;         // 'hello-world'
$entry->status;       // 'published' or 'draft'
$entry->publishedAt;  // '2026-10-27 00:00:00', or null
$entry->title;        // 'Hello world'
$entry->body;         // the raw Markdown
$entry->data;         // all the front matter, as an array

$entry->field('tagline');            // one front matter value, or null
$entry->field('tagline', 'Welcome'); // or a default
```
