# Constraints and known gaps

Content is young. These are the limits you should know about before you build on it, and what to do about each.

## The content directory must be on one machine

Entries are files, and the admin writes them where the request lands. Bonfire doesn't copy a file to other servers, and
the lock that stops two saves overwriting each other is a file in `writable/cache` on that one server.

So run Content on one server, or put the content directory on storage that every server shares, such as a network
volume. Set `$contentPath` in `app/Config/Content.php` to it. Otherwise:

- A post saved on server A isn't on server B, so B answers it with a 404.
- Both servers write the same shared index tables from different files, and each reindex removes the other's rows.

The index is in your database, which all servers share. Only the files are local. If you build your servers from an image
or deploy a fresh copy of the code, the files in it are the ones that were committed, and a post saved in the admin since
isn't among them. See [Git isn't automated](#git-isnt-automated).

## Changing a slug doesn't redirect

When you rename an entry's file, or move a page in a [structure](structure.md#changing-a-slug-or-moving-a-page), its URI
changes, and the old URI answers with a 404. The URIs of a page's children change with it. Nothing remembers the old
ones.

If the old addresses matter, such as for search engines, add redirects of your own with `$routes->addRedirect()` or in
your web server, before you rename. The admin can't rename an entry, because the slug is the file's name. See
[Editing entries](admin.md#creating-and-editing).

## Saving in the admin drops YAML comments

The admin writes an entry's front matter afresh from its values, so comments in it are lost on save. Keys keep their
order, and keys the blueprint doesn't know keep their values. The same goes for a [term](taxonomies.md#editing-terms-in-the-admin)
saved in the admin and for a collection's [tree file](structure.md#the-tree-editor). The body of an entry is kept as it
was unless the blueprint has a `body` field.

A collection's config file, `content/collections/<handle>.yaml`, is never written by Bonfire, so its comments are safe.
If you keep notes for your team in the front matter of entries, put them in the Markdown body, or in a field of the
blueprint.

## Git isn't automated

Bonfire doesn't commit, pull or push. A save in the admin changes a file in the working directory of the server it ran on, and
that is all. Nobody sees the change in version control until someone commits it.

This matters most when you edit on a production server: the next deploy of your code can overwrite what the admin saved,
or fail on a conflict. Pick one place where content is edited, and move it from there. A few ways:

- Edit in the admin on your development machine, commit the files, and deploy them with the code.
- Edit in the admin on production, and have a job of your own commit the content directory and push it.
- Don't allow editing in the admin on production, by taking `content.edit` away from every group there. See
  [Permissions](admin.md#permissions).

Either way, run `php spark content:reindex` after the files change. See [Building the index](index.md#when-to-reindex).

## There are no permissions for one collection

The `content.*` permissions apply to every collection, and to the taxonomies' terms too. You can't let someone edit the
blog but not the pages. The same goes for `content.structure`, which also covers every taxonomy. See
[Permissions](admin.md#permissions).

Anyone you give `content.edit` can edit every collection. Give it only to people you trust with all of Content.

## Smaller things

These are covered where they apply, and are listed here so you can find them:

- **Entries can't be deleted in the admin.** `content.delete` is reserved and nothing uses it yet. Delete the file, then
  run `php spark content:reindex`.
- **Terms have no stale-file check.** If you save a term whose file changed since the form opened, the last save wins.
  Entries do check. See [When the file changed](admin.md#when-the-file-changed) and
  [Editing terms in the admin](taxonomies.md#editing-terms-in-the-admin).
- **The index doesn't follow your files by itself outside `development`.** Reindex when you deploy.
  See [Building the index](index.md#building-the-index).
- **The tree editor loads SortableJS from a CDN**, so it needs the browser to reach it. See
  [The tree editor](structure.md#the-tree-editor).
