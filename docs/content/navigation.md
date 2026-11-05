# Navigation menus

A navigation menu is a list of links for your site's header or footer, built by hand in the admin. Its items link to
entries or to any URL, and can be nested. An item that links to an entry takes its URL from the entry, so renaming the
entry's slug or moving it in a tree changes the menu without editing it.

## Creating a menu

A menu needs a config file at `content/navigation/<handle>.yaml`:

```yaml
title: Main menu
max_depth: 2
collections:
  - pages
  - blog
```

| Key | Default | What it does |
|---|---|---|
| `title` | The handle | The menu's name in the admin. |
| `max_depth` | None | How many levels the menu can have, counting the root as the first. The editor refuses a deeper one. Must be a whole number of at least 1. |
| `collections` | None | The collections whose entries it can link to. Without any, the menu can only have links you type in. |

Handles use letters, numbers, `-` and `_`.

## The tree file

The items live in `content/trees/navigation/<handle>.yaml`, which the admin writes. You can also write it by hand. Each
item has either an `entry`, which is the entry's id, or a `url` and a `title`. Either can have `children`:

```yaml
- entry: 0f8b4e2c-6a1d-4c3e-9b7a-2d5f1e8c4a90   # home
  title: Start                                  # optional, and the entry's title otherwise
- entry: 5c1e9a7b-3d24-4f68-8a0e-71b2c9d4e6f3   # about
  children:
    - url: /contact
      title: Contact
- url: https://example.org/docs
  title: Docs
```

A `url` must start with `/`, `#`, `?`, `http:`, `https:`, `mailto:` or `tel:`. A path that starts with `/` is on your site.

Unlike the [tree of a structured collection](structure.md#the-tree-file), a menu can link to an entry more than once, and
it doesn't have to name every entry.

## Editing a menu in the admin

Each menu has an item under **Content** in the sidebar, for users with `content.structure`. It opens the editor at
`/admin/navigation/<handle>`.

- Pick an entry and choose **Add entry**, or type a title and a URL and choose **Add link**. New items go last at the root.
- Drag an item by its handle to reorder it, or onto another item's list to nest it.
- Type in the box next to an entry to give it a title of its own in this menu. Leave it empty to use the entry's title.
- Remove an item with its **×** button. The items below it go with it.

The picker offers the entries of the menu's `collections` that have a URI, drafts included, so a menu can be ready before
the page goes live. A collection needs a `route` for its entries to have one.

Saving writes the whole file. Bonfire refuses a menu deeper than `max_depth`, a link with no title or a URL it doesn't
accept, and an entry outside the menu's `collections`. It also refuses to save if the file changed on disk since you opened
the editor, the same hash check as [entry forms](admin.md#when-the-file-changed). A refused save changes nothing. Comments
in the tree file don't survive a save.

The editor marks items that won't show on the site: drafts, scheduled entries, entries in the trash, entries with no URI,
and ids the index doesn't have. Remove an item whose entry isn't found before you save, because the save refuses it. An
entry in the trash can stay.

**Discard** on the save bar opens the menu again as it was last saved, so it undoes everything since then.

## Showing a menu

`navigation('main')` returns the menu as a list of `Bonfire\Content\NavigationItem`:

```php
foreach (navigation('main') as $item) {
    $item->title;     // 'About'
    $item->url;       // 'https://example.com/about'
    $item->active;    // true on the page at this URL
    $item->children;  // list<NavigationItem>
}
```

It works anywhere, with `$autoRoute` on or off. See [Routing](routing.md).

- **Skipped items.** An item is left out, along with everything below it, if its entry is a draft, is scheduled for later,
  is in the trash, isn't in the index, or has no URI because its collection has no `route`.
- **`active`** is true only for the items whose path is the path of the current request, ignoring any query or fragment.
  A parent of the current page isn't active, and neither is a link with a full URL, even one to your own site. Use a path
  for links to your own pages.
- **An unknown menu** throws `ContentException`, as does a config file that isn't valid.
- **A tree file that isn't valid** gives an empty menu and logs an error, so a bad edit doesn't take down every page.

Menus read the index, so what they show follows your files after the [index is updated](the_index.md).

### The sample partial

The App theme has a sample at `themes/App/_navigation.php`. Include it from a view in the theme:

```php
<?= $this->include('_navigation') ?>
```

It shows the `main` menu as nested lists and marks the current page with `aria-current="page"`. It's a starting point with
no styling, so copy it and change the menu's handle and the markup to suit your site.

!!! note "Upgrading"

    If you published the App theme before this partial was added, copy it from
    `vendor/lonnieezell/bonfire/themes/App/_navigation.php`.
