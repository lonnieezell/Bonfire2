# Change Log

This holds the change history for Bonfire as we lead up to a 1.0 release. It's not exhaustive, but should give you a good idea of the changes that have been made and how it might impact you.

**IMPORTANT!** *Breaking changes* are marked with words `breaking change` in parentheses right after the date.

## 28 October 2026

The dashboard's widget library now lists widgets by the module that adds them, in name order, in place of by type.
A widget's `group()` gives its module's name; the type keys `quickLinks`, `stats`, `charts` and `custom` still work,
each under its own heading. A `StatsItem` or `ChartsItem` takes `'module' => '…'` (see
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md)). With the library open, the page now widens by
its width instead of narrowing the dashboard, where the screen has room.

The sidebar now shows **Settings** as a single link to the first settings page you may open, since the settings
pages list one another in their own nav. A menu collection opts into this with `setPageNav()`. Installs that
published the Admin theme keep the old expanding Settings menu until they copy the new
`themes/Admin/Components/sidebar.php`.

Each admin can now change a dashboard widget's settings for their own dashboard: in edit mode, its sliders button
opens them in a panel, and they are saved with the layout (see
[Customizing the Dashboard](../modules/customizing_the_dashboard.md#widget-settings)). Stat cards, charts and Log
activity have settings; until an admin changes one, it follows the site-wide value in Settings → Widgets. Widgets
declare their settings with `fields()`, a list of `Bonfire\Dashboard\WidgetField`, and get their values in
`render(WidgetValues $values)`; `DashboardWidget::settingsUrl()` is gone (see
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md#settings)).

The Log activity widget now shows a column for each of the last days up to today, with a thin line for days without
a log file. Its **Days to show** setting (formerly *Log files to show*) keeps its saved value, and now defaults to 7.

Dashboard widgets can need a permission: pass `'permission'` to a `StatsItem` or `ChartsItem`, and admins who lack it
don't see it in the widget library or on their dashboard, nor is its data fetched for them. The built-in Users widgets
need `users.list` and the Groups widgets `groups.settings`. Admins with `widgets.settings` can **Save as site
default** from the dashboard's edit bar, and **Reset site default** in Settings → Widgets (see
[Customizing the Dashboard](../modules/customizing_the_dashboard.md#the-site-default)). The Settings → Widgets
switches are now labelled as choosing which widgets are *available*. Quick Links is now a native widget,
`Bonfire\Dashboard\Widgets\QuickLinksWidget`, registered by the Dashboard module under its old id, and no longer in
the default `Dashboard.cells`; installs that still list `Bonfire\Dashboard\DashboardCells::quickLinks` show it once,
in place. `DashboardCells::quickLinks()` is deprecated. Building widgets is documented in
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md).

## 28 October 2026 (breaking change)

Log activity is now a dashboard widget of its own, `Bonfire\Tools\Widgets\LogActivity`, like Recent errors. It
keeps its id `logs:logActivity`, so saved layouts keep it in place, and its days and Debug defaults in Settings →
Widgets keep their saved values. It no longer has a switch under *Available widgets*: every admin with `logs.view`
can add it, even where it was switched off. It is no longer an item of `service('widgets')->widget('logs')`, so the
`Bonfire\Widgets\Cells\WidgetCells::logs` cell no longer shows it. Code that calls it directly: `days()` and
`levels()` now take the options as arguments, `days(int $count, bool $hideDebug)` and `levels(bool $hideDebug)`;
`render()` takes a `WidgetValues`; and `setTitle()`, `withOptions()`, `module()` and `settings()` are gone.

List rows are now selectable whenever the admin may run any of the list's bulk actions, not only when they may
delete: a module's bulk action shows the checkboxes on its own. A row's checkbox is disabled when no bulk action
`allows()` that record. `ActionList::selectable()` answers both, and `ActionList::showing()` hands a page's records to
`<x-row-actions>`, which no longer loads its record with one query per row once the view calling it does so. Installs that published the Admin theme
should copy the new `themes/Admin/Components/row-actions.php`, `themes/Admin/Search/users.php` and
`themes/Admin/_table_head.php`; `_table_head.php`
now shows the select-all checkbox only when `$showSelectAll` is true, rather than whenever it is set and the admin
has `users.delete`.

Each widget's `fields()` is now the only place its settings are declared: the Settings → Widgets tabs are built from
the fields that have a `key`, one tab per settings class, and saved through them. Values you saved there keep
working. The properties of `Bonfire\Widgets\Config\Stats`, `LineChart`, `BarChart`, `DoughnutChart`, `PieChart`,
`PolarAreaChart` and `LogActivity` are gone, so a default overridden in a published copy of those classes or in
`.env` no longer applies: set it in Settings → Widgets instead. The line chart's subtitle and custom border
settings, which had no form, are no longer read. `WidgetOptions` keeps only `all()`, `forAlias()`, `save()` and
`reset()`.

**Users by group** is now a bar list: each group, a bar scaled to the largest one, and its number of users, with a
setting to sort by count or by name. It is a new widget, `users:byGroup`, in place of the *Users by Group* stat card
(`stats:usersExpTable693`), which is gone. Saved dashboards that placed the stat card lose it: re-add Users by group
from the widget library. Two new widgets: **Newest users** (the latest users to join, linking to each; needs
`users.list`) and **Recent errors** (the newest errors, or errors and warnings, across the log files, linking to each
log file; needs `logs.view`). The default dashboard now has Users by group and Newest users in its **Users** section, in place of the
Users in Recycler stat, and Log activity and Recent errors side by side in **Monitoring** (see
[The Dashboard](../modules/dashboard.md#configuration)). Installs that published the Admin theme need
the new `.bf-list-card`, `.bf-bars` and `.bf-rows` rules of `themes/Admin/css/admin.css`.

Lists have a shared system for row and bulk actions (see [Row and Bulk Actions](../building_admin_modules/actions.md)):
an action is one class, registered on a named list, run through `POST admin/actions/{list}/{action}` with permission,
CSRF and per-record checks, confirmation (with or without JavaScript), and an optional queue through
`codeigniter4/queue`. Run `php spark migrate --all` for its new `action_runs` table. The Users and Logs deletes use it,
which removes routes: `GET admin/users/{id}/delete` and `POST admin/users/delete-batch` are gone, as is
`UserController::delete()` and `deleteBatch()`. Links or forms that used them must post to
`admin/actions/users/delete-users` instead, with the IDs in `selects[]`. The Users and Logs list checkboxes are now
named `selects[]` with the ID as their value. Every inline `confirm()` is replaced by `<x-confirm-dialog>`, which also
handles `hx-confirm`. Installs that published their own copy of the Admin theme must re-publish it: `master.php` gains
`<x-confirm-dialog />` and `<x-action-run />`, and the lists use the new `<x-row-actions>` and `<x-bulk-actions>`.

`<x-switch>` gained an `offValue` attribute: the value its hidden input posts when the switch is off (`0` by default).
An empty `offValue` posts nothing, which Bonfire's own views now use for array names like `permissions[]`. Installs
that published their own copy of the Admin theme must re-publish it; an older copy posts `0` for every switch that is
off in those lists. The user edit screens (details, permissions and security) use the new admin layout.

The settings pages share a new `<x-settings-layout>` component (the Settings head and a sub-nav of the settings
pages). An older published copy of the Admin theme doesn't have it, so re-publish the theme or those pages fail to
render. Turning **Site Online** off now saves `0`; the offline check treats any empty value as offline.

The Logs, log view, Recycler and System Info pages use the new admin layout, with styles an older published copy of
the Admin theme doesn't have, so re-publish the theme. A log file can now be downloaded from its page.

A new **Log activity** dashboard widget charts the entries of the newest log files per level (see
[Widgets](../building_admin_modules/widgets.md#log-activity-widget)). Turn it on in Settings → Widgets; its own tab sets
how many files it shows and whether Debug entries are left out. It is shown by a new
`Bonfire\Widgets\Cells\WidgetCells::logs` cell: installs with their own `Config\Dashboard` must add that cell to
`$cells` to see it. Its styles are in the Admin theme, so re-publish the theme.

Each admin can customize their own dashboard (see [The Dashboard](../modules/dashboard.md#customizing-the-dashboard)):
**Customize** lets them drag widgets into a new order, group them into titled sections, size them (¼ / ⅓ / ½ / ⅔ /
Full, from their tools or by dragging a corner), remove them and add others from a widget library, all of it also from
the keyboard, with undo and redo. **Save layout** stores the layout as theirs alone; saving the default layout, after
resetting to it, forgets theirs. Every stat card, chart, Log activity and custom cell in `Dashboard.cells` is now
one widget, and modules can add their own by implementing `Bonfire\Dashboard\DashboardWidget` and calling
`service('dashboard')->register()` in `initAdmin()` (see [Widgets](../building_admin_modules/widgets.md#your-own-dashboard-widget)).
The switches in Settings → Widgets now decide which widgets are *available* in the palette, and are on unless a site
admin switches them off; their stored keys are unchanged. The default dashboard is the new `Dashboard.layout` config
property: Quick Links, a Users section (Users by Group and Users in Recycler) and a Monitoring section (Log activity).
Stats, charts and Log activity no longer appear on it by being switched on, and each admin adds the ones they want
from the widget library. Installs with their own `Config\Dashboard` and no `$layout` property get the plain cells of
`Dashboard.cells` (Quick Links and your own cells) instead. A stats or chart item now fetches its data only
when it is on the viewer's dashboard, so `service('dashboardContext')->shows()` answers "is it in the viewer's
layout". The dashboard needs the new `admin/js/dashboard.js` and new `admin.css` rules
from the Admin theme, so installs that published their own copy of the Admin theme must re-publish it; with an older
copy the dashboard shows its widgets stacked in one column and Customize does nothing.

Also fixed: a custom SMTP port is saved and shown again, and a user's name, profile fields and avatar initials are
escaped wherever the admin shows them. The Recycler's Restore button no longer asks for confirmation; Purge asks
first.

## 27 October 2026 (breaking change)

The Admin theme is being redesigned. Installs that published their own copy of the Admin theme must re-publish it:
Bonfire's admin views now use new View Components (`<x-setting-row>`, `<x-switch>`, `<x-segmented>`, `<x-chip>`,
`<x-status>`, `<x-empty-state>`, `<x-selection-bar>`, `<x-tabs>`, `<x-save-bar>`) that an older copy doesn't have,
and the shell (`master.php`, `_header.php`, `Components/sidebar.php`) has a new layout. `<x-page-head>` and
`<x-admin-box>` gained optional attributes and keep working as before without them; `<x-button>` no longer adds
`btn-lg`. Escape component attribute values with `esc()`: the renderer now decodes HTML entities in attribute
values before handing them to the component, so a custom component that prints an attribute must escape it. See
[View Components](../building_admin_modules/view_components.md).

Dashboard stats widgets are now neutral cards, so a `StatsItem`'s `bgColor` no longer has a visible effect.

The admin shell has a collapsible sidebar (remembered per viewer), a breadcrumb, a dark-mode toggle and a ⌘K / Ctrl+K
command palette that jumps to any admin page or runs a search. Admin pages below a menu item can name themselves in the
breadcrumb with a `crumb` view section (and `crumbParent`, when the page isn't under the active menu item).

The admin theme has an accent color. Change it under **Settings → General → Appearance**, or set it from code
with `setting('Site.adminAccent', '#2F6FEB')`. It must be a `#rrggbb` hex color. Buttons, links, focus rings and checked
inputs follow it. The admin also gains a dark mode that follows the operating system until a viewer picks one, and it
now uses the Geist font. Installs that published their own copy of the Admin theme need to re-publish it to get the
new tokens; until then the accent setting saves but has no visible effect.

## 11 October 2026 (breaking change)

Bonfire no longer adds a `users` rule group to `Config\Validation`. Nothing in Bonfire used it, and because
`Config\Validation` does not declare `$users`, it raised a "Creation of dynamic property" deprecation under PHP 8.2+.
The rules for the user form live in `Bonfire\Users\Config\Users::$validation`. If your app calls
`$this->validate('users')`, add the rules you need to your own `app/Config/Validation.php` as `public array $users`. If
you already declare `$users` there, Bonfire no longer merges its `email`, `username`, `first_name` and `last_name` rules
into it, so add any of those you rely on.

Fixes: Bonfire found no themes under CodeIgniter 4.7.5, so every admin and auth page failed with "`Admin` is not a
valid theme folder". `Theme::available()` now lists theme folders itself instead of using `get_dir_file_info()`, which
no longer returns folders.

## 8 October 2026 (breaking change)

The Recycler no longer guesses at what a model can do by checking `method_exists()`. A model that customizes the
Recycler must now implement the matching interface from `Bonfire\Recycler\Interfaces`: `CustomRecyclerQuery`
(`setupRecycler(): Model`), `CustomRecyclerRestore` (`recyclerRestore(int $id): bool`) and `CustomRecyclerPurge`
(`recyclerPurge(int $id): bool`). Methods with those names on a model that does not implement the interface are no
longer called. Without a custom hook, the Recycler now uses the model's own primary key and soft delete field instead of
assuming `id` and `deleted_at`, and refuses to restore or purge a record that is not in the Recycler.

Modules now register their recyclable resources with `service('recycler')->register()` from `initAdmin()`, instead of
editing `Config\Recycler::$resources`. The Users module registers `users` this way, so `$resources` is empty by default;
entries you already have in your own `Config\Recycler` keep working and take precedence over a module's. If you publish
the Recycler `listResource` view, it now receives `RecyclableResource` objects as `$resources` and `$currentResource`
(use `->label()`, `->columns()`, `->localizedColumns()`, `->alias()`) and no longer receives `$currentAlias`.

The logs in the Tools area are now read and deleted through `Bonfire\Tools\Libraries\LogStore`, which only accepts
file names like `log-2024-01-31`. `Logs::getAdjacentLogFiles()` moved to `LogStore::neighbours()` and
`Logs::paginateLogs()` takes the page as its third argument instead of reading `$_GET`. If you publish the logs list
view, each log now has a `name` (without extension) instead of a `filename`.

Fixes: viewing and deleting logs now requires the `logs.view` and `logs.manage` permissions; before, any user with
`admin.access` could read and delete the logs by calling the URLs directly. "Delete all" now removes only the log
files. The levels count in the logs list counts entries instead of every occurrence of a level's name in a message.

View Components now render self-closing (`<x-foo />`) and paired (`<x-foo>…</x-foo>`) tags through the same code, so the
two forms no longer differ by accident. Two behaviours change:

- A self-closing tag now passes its attributes to a component class, as the docs always said. Before, `<x-foo attr="bar" />`
  lost `attr` whenever a `FooComponent` class existed, so the class' `$this->data` was empty. If your class worked around
  that, it now receives the attributes.
- A tag whose view can't be found now always throws a `RuntimeException` (`View not found for component: foo`). Before,
  a paired tag with a missing view (or any error while rendering one) was swallowed and the raw `<x-foo>` tag stayed in
  the page. A regex failure while rendering components also throws now, instead of returning a number as the page.

What a component view sees (the attributes as variables, `$slot`), the lookup order (theme first, then
`componentsLookupPaths`) and the `Components/<name>.php` + `<Name>Component.php` convention are unchanged.

## 8 October 2026 (breaking change)

Everything about whether a dashboard widget is enabled, and what its display options are, now has one owner in
`Bonfire\Widgets`: `ItemSettings` (the enabled flag of an item), `DashboardContext` (are we on the dashboard, and does
this item show) and `WidgetOptions` (the options of the Stats widget and of each chart type). The settings keys stored
in your database are unchanged.

If you publish your own copy of the dashboard cell views (`Cells/stats.php`, `Cells/charts.php`, `Cells/scripts.php`)
or the widgets `settings.php` view, note that they no longer receive `$manager` from `Manager::manager()`, which has
been replaced by `Manager::items()`. Ask each item for `$item->settings()->enabled()` instead.

Widget items now also report an item's settings through `settings()`, which is part of the `Item` interface. If you
wrote your own item class implementing `Bonfire\Widgets\Interfaces\Item`, add `settings(): ItemSettings` to it. The
protected `$dashboardRoute` property of `StatsItem` and `ChartsItem` is gone. `StatsItem::addValue()`, `addValueByFreeQuery()` and `ChartsItem::addDataset()` still
only run their query on the dashboard when the item is enabled, and the check can now be swapped in tests through
`service('dashboardContext')`.

Items in a second collection of a widget now show up on the widgets settings page and on the dashboard.

## 8 October 2026

The rules for who may edit, ban, delete or assign groups and permissions to whom in the Users admin now live in one
place (`Bonfire\Users\Libraries\UserAccess`), and the views and the controller both use it. If you have published
your own copy of the Users views `form.php`, `permissions.php` or `_table.php`, they now receive an `$access` variable
(a `UserAccess` instance) instead of working out these rules themselves. Avatar storage moved to `AvatarStorage`.

Fixes: purging several users at once now removes the avatar and meta info of every purged user, not only the first.
A user with `users.edit` but without `me.edit` can no longer ban themselves.

## 18 March 2025 (breaking change)

The way widgets are enabled has changed in the database, so if you had widgets enabled before, they will
all be disabled. To clear the database of the orphaned data about enabled widgets run this in your DB:

```sql
DELETE FROM `settings`
WHERE `class` = 'Bonfire\Widgets\Config\Stats' AND `key` LIKE 'Stats_%';
DELETE FROM `settings`
WHERE `class` = 'Bonfire\Widgets\Config\Stats' AND `key` LIKE 'Charts_%';
```

## 26 February 2025 (breaking change)

Finished implementation of **<x-button\>** component in the Admin theme. Users need to update the
Admin theme (updated the component definition).

## 19 January 2025

Resource Meta Info (if you have configured such) can now be included in the Admin area search (see docs page
[Search](../building_admin_modules/search.md) for details).

## 18 January 2025 (breaking change)

Possibility to have Second Factor Authentication is added to Bonfire2, implementing Codeigniter Shield
feature.

To use the feature on existing installs you will first need to update the userspace Config file `app/Config/Auth.php`,
in particular – the property `$views`, two keys relating to 2FA to look like this:

```php
    'action_email_2fa'        => '\Bonfire\Views\Auth\email_2fa_show',
    'action_email_2fa_verify' => '\Bonfire\Views\Auth\email_2fa_verify',
```

Failing to do that and enabling 2FA in the admin area will lock the users out of your website.

To fix it, after modifying the config file, issue this command on your database to clean up references 
to wrong classes:

```sql
DELETE FROM settings WHERE class="Config\Auth" AND key="actions";
```

## 16 January 2025

Bonfire can henceforth warn you about breaking changes (like need to update Admin/Auth themes, change config files, etc).

To get such notifications when updating Bonfire, you can add a command to your
composer.json scripts section:

```json
    "scripts": {
        "post-update-cmd": [
            "php spark notify:breaking-changes"
        ]
    }
```

New Bonfire install will automatically include this command.

## 18 March 2024 (breaking change)

**<x-button\>** and **<x-button-container\>** view [components](https://github.com/lonnieezell/Bonfire2/blob/develop/docs/building_admin_modules/view_components.md) implemented.

When updating, copy files `themes/Admin/Components/button-container.php` and `themes/Admin/Components/button.php` into the corresponding folder of your project's Admin theme. See [#434](https://github.com/lonnieezell/Bonfire2/pull/434) for details about the change.
