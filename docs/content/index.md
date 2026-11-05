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
out of date, `php spark content:reindex` rebuilds it from the files, and loses nothing.

So the files are for people and the index is for the site. If they differ, the files are right.

You can edit entries in the admin, where each collection has a list and a form built from its blueprint: see
[Editing entries](admin.md), [Blueprints](blueprints.md) and [Field types](field_types.md). To serve entries as pages,
see [Routing](routing.md). To keep pages in a tree with nested URIs, see [Pages and structures](structure.md).

## File layout

Everything lives under the content root, which is `content/` in your project by default.

```
content/
├── collections/
│   ├── blog.yaml                       the blog collection's config
│   └── blog/
│       ├── 2026-10-27.hello-world.md   an entry
│       └── 2026-11-01.second-post.md
└── taxonomies/
    ├── tags.yaml                       the tags taxonomy's config
    └── tags/
        └── php.yaml                    a term
```

A structured collection also has a tree file, at `content/trees/collections/<handle>.yaml`. See
[Pages and structures](structure.md#the-tree-file). A [navigation menu](navigation.md) has a config at
`content/navigation/<handle>.yaml` and its items at `content/trees/navigation/<handle>.yaml`.

Blueprints are not in the content root. They live at `resources/blueprints/`.

Change the locations with `$contentPath` and `$blueprintPath` in `app/Config/Content.php`. See
[Collections](collections.md) for the config file and the entry format, [Blueprints](blueprints.md) for the forms, and
[Taxonomies](taxonomies.md) for tags and categories.

## Building the index

Run the migrations once to create the index tables:

```bash
php spark migrate --all
```

Then index your files, and again whenever they change, such as when you deploy:

```bash
php spark content:reindex
```

The command prints how many entry rows it added, updated and removed, and how many it left alone. It reads every file, so a
change to a collection's config reaches its entries, but it only writes rows that differ. It also brings the
[taxonomies](taxonomies.md)' terms, and which entries have them, in line with the files.

If the content root has no `collections` folder, such as when shared storage isn't mounted, or the index can't be
written, the index stays as it was and the command exits with an error code.

A file that can't be indexed is listed with the reason, and the rest are still indexed. This doesn't count as a failed
run, so check the output. A file that breaks after it was indexed loses its row until you fix it.

To empty the index and rebuild it:

```bash
php spark content:reindex --fresh
```

### When to reindex

Run `php spark content:reindex`:

- **On every deploy.** Make it a step of your deploy, after the migrations and after the new files are in place. This is
  the one that matters. Without it, a changed file or a collection's new `route` isn't served until someone runs it.
- After you add or edit files by hand outside `development`, such as with `git pull`. A save in the admin updates the
  index by itself.
- After you change a collection's config, such as its `route`, `sort` or `taxonomies`, because it is applied to the
  entries when they are indexed.
- After you add [term](taxonomies.md#tagging-entries) files by hand. The automatic reindex in development doesn't watch them.
- After you restore the database from a backup, or create the index tables on a site that already has content files,
  because the index won't match the files.

Use `--fresh` if the index looks wrong and you want to be sure it matches the files. It is safe, because the index holds
nothing that isn't in the files, and it runs in one transaction.

In `development`, the first `content()` call in a request reindexes by itself if a file under `content/collections/`
changed. See [Seeing changes](routing.md#seeing-changes). Let your deploy stop when the command exits with an error, as it does
when the content root is missing, so you don't ship a site that serves nothing.
