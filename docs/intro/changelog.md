# Change Log

This holds the change history for Bonfire as we lead up to a 1.0 release. It's not exhaustive, but should give you a good idea of the changes that have been made and how it might impact you.

**IMPORTANT!** *Breaking changes* are marked with words `breaking change` in parentheses right after the date.

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
