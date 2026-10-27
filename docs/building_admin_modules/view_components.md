# View Components

View Components (or just Components) allow you to create custom HTML elements to use within your views. The main 
description of their use and creating new ones is described within the `Building Sites` section of the guide. This
page lists the components that are available within the default `Admin` theme. 

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

**<x-sidebar\>**

This is the main view that renders the sidebar and all links within it.

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
<form method="post" action="<?= site_url(ADMIN_AREA . '/users/delete-batch') ?>">
    <table class="table">…</table>
    <x-selection-bar>
        <button type="submit" class="btn btn-danger">Delete</button>
    </x-selection-bar>
</form>
```

## Buttons and filters

**<x-button\>**

This is used within the admin forms to add pre-styled buttons (configurable via attributes `color` (any pre-defined Bootstrap 5 color, like `primary`, `secondary`, `success`, etc., defaulting to `primary`) and `type` (defaults to `submit`, but can also be `reset`, `button`, etc.). `id`, `name` and `onclick` are passed on as well.

**<x-button-container\>**

Wrapper for form button area enforcing uniform margins.

**<x-module-title\>**

A muted line of text for page headers.

**<x-filter-link\>**

This is used by the Filters system to show the link that display or hides the filters in a resource list page. 

**<x-filter-list\>**

This is the box that wraps the list of filters in a resource list page. 
