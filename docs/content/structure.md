# Pages and structures

A collection with a **structure** keeps its entries in a tree, such as the pages of a site. Each entry has a place in
the order and a parent, and its URI follows its parents: the page `team` under `about` is served at `/about/team`.

## Turning it on

Add `structure: true` to the collection's config, and a `route` so the entries have URIs:

```yaml
title: Pages
structure: true
max_depth: 3
route: /{slug}
blueprints:
  - page
template: page
```

| Key | Default | What it does |
|---|---|---|
| `structure` | `false` | Whether the collection has a tree. |
| `max_depth` | None | How many levels the tree can have, counting the root as the first. `max_depth: 1` means no nesting. Must be a whole number of at least 1. |
| `route` | None | Where the URIs start. `{slug}` stands for the entry's slug and those of its parents, so `/{slug}` gives `/about/team` and `/docs/{slug}` gives `/docs/about/team`. Without a `route`, entries have a place in the tree but no URI. |

Run `php spark content:reindex` afterwards, because an entry's URI is stored in the index.

## The tree file

The order and nesting live in `content/trees/collections/<handle>.yaml`, apart from the entry files. It is a nested list
of entry ids, with the entries below another under `children`:

```yaml
- id: 0f8b4e2c-6a1d-4c3e-9b7a-2d5f1e8c4a90   # home
- id: 5c1e9a7b-3d24-4f68-8a0e-71b2c9d4e6f3   # about
  children:
    - id: 9a3d7e51-2b86-4c0f-a1d9-4e8f6b2c7a15   # team
    - id: e2f40b68-9c37-4d15-b8a2-6d1c3f7e9054   # history
```

The ids are those in the entries' front matter, so an entry keeps its place when you rename or move its file. The
reindex reads the file and writes three columns for each entry:

- `parent_id`, the id of the entry above it, or null at the root.
- `sort`, its position among its siblings, from 0.
- `uri`, built from the slugs of the entry and its parents, such as `/about/team`.

The tree file is optional and forgiving:

- An entry the file doesn't name is still indexed, at the end of the root, in the order of its file path. Nothing is lost
  by adding an entry file by hand.
- An id with no entry file is skipped, and its children move up to where it was.
- An id named twice keeps its first place.
- A file that isn't valid is reported by the reindex, and every entry goes to the root until you fix it.

The reindex never writes the tree file. The admin does.

### Serving nested URIs

With [routing](routing.md) on, `/about/team` finds the entry with that URI in one index lookup, like any other URI.
`content()->findByUri('/about/team')` does too. Give the collection a `template` or `controller` to render it.

Two entries can't share a URI, so the slug of each entry in a structured collection must be unique within it.

### Changing a slug or moving a page

Rename an entry's file, or move it in the tree, and the next reindex or tree save recomputes the URI of the entry and of
every entry below it.

!!! warning "There are no redirects"

    In this version, the old URI of a page that was renamed or moved is not redirected. It answers with a 404, and so do
    the URIs of its descendants. Add redirects of your own if the old addresses matter.

## The tree editor

A user with `content.structure` sees **Edit structure** on the collection's list, which opens the editor at
`/admin/content/<collection>/structure`. It shows the entries as a nested list. Drag the handle of a page to reorder it,
or onto another page's list to nest it, then save. The editor uses SortableJS, which it loads from a CDN.

Saving posts the whole tree. Bonfire then:

- refuses a tree deeper than `max_depth`, one that names an entry the collection doesn't have, or one that would give
  two entries the same URI, such as with an entry of another collection,
- refuses it if the tree file changed on disk since the editor was opened, the same hash check as [entry
  forms](admin.md#when-the-file-changed). Reload the structure and make your changes again,
- writes the tree file in one step, through a temporary file that is renamed into place, and
- updates `parent_id`, `sort` and `uri` of every entry that moved.

A refused save changes nothing. The depth limit is checked when you save in the editor. A tree file you edit by hand
isn't checked, so a deeper one is indexed as written. Comments in the tree file don't survive a save in the editor.

The editor and the tree file are checked against the index, not the entry files. If you add entry files or edit the tree
file by hand, run `php spark content:reindex` before you use the editor, or a save can leave out what the index doesn't
know yet.

### New entries

A new entry made in the admin goes last at the root. The tree file is rewritten to include it, along with any entries it
didn't name yet. Open the editor to put it elsewhere. Editing an entry keeps its place.
