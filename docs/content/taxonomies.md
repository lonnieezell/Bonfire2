# Taxonomies

A taxonomy groups entries by terms, like tags or categories. Both are files: a config file for the taxonomy and one file
for each term. Entries hold the slugs of their terms in their front matter, and the index lets you query by them.

```
content/
├── collections/
│   ├── blog.yaml
│   └── blog/
│       └── 2026-10-27.hello-world.md   tags: [php, laravel]
└── taxonomies/
    ├── tags.yaml                       the tags taxonomy's config
    └── tags/
        ├── php.yaml                    a term
        └── laravel.yaml
```

Handles use letters, numbers, `-` and `_`. Subfolders aren't read.

## Taxonomy config

```yaml
title: Tags
route: /tags/{slug}
template: tags/show
```

| Key | Default | What it does |
|---|---|---|
| `title` | The handle | The taxonomy's name. |
| `route` | None | The URI of its term pages, such as `/tags/{slug}`. It must contain `{slug}`, the only placeholder. |
| `template` | None | The view that renders a term page, such as `tags/show`. |

`route` and `template` only matter when [`$autoRoute`](#term-pages) is on. A taxonomy without them has no pages.

## Terms

A term is a YAML file at `content/taxonomies/<handle>/<slug>.yaml`:

```yaml
id: 5d1f6c2e-3b7a-4e8d-9a41-0c2b7e9f1a63
title: PHP
color: blue
```

| Key | Required | What it does |
|---|---|---|
| `id` | Yes | A UUID. It identifies the term, so it must be unique across every taxonomy. |
| `title` | No | The term's title, up to 255 characters. Defaults to the slug. |

The filename, without `.yaml`, is the term's slug: letters, numbers, `-` and `_`. Any other keys are yours, and you read
them with [`field()`](query_api.md#taxonomies). The admin only saves the keys of the taxonomy's blueprint, and keeps the rest
as they were.

## Tagging entries

A collection opts in to the taxonomies it uses:

```yaml
title: Blog
dated: true
blueprints:
  - post
taxonomies:
  - tags
```

An entry holds the slugs of its terms in a front matter key named after the taxonomy:

```markdown
---
id: 0f8b4e2c-6a1d-4c3e-9b7a-2d5f1e8c4a90
title: Hello world
tags: [php, laravel]
---
```

The reindex reads that key for each taxonomy the collection lists, and fills `content_terms` with the terms and
`content_entry_terms` with which entries have which. A slug with no term file is ignored until there is one, and a
taxonomy the collection doesn't list is never read. Run `php spark content:reindex` after adding or editing term files
by hand, even in development, because the [automatic reindex](the_index.md#in-development) doesn't watch them.

To tag entries in the admin, give the blueprint a [`terms` field](field_types.md#terms):

```yaml
fields:
  - handle: tags
    type: terms
    taxonomy: tags
```

The form shows a checkbox for each term. Saving writes the slugs to the front matter and updates the entry's rows in the
index in the same request.

## Querying

`whereTerm()` keeps the published entries of a collection that have a term. See [Query API](query_api.md#taxonomies) for it, and
for listing a taxonomy's terms with their entry counts.

```php
$posts = content()->collection('blog')->whereTerm('tags', 'php')->get();
$tags  = content()->terms('tags'); // each with ->count
```

## Editing terms in the admin

A taxonomy gets an item in the **Content** menu, with a list of its terms and the number of published entries each has.
**New term** opens a form, and a term's title opens it for editing. The screens are at `/admin/taxonomies/<taxonomy>`, and
need `content.structure`: tags and categories change how the whole site is organized, so they are not left to those who
only write entries. See [Permissions](admin.md#permissions).

Without a blueprint, a term has a title and a slug. The slug names the file, so it can't change once the term exists. To
add fields, write a blueprint at `resources/blueprints/taxonomies/<taxonomy>.yaml`, in the same format as an
[entry blueprint](blueprints.md) with the [field types](field_types.md) that are saved in the front matter. A term file
has nowhere to keep a body, so a blueprint with a `body` field is refused.

Saving a term writes its file whole to a temporary file and renames it into place, then updates its row in the index. It
doesn't check whether the file changed since the form was opened, so the last save wins, and comments in the file are
dropped. A date written without quotes becomes a number when the term is saved, so quote dates. A term can't have the
slug `new`, which the admin uses for its form, and the reindex reports a file with that name.

Entries that were tagged with a slug before its term existed are linked to the term on the next reindex.

## Term pages

With [`$autoRoute`](routing.md#turning-it-on) on, a taxonomy with a `route` and a `template` gets a page for each term:

```yaml
title: Tags
route: /tags/{slug}
template: tags/show
```

`/tags/php` renders the template with:

| Variable | What it is |
|---|---|
| `$term` | The `Bonfire\Content\Term`, with `->title`, `->slug`, `->field()` and `->count`. |
| `$taxonomy` | The `Bonfire\Content\Taxonomy`, with `->title` and `->handle`. |
| `$entries` | The term's published entries from every collection, most recent first. |

```php
<h1><?= esc($term->title) ?></h1>

<?php foreach ($entries as $entry) : ?>
    <p><?= esc($entry->title) ?></p>
<?php endforeach ?>
```

A template is looked up like [a collection's](routing.md#templates). An unknown term gets a 404, and so does a request
that no taxonomy's route matches. If an entry has the same URI, the entry wins. Every request that no entry matches
reads the taxonomy configs to look for a term page, on top of the cost described under
[Turning it on](routing.md#turning-it-on). For a different page, such as one that paginates, skip the template and use
`whereTerm()` in a route of your own.
