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
| `content.edit` | Creating and editing entries, using the Markdown preview and making [draft preview links](#previewing-a-draft). Also editing [globals](globals.md#editing-in-the-admin), which needs `content.access` too. |
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
[catches up with your files by itself](the_index.md#in-development).

## Search

Entries show up in the admin's search box, under **Content**. The search matches the term anywhere in an entry's title,
body or blocks, ignoring case, and lists the entry's title, collection and status. The body and blocks are searched as
plain text, so Markdown syntax such as `**bold**` doesn't count, and other front matter fields aren't searched. Fragments
are found here, unlike in [`content()->search()`](query_api.md#searching). Trashed entries are left out, and so is
everything for a user without `content.access`. The title links to the edit form for users with
`content.edit`, and is plain text for the others. It reads the index, the same as [the list](#the-list).

Content has no page that lists every collection, so the "see all" link under the results goes to the first collection's
list.

## Creating and editing

**New entry** opens the form of the collection's first blueprint. To use another blueprint the collection offers, add
`?blueprint=<handle>` to the address.

A new entry gets:

- An `id`, which is a new UUID.
- A filename from its slug. In a dated collection, the name starts with a date: the day of the publish date if you set
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

If you don't change `published_at`, it stays where it was: in the front matter, or in the filename of a dated
collection.

## The Markdown preview

A `markdown` field has **Write** and **Preview** tabs. Preview sends the text to the server, which renders it with
CommonMark and shows the result without saving. Raw HTML in the text shows as text in the preview. The preview only
answers requests from htmx, and needs `content.edit`.

## Editing blocks

A [`blocks` field](blocks.md) is a list of cards, one for each block, so a client can compose a page from the block types
you wrote without touching markup.

- **A card** shows the block type's `title` and a one-line summary, which is the value of the type's first `text` field.
  Click it to open the fields. Cards start closed, except one with an error, which opens by itself.
- **Move up** and **move down** swap a card with its neighbour, and the handle on the left drags it anywhere in the list.
- **Duplicate** adds a copy with the values as they are now, right below the card. **Delete** removes the card.
- **Add block** lists the types the field allows and adds a new card at the end. The menu is disabled once the list has
  `max_items` blocks.

Saving writes the blocks in the order the cards are in on the page. Nothing is saved until you press **Save**. If cards
were added, moved or deleted, **Discard** opens the page again to put them back.

A field with `max_items: 1` is a slot. It shows the block without the handle and the move and duplicate buttons. The
menu says **Choose a block** while the slot is empty and **Replace** after, and choosing a type replaces whatever is in
the slot. **Delete** empties it.

The form for a new block comes from the server when you add it, drawn by the same input partials as the rest of the
form. So an app's own copy of an input in `app/Views/fields/`, and a [custom field type](custom_field_types.md), work
inside a block with no JavaScript of your own. The Markdown preview tab inside a block previews the text as plain
Markdown, as [it does](#the-markdown-preview) everywhere else. The **Preview** link at the top of the entry shows the
page as the site renders it, blocks included.

### Errors in a block

A block that fails validation keeps what was typed, and its card opens with each problem labelled by the block's
position, counting from 1, and its type: `Block 3 (Feature grid): The Title field is required.` The first problem is also
shown under the field, and `max_items` and `min_items` are checked on the server when you save, whatever the page let
you do.

### A block type that is gone

If an entry has a block whose type no longer has a file in `resources/blueprints/blocks`, its card is read-only and says
``Unknown block type `x`, kept as is.`` You can move it or delete it. Saving writes it back as the file had it, with
every key it held. Values written as `1.0` come back as `1`, the way the index holds numbers.

The form signs what it keeps with the app's encryption key, so a post can't make up a block of a type that has no file.
Run `php spark key:generate` if your app has no key.

## Previewing a draft

**Preview** at the top of an entry's form gives you a link to the entry as it will look on the site, through the
collection's `template` or `controller`, whether it's a draft, scheduled or published. It needs `content.edit`, and a
collection with neither a template nor a controller doesn't offer it. The link doesn't need a `route` or `$autoRoute`.

The link is signed with the app's encryption key (run `php spark key:generate` if it has none) and works for 24 hours.

!!! warning "Anyone who has the link can open it without logging in"

    That's what lets you send it to a client to review a draft, so share it only with people who may see the draft. It
    keeps working when the entry is published, and stops only when it expires or the entry goes to the trash.

After it expires, or if its address is changed in any way, or it is used for another entry, it gets a 404. See
[Previews](routing.md#previews) for what the page gets.

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
