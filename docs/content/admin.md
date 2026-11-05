# Editing entries in the admin

Content has an admin screen for each collection. It lists the collection's entries and gives you a form for each one,
built from the entry's [blueprint](blueprints.md). Saving writes the entry's Markdown file, so the files stay the source
of truth.

Open a collection from **Content** in the sidebar, which has an item for each collection in `content/collections/`. The
screens are at `/admin/content/<collection>`.

## Permissions

| Permission | What it allows |
|---|---|
| `content.access` | Seeing the Content menu and each collection's list. Every other entry screen needs it too. |
| `content.edit` | Creating and editing entries, and the Markdown preview. |
| `content.publish` | Publishing an entry, and setting or changing when it goes live. |
| `content.delete` | Moving an entry to the trash. See [Deleting and restoring](#deleting-and-restoring). Restoring and purging need `recycler.view` instead. |
| `content.structure` | Arranging the entries of a structured collection in its tree editor (see [Pages and structures](structure.md)), and listing, creating and editing the terms of a [taxonomy](taxonomies.md#editing-terms-in-the-admin). Neither needs `content.access`. |

`superadmin` and `admin` have all of them. A user without `content.access` doesn't see the menu, and the routes send them
back to the dashboard with a "not authorized" message.

You publish your own `AuthGroups`, so after updating **run `php spark bonfire:permissions`**, which grants `content.*` to
`superadmin` and `admin`. Until you do, only the groups whose matrix already has `content.*` see Content. You can give
`content.edit` without `content.publish`, to let someone write drafts that someone else publishes. See
[Permissions](../building_admin_modules/permissions.md).

## The list

The list shows each entry's title, status and date, a page at a time. It reads the index, so run
`php spark content:reindex` first if you have files that were not saved through the admin. In development the index
catches up with your files by itself.

## Search

Entries show up in the admin's search box, under **Content**. The search matches the term anywhere in an entry's title
or in its Markdown body, ignoring case, and lists the entry's title, collection and status. The body is searched as it
was written, so Markdown syntax such as `**bold**` counts, but front matter fields do not. Trashed entries are left out,
and so is everything for a user without `content.access`. The title links to the edit form for users with
`content.edit`, and is plain text for the others. It reads the index, the same as [the list](#the-list).

Content has no page that lists every collection, so the "see all" link under the results goes to the first collection's
list.

## Creating and editing

**New entry** opens the form of the collection's first blueprint. To use another blueprint the collection offers, add
`?blueprint=<handle>` to the address.

A new entry gets:

- An `id`, which is a new UUID.
- A file name from its slug. In a dated collection, the name starts with a date: the day of the publish date if you set
  one, otherwise today. The slug is made from the title if you leave it empty, and can't change once the entry exists,
  because it is the file's name.
- The status `draft`, unless the user can publish.

The form shows what is in the file now, not what the index says, so a file edited in your editor shows up as it is.
Saving writes the whole file to a temporary file and renames it into place, so nothing reads half an entry, and then
updates the entry's row in the index.

When you save an entry:

- Keys in the front matter that the blueprint doesn't know stay as they were, with their values, and so does the body if
  the blueprint has no `body` field.
- Comments in the front matter are dropped, because the YAML is written afresh. Keys keep their order.
- A date written without quotes, such as `2026-10-27`, is saved back as `2026-10-27 00:00:00` in your app's timezone, the
  same value the index holds. Quote a date to keep it exactly as you wrote it.
- Line breaks you type are saved as `\n`, even though the browser posts `\r\n`.

If the form doesn't validate, it shows again with what you typed and an error under each field that failed. Nothing is
written.

### When the file changed

The form carries a hash of the file it loaded. If the file on disk is different when you save, because someone else
saved it or you edited it by hand, Bonfire refuses the save with a message and a link to reload the entry. Your input
stays in the form, and the file is left as the other change made it. Make your changes again from the reloaded entry.

This guards against overwriting a change, but it doesn't merge two.

## Status and publishing

An entry is a `draft` or `published`. A published entry is only live once its `published_at` has passed.

Publishing needs `content.publish`:

- Changing an entry to published.
- Setting `published_at`, or changing it.

A user without it can still write drafts, edit entries that are already published, and change a published entry back to
a draft. The status and publish date inputs are disabled for them, and a request that posts them anyway is refused with a
403 and nothing is saved. An entry they create is a draft.

If you don't change `published_at`, it stays where it was: in the front matter, or in the file name of a dated
collection.

## The Markdown preview

A `markdown` field has **Write** and **Preview** tabs. Preview sends the text to the server, which renders it with
CommonMark and shows the result without saving. Raw HTML in the text shows as text in the preview. The preview only
answers requests from htmx, and needs `content.edit`.

## Deleting and restoring

A user with `content.delete` sees **Delete** on each row of the list and at the top of the entry's form. It asks first.

Deleting moves the file to `content/.trash/<collection>/<filename>` as it is on disk, and marks the entry's row as
deleted. The entry leaves the list, the front end, queries, navigation menus and `entries` fields. Someone who still has
the form open gets the [conflict message](#when-the-file-changed) when they save. If `.trash/` already holds a file with
that name, the delete is refused and nothing is overwritten.

Trashed entries are listed in the Recycler under **Tools**, as **Content entries**. Restoring and purging there need only
`recycler.view`, like the Recycler's other resources.

- **Restore** moves the file back and the entry reappears. It's refused, with a message saying why, if its original path
  or URI has been taken, or its file is missing from the trash.
- **Purge** deletes the file and the row for good. If the file is already gone, it just clears the row.

A trashed entry keeps its slug and URI, so a new entry can't take them until you restore or purge it. For what deleting
does to pages in a tree, see [Pages and structures](structure.md#deleting-and-restoring-pages).

### The trash and git

`.trash/` is ordinary files under your content directory. You probably don't want them in version control, so add this
to your `.gitignore`, adjusting the path if your content lives elsewhere:

```
content/.trash/
```

The consequence is that the Recycler's contents belong to one machine. After a fresh clone and a reindex, the entries
that were in that machine's trash aren't there. The reindex treats the location of the file as the truth:

| On disk | Result of a reindex |
|---|---|
| Only in `.trash/` | The entry is trashed, even if it was live. |
| Only in `collections/` | The entry is live, even if it was trashed. |
| The same id in both | The live one wins. The trash copy is reported as an error and left alone. |
| In neither | The row is removed. |
| In `.trash/`, but its collection's config is gone | Reported, with no row. It comes back when the config does. |

After `content:reindex --fresh`, the date an entry was deleted comes from the time on its file in `.trash/`.
