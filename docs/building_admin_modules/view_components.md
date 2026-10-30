# View Components

View Components (or just Components) allow you to create custom HTML elements to use within your views. The main 
description of their use and creating new ones is described within the `Building Sites` section of the guide. This
page lists the components that are available within the default `Admin` theme. 

Your app renders its own copy of these components, in `themes/Admin/Components/`, which `composer update` doesn't
change. See [Why Themes Are Published Into Your App](../intro/published_themes.md).

Escape attribute values with `esc()` (`title="<?= esc($user->name()) ?>"`), as you would any HTML attribute. The
renderer decodes them and the component escapes them again on output, so a quote in the data can't end the attribute
early. On a paired tag (`<x-…>…</x-…>`) a value can't contain `>`. Slots are HTML and are output as given.

## Page structure

**<x-page-head\>**

The page header. Give it a `title` and it renders the design's head: an `<h1>`, an optional `count` badge or
`subtitle`, an optional `back` link (`backLabel` names it, defaulting to "Back"), and the slot as actions on the right.

```php
<x-page-head title="Users" count="<?= $total ?>">
    <a href="<?= url_to('user-new') ?>" class="btn btn-primary">New user</a>
</x-page-head>

<x-page-head title="<?= esc($user->displayName()) ?>" back="<?= esc(site_url(ADMIN_AREA . '/users')) ?>" backLabel="<?= esc(lang('Users.usersModTitle')) ?>" />
```

Without a `title` it wraps the slot as before, so existing `<x-page-head><h2>…</h2></x-page-head>` headers keep working.

**<x-admin-box\>**

A card section. The optional `title` and `description` render a header. Setting rows and tables inside it run edge to
edge; put buttons in a `<div class="bf-card-footer">` as the last child for a footer.

```php
<x-admin-box title="Site" description="How the site presents itself.">
    …
</x-admin-box>
```

**<x-tabs\>**

Underline tabs. The slot holds Bootstrap `nav-item` list items, so `service('resourceTabs')->renderTabsFor()` output
fits in it.

```php
<x-tabs>
    <li class="nav-item"><a class="nav-link active" href="<?= $user->adminLink() ?>">Details</a></li>
    <?= service('resourceTabs')->renderTabsFor('user') ?>
</x-tabs>
```

**<x-settings-layout\>**

The frame of a settings page: a "Settings" page head, a sub-nav of the sidebar's `settings` collection (only the pages
the user may open, the current one marked), and the slot beside it. Register your module's settings page in that
collection and wrap the page in it.

```php
<x-settings-layout>
    <form action="<?= site_url(ADMIN_AREA . '/settings/blog') ?>" method="post">
        <?= csrf_field() ?>
        <x-admin-box title="Posts">…</x-admin-box>
        <x-save-bar />
    </form>
</x-settings-layout>
```

**<x-sidebar\>**

The admin sidebar: the site's brand, a Dashboard link, then the `sidebar` menu's collections and items that the
current user may see, with the current page marked. It takes no attributes. The Admin layout (`master.php`) renders it
once, so you don't place it yourself: add your module's links to the `sidebar` menu instead (see [Menus](menus.md)).

```php
<x-sidebar />
```

## Settings forms

**<x-setting-row\>**

A label and description on the left, the control (the slot) on the right. Wraps on narrow screens. `for` points the
label at the control's `id`.

```php
<x-setting-row label="Site name" description="Shown in the admin." for="siteName">
    <input type="text" class="form-control" id="siteName" name="siteName" value="<?= esc($siteName) ?>">
</x-setting-row>
```

**<x-switch\>**

An accessible `role="switch"` checkbox. A hidden input posts `offValue` (default `0`) when it is off; `value` (default
`1`) posts when it is on. Give it an empty `offValue` to post nothing when off, as for an array name like
`permissions[]` that should hold only the checked values. `checked` turns it on unless it is empty, `0` or `false`;
`label` becomes its `aria-label`. Any other attribute (`id`, `x-model`, `disabled`, …) goes on the checkbox.

```php
<x-switch name="siteOnline" label="Site online" checked="<?= setting('Site.siteOnline') ? '1' : '' ?>" />
<x-switch name="permissions[]" offValue value="users.edit" checked="<?= $canEdit ? '1' : '' ?>" />
```

**<x-segmented\>**

A radio group styled as a segmented control. `options` is a JSON object of value => label; wrap it in single quotes.

```php
<x-segmented name="timeFormat" label="Time format" value="<?= $timeFormat ?>"
    options='<?= json_encode(['g:i A' => '12-hour', 'H:i' => '24-hour'], JSON_HEX_APOS) ?>' />
```

**<x-save-bar\>**

A sticky "Unsaved changes · Discard · Save" bar for long forms. Put it inside the `<form>`: it appears once a field
changes, Discard resets the form, and Save submits it. `save` relabels the Save button. Without JavaScript it is always
shown, so the form can still be submitted.

```php
<form method="post" action="…">
    …
    <x-save-bar save="Save settings" />
</form>
```

## Lists

**<x-chip\>**

A pill filter with an optional `count`. With an `href` it is a link (`active` marks the current one). With a `name` it
is a radio input for a filter form, checked when `active`; give the chips one `name` and each its own `value`. With
neither it is a toggle button. Other attributes (`hx-get`, `hx-target`, …) pass through.

```php
<x-chip label="Admins" count="3" href="?group=admin" active="<?= $group === 'admin' ? '1' : '' ?>" />

<x-chip name="role" value="admin" label="Admins" count="3" active="<?= $role === 'admin' ? '1' : '' ?>" />
```

