# Content

Content lets you keep pages and posts as Markdown and YAML files in your repository, and query them from your own
controllers and views. The files are the source of truth. The database holds an index that you can always rebuild.

You can edit entries in the admin, where each collection has a list and a form built from its blueprint: see
[Editing entries](admin.md), [Blueprints](blueprints.md) and [Field types](field_types.md). To serve entries as pages,
see [Routing](routing.md). To keep pages in a tree with nested URIs, see [Pages and structures](structure.md).

## File layout

Everything lives under the content root, which is `content/` in your project by default.

```
content/
└── collections/
    ├── blog.yaml                       the blog collection's config
    └── blog/
        ├── 2026-10-27.hello-world.md   an entry
        └── 2026-11-01.second-post.md
```

Change the location with `$contentPath` in `app/Config/Content.php`. See [Collections](collections.md) for the config
file and the entry format.

## Building the index

Run the migrations once to create the index tables:

```bash
php spark migrate --all
```

Then index your files, and again whenever they change, such as when you deploy:

```bash
php spark content:reindex
```

The command prints how many rows it added, updated and removed, and how many it left alone. It reads every file, so a
change to a collection's config reaches its entries, but it only writes rows that differ.

If the content root has no `collections` folder, such as when shared storage isn't mounted, or the index can't be
written, the index stays as it was and the command exits with an error code.

A file that can't be indexed is listed with the reason, and the rest are still indexed. This doesn't count as a failed
run, so check the output. A file that breaks after it was indexed loses its row until you fix it.

To empty the index and rebuild it:

```bash
php spark content:reindex --fresh
```
