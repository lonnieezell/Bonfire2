# Navigation menus

A navigation menu is a tree of links that you build by hand in the admin, and show on your front end with `navigation()`.
An item either points at an entry, so its title and URL follow the entry, or is a link to anywhere.

## Creating a menu

A menu has a config at `content/navigation/<handle>.yaml`:

```yaml
title: Main menu
max_depth: 2
collections:
  - pages
  - blog
```

| Key | Default | What it does |
|---|---|---|
| `title` | The handle | What the admin calls the menu. |
| `max_depth` | None | How many levels the menu can have, counting the top as the first. `max_depth: 1` means no nesting. Must be a whole number of at least 1. |
| `collections` | Every collection | The collections whose entries can be added to the menu. |

The config can be empty, and its file name is the menu's handle: `navigation('main')` reads `main.yaml`. The handle can
have letters, numbers, `-` and `_`.

## The tree file

The items live in `content/trees/navigation/<handle>.yaml`, apart from the config. It is a nested list. An item is
`entry: <id>`, with an optional `title` to use instead of the entry's, or `url` and `title`. Items below another go under
`children`:

```yaml
- entry: 0f8b4e2c-6a1d-4c3e-9b7a-2d5f1e8c4a90   # Home
- entry: 5c1e9a7b-3d24-4f68-8a0e-71b2c9d4e6f3   # About
  title: Who we are
  children:
    - entry: 9a3d7e51-2b86-4c0f-a1d9-4e8f6b2c7a15   # Team
- url: https://github.com/lonnieezell/Bonfire2
  title: Source
```

The ids are those in the entries' front matter, so an item keeps pointing at its entry when the entry's file is renamed.

A link's `url` is a path on your site (`/contact`), a fragment (`#top`), a query (`?page=2`), or an address that starts with
`http://`, `https://`, `mailto:` or `tel:`. Anything else, such as `javascript:`, is refused. A link needs a `title`.

An item can't have both an `entry` and a `url`. An item that isn't valid makes the file unreadable: the menu is empty on
your front end, the reason is logged, and the editor shows it and lets you save over the file.

## The menu editor

A user with `content.structure` sees each menu under **Content** in the admin sidebar, which opens the editor at
`/admin/navigation/<handle>`. It uses the same drag and drop list as the [tree editor](structure.md#the-tree-editor):
drag the handle of an item to reorder it, or onto another item's list to nest it. Then save.

- **Add an entry** picks from the entries of the menu's `collections`, drafts included, so you can build the menu before a
  page goes live. Type in the box on the item to show a title other than the entry's, or leave it empty to use the
  entry's.
- **Add a link** takes the text and the URL.
- **Remove** takes an item out, with the items below it.

Saving posts the whole menu. Bonfire then:

- refuses a menu deeper than `max_depth`, one that links an entry that doesn't exist or isn't in an allowed collection,
  and a link that isn't valid,
- refuses it if the tree file changed on disk since the editor was opened, the same hash check as the [tree
  editor](structure.md#the-tree-editor). Reload the menu and make your changes again, and
- writes the tree file in one step, through a temporary file that is renamed into place.

A refused save changes nothing. The editor drops items for entries that have since been deleted. Comments in the tree file
don't survive a save in the editor, and a menu you edit by hand isn't checked against `max_depth` or `collections`.

## Showing a menu

`navigation($handle)` returns the menu as a tree:

```php
$items = navigation('main');
// [
//     ['title' => 'Home', 'url' => '/home', 'active' => false, 'children' => []],
//     ['title' => 'Who we are', 'url' => '/about', 'active' => true, 'children' => [
//         ['title' => 'Team', 'url' => '/about/team', 'active' => false, 'children' => []],
//     ]],
// ]
```

`content()->navigation($handle, $currentPath)` does the same, and takes the path to mark active instead of the request's.
It throws `Bonfire\Content\ContentException` if the menu's config doesn't exist or is invalid.

| Key | Holds |
|---|---|
| `title` | The item's own title, or else the entry's. |
| `url` | The entry's URI, such as `/about/team`, or the link's `url` as it is in the file. `null` for an entry whose collection has no `route`. |
| `active` | Whether the item points at the page being shown. |
| `children` | The items below it, in the same shape. |

- **Entries are looked up each time**, so renaming an entry's slug changes the URL in the menu without touching the tree file.
  The index has to know about it: the admin updates it for you, and `php spark content:reindex` does after you rename a file.
- **Unpublished entries are skipped.** An item for a draft, a scheduled entry that isn't live yet, or an entry in the trash is
  left out, along with the items below it.
- **`active` compares paths.** It is `true` for a link on your site whose path is the current page's, ignoring the query and
  the fragment. A link to another site is never active, and an item isn't marked active because a child is.

## The sample partial

The App theme has a sample, `themes/App/_navigation.php`, which shows the menu `main` with Bootstrap's `nav`, the active
item with `active` and `aria-current="page"`, and nested items in an indented list. Include it in a layout:

```php
<?= $this->include('_navigation') ?>
```

Copy it to change the handle or the markup. Everything it prints is escaped.
