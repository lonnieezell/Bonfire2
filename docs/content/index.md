# Content

Content lets you keep pages and posts as Markdown and YAML files in your repository, and query them from your own
controllers and views. The files are the source of truth. The database holds an index that you can always rebuild.

New to Content? [Getting started](getting_started.md) takes you from nothing to a published blog post. Before you rely on
it, read the [Constraints and known gaps](constraints.md).

## How it works

Content is file-first. Your entries, and the config of your collections and taxonomies, are plain files that you can
write in your editor, review in a pull request and keep in version control. Nothing about them is stored only in the
database.

The database holds an **index** of those files: a row for each entry and term, with the columns a query needs, such as
the status, the date and the URI. [Querying](query_api.md) and [routing](routing.md) read the index, so they don't open
a file for every request. The admin writes the files and updates the index in the same save. If the index is lost or
out of date, `php spark content:reindex` rebuilds it from the files, and loses nothing. See
[The index and deploys](the_index.md).

So the files are for people and the index is for the site. If they differ, the files are right.

## What Content does and doesn't do

Content stores, indexes, edits and finds your content. How it looks on your site is up to you.

It gives you:

- An admin form for each entry, built from a [blueprint](blueprints.md), that writes the file.
- A [query API](query_api.md) for your controllers and views.
- An optional fallback route that serves each entry at its URI through a template or controller you name. See
  [Routing](routing.md).
- Helpers for what most sites need: [SEO tags](seo.md), [feeds and a sitemap](feeds_and_sitemaps.md),
  [navigation menus](navigation.md) and [redirects](routing.md#redirects).

It doesn't give you:

- **Page designs.** There are no built-in listing pages, search pages or themes for your content. You write the views,
  or start from the ones [`content:scaffold`](scaffolding.md) writes.
- **Routes you didn't ask for.** Routing, the sitemap route and the feed route are all off until you turn them on.
- **Version control.** Bonfire doesn't commit or push what the admin saves. See
  [Git isn't automated](constraints.md#git-isnt-automated).

## File layout

Everything lives under the content root, which is `content/` in your project by default.

```
content/
├── collections/
│   ├── blog.yaml                       the blog collection's config
│   └── blog/
│       ├── 2026-10-27.hello-world.md   an entry
│       └── 2026-11-01.second-post.md
├── taxonomies/
│   ├── tags.yaml                       the tags taxonomy's config
│   └── tags/
│       └── php.yaml                    a term
├── trees/
│   ├── collections/pages.yaml          the page tree of a structured collection
│   └── navigation/main.yaml            the items of a navigation menu
├── navigation/
│   └── main.yaml                       a navigation menu's config
├── globals/
│   └── site.yaml                       a set of globals
├── .trash/                             deleted entries, until they're restored or purged
└── redirects.yaml                      the old URIs of renamed and moved entries
```

| Path | See |
|---|---|
| `collections/` | [Collections](collections.md) |
| `taxonomies/` | [Taxonomies](taxonomies.md) |
| `trees/collections/` | [Pages and structures](structure.md#the-tree-file) |
| `navigation/` and `trees/navigation/` | [Navigation menus](navigation.md) |
| `globals/` | [Globals](globals.md) |
| `.trash/` | [Deleting and restoring](admin.md#deleting-and-restoring) |
| `redirects.yaml` | [Redirects](routing.md#redirects) |

Blueprints are not in the content root. They live at `resources/blueprints/`. See [Blueprints](blueprints.md).

Change the locations with `$contentPath` and `$blueprintPath` in `app/Config/Content.php`. See
[Configuration and commands](reference.md).