**<x-status\>**

A coloured dot and a label. `tone` is `ok`, `warn`, `danger`, `info` or `muted` (the default).

```php
<x-status tone="ok" label="Active" />
```

**<x-empty-state\>**

A centered `title` and optional `hint` for a list with nothing in it.

```php
<x-empty-state title="No users match" hint="Try a different search or group." />
```

**<x-selection-bar\>**

A sticky bar showing "N selected" while any row checkbox (`tbody input[type=checkbox]`) in its form is checked. The
slot holds the bulk actions; the ✕ clears the selection.

```php
<form method="post">
    <?= csrf_field() ?>
    <table class="table">…</table>
    <x-selection-bar>
        <x-bulk-actions list="users" />
    </x-selection-bar>
</form>
```

**<x-bulk-actions\>**

The bulk [actions](actions.md) of the `list`, as buttons that post the form's checked `selects[]`. Place it inside
`<x-selection-bar>`.

**<x-row-actions\>**

A row's menu: its slot (links like Edit), then the row [actions](actions.md) of the `list` for the record `id`.

```php
<x-row-actions list="users" id="<?= $user->id ?>">
    <li><a href="<?= $user->adminLink() ?>" class="dropdown-item">Edit</a></li>
</x-row-actions>
```

**<x-confirm-dialog\>** and **<x-action-run\>**

Both sit once in the Admin layout (`master.php`). `<x-confirm-dialog>` asks before actions, `hx-confirm` requests and
`data-confirm` links and buttons go ahead (see [Confirmation](actions.md#confirmation)), and renders the form
`<x-row-actions>` submits, so row actions need it even without JavaScript. `<x-action-run>`, inside the
toasts, shows the progress of an action run just queued.

## Buttons and filters

**<x-button\>**

A Bootstrap button with the slot as its label.

| Attribute | Default   | Description                                                              |
|-----------|-----------|--------------------------------------------------------------------------|
| `color`   | `primary` | A Bootstrap colour (`secondary`, `success`, `danger`, …): `btn-{color}`. |
| `type`    | `submit`  | The button's `type`: `submit`, `reset` or `button`.                      |
| `id`      |           | The button's `id`.                                                       |
| `name`    |           | The button's `name`, posted with the form when it submits it.            |
| `onclick` |           | A JavaScript `onclick` handler.                                          |

It drops any other attribute, so for `hx-*`, Alpine or `disabled` write a `<button class="btn …">` yourself.

```php
<x-button>Save</x-button>
<x-button color="secondary" type="button" id="preview">Preview</x-button>
```

**<x-button-container\>**

Wraps a form's buttons with uniform spacing. It takes no attributes.

```php
<x-button-container>
    <x-button>Save</x-button>
</x-button-container>
```

**<x-module-title\>**

A muted line of text for page headers, with the slot as its text. It takes no attributes.

```php
<x-module-title>Manage the site's users</x-module-title>
```

**<x-filter-link\>**

The "Filter" link that shows and hides the filters of a resource list. It takes no attributes and no slot. It toggles
an Alpine `filtered` variable, so put it inside an element with `x-data="{filtered: false}"`. See
[Filters](filters.md#the-view).

**<x-filter-list\>**

The panel that holds a resource list's filters, shown while `filtered` is true. It takes no attributes; the slot is
the filters, usually the `Filters::renderList` cell.

```php
<div x-data="{filtered: false}" hx-target="#content-list">
    <x-filter-link />

    <div class="row">
        <div class="col order-2 order-md-1" id="content-list">
            <?= $this->include('Blog\Views\_table') ?>
        </div>

        <x-filter-list>
            <?= view_cell('Bonfire\Core\Cells\Filters::renderList', 'model=PostFilter target=#content-list') ?>
        </x-filter-list>
    </div>
</div>
```

## Pager templates

Bonfire registers six [pager](https://codeigniter.com/user_guide/libraries/pagination.html) templates. Name one when
you render the links:

```php
<?= $pager->links('default', 'bonfire_list_hx') ?>
```

| Template            | Renders                                                     | htmx target          |
|---------------------|-------------------------------------------------------------|----------------------|
| `bonfire_full`      | First, Previous, two page numbers either side, Next, Last.  | None (full page loads) |
| `bonfire_full_hx`   | The same links, loaded through htmx.                         | `#content-list`      |
| `bonfire_simple`    | Newer and Older links.                                      | None (full page loads) |
| `bonfire_simple_hx` | Newer and Older links, loaded through htmx.                  | `#content-list`      |
| `bonfire_list_hx`   | The admin list footer: "Showing X of Y" with Previous and Next buttons, loaded through htmx. | The nearest `hx-target` ancestor |
| `bonfire_head`      | `<link rel="prev">`, `<link rel="canonical">` and `<link rel="next">` tags. | —                    |

The page-link templates render nothing when there is only one page; `bonfire_list_hx` still shows its "Showing" line.

`bonfire_full_hx` and `bonfire_simple_hx` swap the response into `#content-list`, so give the element that holds your
list that `id`. `bonfire_list_hx` sets no target of its own: put `hx-target` (and any `hx-include`) on an element
that wraps the list, as in the [Filters](filters.md#the-view) example.

`bonfire_head` belongs in the page's `<head>`, so search engines can follow the pages:

```php
<?= $pager->links('default', 'bonfire_head') ?>
```

The templates are package views in `src/Views/`, not part of the published theme, so `composer update` updates them.
To change one, register your own template in `app/Config/Pager.php` and use its name instead.
