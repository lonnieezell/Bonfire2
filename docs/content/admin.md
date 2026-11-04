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
| `content.delete` | Reserved. Nothing uses it yet. |
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
