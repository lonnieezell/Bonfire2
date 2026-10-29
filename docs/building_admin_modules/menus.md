# Menus

The admin sidebar is a menu. Your module adds pages to it from its `Module::initAdmin()`, and it can build menus of its
own. This page lists every menu class, key and method. For where `initAdmin()` sits in a module, see
[Creating an Admin Module](creating_an_admin_module.md#adding-a-menu-item).

## How menus fit together

| Class | What it is | Get one with |
|---|---|---|
| `Bonfire\Menus\Manager` | Holds every menu by name. | `service('menus')` |
| `Bonfire\Menus\Menu` | A named menu, like `sidebar`. Holds collections. | `service('menus')->menu('sidebar')` |
| `Bonfire\Menus\MenuCollection` | A group of items, like the sidebar's Settings. | `->collection('settings')` |
| `Bonfire\Menus\MenuItem` | One link. | `new MenuItem([...])` |

Menus only exist on admin requests. Bonfire creates the built-in ones before it calls `initAdmin()`, so add items
there and nowhere else.

### Built-in menus

Bonfire creates two menus:

- `sidebar`, the admin's side navigation, with three collections:

    | Collection | Title | Shown as |
    |---|---|---|
    | `content` | Content | One sidebar link per item, with its icon. Also lists the items in the Quick Links dashboard widget. |
    | `settings` | Settings | One sidebar link (`fas fa-cog`) to the first item the user may see. The settings pages list the rest with `<x-settings-layout>`. |
    | `tools` | Tools | A collapsible group (`fas fa-toolbox`) of item titles. |

- `iconbar`, which is empty. The Admin theme doesn't render it.

Every item the user may see in a `sidebar` collection also appears in the ⌘K / Ctrl+K command palette.

## MenuItem

### Keys

Pass the keys as an array to the constructor. Each key calls the setter of the same name, in array order.

| Key | Type | Default | Setter | Effect |
|---|---|---|---|---|
| `title` | string | none | `setTitle()` | Text of the link. Required. The sidebar prints it without escaping, so escape text that comes from users. |
| `namedRoute` | string | none | `setNamedRoute()` | Sets the URL with `route_to()`. Required unless you pass `url`. It's also the key `$menuWeights` looks up. |
| `url` | string | none | `setUrl()` | Sets the URL directly. A URL without `://` gets a leading `/`. When you pass both `url` and `namedRoute`, the later one in the array wins. |
| `fontAwesomeIcon` | string | none | `setFontAwesomeIcon()` | [FontAwesome](https://fontawesome.com/) classes, like `fas fa-users`. |
| `iconUrl` | string | none | `setIconUrl()` | URL of an image icon. Used only when there's no `fontAwesomeIcon`. Currently broken, see [Limitations](#limitations). |
| `altText` | string | none | `setAltText()` | Stored, but the Admin theme doesn't use it. See [Limitations](#limitations). |
| `weight` | int | `0` | `setWeight()` | Sort order inside the collection. Larger weights appear later; equal weights sort by title. `$menuWeights` overrides it, see [Ordering](#ordering-with-menuweights). |
| `permission` | string | none | `setPermission()` | A [permission](permissions.md) the user needs to see the item. Leave it out to show the item to everyone. |

```php
use Bonfire\Menus\MenuItem;

$item = new MenuItem([
    'title'           => lang('Blog.posts'),
    'namedRoute'      => 'blog-posts',
    'fontAwesomeIcon' => 'fas fa-newspaper',
    'permission'      => 'blog.list',
    'weight'          => 10,
]);

service('menus')->menu('sidebar')->collection('content')->addItem($item);
```

Every setter returns the item, so you can build one fluently instead:

```php
$item = (new MenuItem())
    ->setTitle('Blog Settings')
    ->setUrl(ADMIN_AREA . '/settings/blog')
    ->setFontAwesomeIcon('fas fa-newspaper')
    ->setPermission('blog.settings');
```

An item built this way isn't matched by `$menuWeights`, because only the `namedRoute` key in the constructor registers
the route for it.

### Methods

| Method | Returns |
|---|---|
| `title()` | The title. |
| `url()` | The URL. |
| `altText()` | The alt text. |
| `weight()` | The weight, `0` when unset. |
| `userCanSee()` | `true` when the item has no permission or the logged-in user has it. |
| `icon(string $class = '')` | The icon tag: `<i class="…">` for FontAwesome, `<img>` for `iconUrl`, or `''`. `$class` adds classes to it. |

You can also read each of these methods as a property: `$item->title`, `$item->url`, `$item->icon`.

## MenuCollection

A `MenuCollection` is a `MenuItem` with a name and a list of items. It accepts the same keys and setters, so it can have
a title and an icon. A collection has no URL.

### Creating collections

Create collections on a `Menu`:

| Method | Does |
|---|---|
| `createCollection(string $name, string $title)` | Adds a new, empty collection and returns it. Doesn't check whether `$name` exists. |
| `collect(string $name, array $items)` | Adds `$items` to the collection called `$name`, creating it first (titled `ucfirst($name)`) if needed. Returns the collection. |
| `collection(string $name)` | The collection called `$name`, or `null`. |
| `collections()` | Every collection, in the order they were created. |

The sidebar shows collections in the order they were created, so a new one appears after Tools. This adds a
collapsible Blog group to the sidebar:

```php
$sidebar = service('menus')->menu('sidebar');

$sidebar->createCollection('blog', lang('Blog.title'))
    ->setFontAwesomeIcon('fas fa-newspaper')
    ->setCollapsible();

$sidebar->collect('blog', [
    new MenuItem(['title' => lang('Blog.posts'), 'namedRoute' => 'blog-posts', 'permission' => 'blog.list']),
    new MenuItem(['title' => lang('Blog.categories'), 'namedRoute' => 'blog-categories', 'permission' => 'blog.list']),
]);
```

### Options

| Method | Default | Effect |
|---|---|---|
| `setName(string $name)` | `''` | The name `collection()` finds it by. `isActive()` uses it too. |
| `setCollapsible(bool $collapse = true)` | `false` | Show the collection as a group that opens to list its item titles. |
| `setPageNav(bool $pageNav = true)` | `false` | Show one sidebar link to the first item the user may see instead of the items. Your pages then need their own nav. `<x-settings-layout>` provides it for `settings` only. Takes priority over `setCollapsible()`. |

Read them back with `name()`, `isCollapsible()` and `hasPageNav()`.

### Items

| Method | Does |
|---|---|
| `addItem(MenuItem $item)` | Adds one item. |
| `addItems(array $items)` | Adds several items. |
| `removeItem(string $title)` | Removes the first item with that title. |
| `removeAllItems()` | Empties the collection. |
| `items()` | The items, sorted by weight, then title. Includes items the user can't see. |
| `hasVisibleItems()` | `true` when the user may see at least one item. The sidebar skips collections without one. |
| `firstVisibleItem()` | The first item, by weight, the user may see, or `null`. |
| `isActive()` | `true` when the current URL starts with `ADMIN_AREA/{name}`. |

To take a built-in page out of the sidebar, remove it by its title:

```php
service('menus')->menu('sidebar')->collection('tools')->removeItem(lang('Tools.logsModTitle'));
```

## Menu

| Method | Does |
|---|---|
| `addItem(MenuItem $item)` | Adds an item outside any collection. Nothing renders these, see [Limitations](#limitations). |
| `items()` | Every top-level item and collection, in the order added. |
| `activeItem()` | The visible item whose URL is the longest match for the start of the current URL, or `null`. |
| `activeTrail()` | The titles for the breadcrumb: the collection's title (for collapsible collections) and the active item's title. Empty when nothing matches. |

The collection methods are listed under [Creating collections](#creating-collections).

## Custom menus

| Manager method | Does |
|---|---|
| `createMenu(string $name)` | Creates an empty menu called `$name` and returns the manager. Replaces a menu that has the same name, so never call it for `sidebar`. |
| `menu(string $name)` | The menu called `$name`, created if it doesn't exist. |

The Admin theme only renders `sidebar`, so you render a custom menu yourself. Build it in `initAdmin()`:

```php
$reports = service('menus')->menu('reports');

$reports->collect('sales', [
    new MenuItem(['title' => 'By month', 'namedRoute' => 'reports-monthly', 'permission' => 'reports.view']),
    new MenuItem(['title' => 'By region', 'namedRoute' => 'reports-region', 'permission' => 'reports.view']),
]);
```

Then loop over it in a view:

```php
<?php $reports = service('menus')->menu('reports') ?>
<?php foreach ($reports->collections() as $collection) : ?>
    <?php if (! $collection->hasVisibleItems()) {
        continue;
    } ?>
    <h3><?= esc($collection->title) ?></h3>
    <?php foreach ($collection->items() as $item) : ?>
        <?php if ($item->userCanSee()) : ?>
            <a href="<?= site_url($item->url) ?>" <?= $item === $reports->activeItem() ? 'aria-current="page"' : '' ?>>
                <?= $item->icon ?> <?= esc($item->title) ?>
            </a>
        <?php endif ?>
    <?php endforeach ?>
<?php endforeach ?>
```

## Ordering with `$menuWeights`

`$menuWeights` in `app/Config/Bonfire.php` sets weights by named route, so you can reorder items without editing the
modules that add them:

```php
public $menuWeights = [
    'user-list'   => 1,
    'blog-posts'  => 2,
    'recycler'    => 1,
    'sys-info'    => 2,
];
```

- A listed route's weight replaces the item's `weight`.
- An item matches only when the `namedRoute` key was passed to its constructor.
- Weights only sort items within their collection. They can't reorder collections, so Content, Settings and Tools stay
  where they are.
- Bonfire reads the value with `setting('Bonfire.menuWeights')`, so a value saved through the settings library
  replaces the config.

The [Configuration Reference](../intro/configuration.md#bonfire) lists the key with Bonfire's other settings.

## The Admin theme

The rendering described on this page is the stock Admin theme's: `Components/sidebar.php`, `_palette.php` and
`_header.php`. Your install has its own copy of the theme, which you may have changed. See
[Why Themes Are Published Into Your App](../intro/published_themes.md).

## Limitations

- `iconUrl` icons don't appear. The tag uses `href` instead of `src` ([#727](https://github.com/lonnieezell/Bonfire2/issues/727)).
- `altText` isn't rendered anywhere ([#728](https://github.com/lonnieezell/Bonfire2/issues/728)).
- Items added with `Menu::addItem()` don't appear in the sidebar, the palette or the breadcrumb. Put every item in a
  collection ([#729](https://github.com/lonnieezell/Bonfire2/issues/729)).
- Collapsible groups list item titles only, without icons.
