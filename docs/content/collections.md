# Collections

A collection is a group of entries, like a blog. It has a config file at `content/collections/<handle>.yaml` and keeps
its entries in `content/collections/<handle>/`. Handles use letters, numbers, `-` and `_`. Subfolders aren't read.

## Collection config

```yaml
title: Blog
dated: true
sort: published_at desc
blueprints:
  - post
```

| Key | Default | What it does |
|---|---|---|
| `title` | The handle | The collection's name. |
| `dated` | `false` | Whether entries are dated. See [Filenames](#filenames). |
| `sort` | `published_at desc` if dated, otherwise `title asc` | The default order: `title`, `slug` or `published_at`, then `asc` or `desc`. |
| `blueprints` | None | The blueprints its entries can use. The first is the default. |

## Entries

An entry is a Markdown file that starts with a YAML front matter block:

```markdown
---
id: 0f8b4e2c-6a1d-4c3e-9b7a-2d5f1e8c4a90
title: Hello world
tagline: Where it starts
---
The *Markdown* body.
```

| Key | Required | What it does |
|---|---|---|
| `id` | Yes | A UUID. It identifies the entry across renames and moves, so it must be unique. |
| `title` | No | The entry's title, up to 255 characters. Defaults to the slug. |
| `status` | No | `draft` or `published`. Defaults to `published`. |
| `published_at` | No | When the entry goes live, such as `2026-10-27 09:00:00`. It's read in your app's timezone unless it has an offset (`+02:00`). Overrides the date in the filename. |
| `blueprint` | No | Overrides the collection's default blueprint. |

Any other keys are yours, and you read them with [`field()`](query_api.md#entries).

## Filenames

The filename gives the entry its slug. In a dated collection, `2026-10-27.hello-world.md` has the slug `hello-world` and
is published from midnight on 27 October 2026. A dated entry needs a date in its filename or a `published_at`, and is
reported by the reindex without one. In any other collection, the slug is the whole name, without `.md`.
