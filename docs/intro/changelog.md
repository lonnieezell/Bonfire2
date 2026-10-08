# Change Log

This holds the change history for Bonfire as we lead up to a 1.0 release. It's not exhaustive, but should give you a good idea of the changes that have been made and how it might impact you.

**IMPORTANT!** *Breaking changes* are marked with words `breaking change` in parentheses right after the date.

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
