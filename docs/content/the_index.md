# The index and deploys

The database holds an **index** of your content files: a row for each entry and term, with the columns a query needs,
such as the status, the date and the URI. [Querying](query_api.md), [routing](routing.md), the admin's lists and
[navigation menus](navigation.md) read the index, not the files. This page covers how to build it, and when it has to be
rebuilt.

## Building the index

Run the migrations once to create the index tables. `php spark bf:install` already does this for a new install:

```bash
php spark migrate --all
```

Then index your files, and again whenever they change, such as when you deploy:

```bash
php spark content:reindex
```

The command prints how many entry rows it added, updated and removed, and how many it left alone. It reads every file, so
a change to a collection's config reaches its entries, but it only writes rows that differ. It also updates the terms of
the [taxonomies](taxonomies.md), and which entries have each term, to match the files.

If the content root has no `collections` folder, such as when shared storage isn't mounted, or the index can't be
written, the index stays as it was and the command exits with an error code.

A file that can't be indexed is listed with the reason, and the rest are still indexed. This doesn't count as a failed
run, so check the output. A file that breaks after it was indexed loses its row until you fix it.

To empty the index and rebuild it:

```bash
php spark content:reindex --fresh
```

Use `--fresh` if the index looks wrong and you want to be sure it matches the files. It is safe, because the index holds
nothing that isn't in the files, and it runs in one transaction. It leaves [`redirects.yaml`](routing.md#redirects) alone.

## When to reindex

Run `php spark content:reindex`:

- **On every deploy.** Make it a step of your deploy, after the migrations and after the new files are in place. This is
  the one that matters. Without it, a changed file or a collection's new `route` isn't served until someone runs it. Let
  the deploy stop when the command exits with an error, as it does when the content root is missing, so you don't ship a
  site that serves nothing.
- **After you add or edit files by hand outside `development`**, such as with `git pull`. A save in the admin updates the
  index by itself.
- **After you change a collection's config**, such as its `route`, `sort` or `taxonomies`, because it is applied to the
  entries when they are indexed.
- **After you edit [term](taxonomies.md#terms) or [tree](structure.md#the-tree-file) files by hand**, even in
  `development`. The [automatic reindex](#in-development) doesn't watch them.
- **After you restore the database from a backup**, or create the index tables on a site that already has content files,
  because the index won't match the files.

In production, a changed file or `route` has no effect until you reindex. If a collection's config is missing or broken
by then, its entries get a 404 and the reason is logged.

## In development

When `ENVIRONMENT` is `development`, the first `content()` call in a request checks whether any file under
`content/collections/` has changed, or the index has lost rows, and reindexes if so. You can edit a file and refresh the
page. The fallback route makes that call for you. A file that can't be indexed is written to the log, and loses its row
until you fix it.

It only watches `content/collections/` and `content/.trash/`. After you edit a term file or a
[tree file](structure.md#the-tree-file) by hand, run `php spark content:reindex`.
