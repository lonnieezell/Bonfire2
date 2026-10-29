# Common functions

Bonfire loads these helpers from `src/Common.php` on every request, so you can call them anywhere without
`helper()`.

| Helper | Returns |
| ------ | ------- |
| [`app_date()`](#app_date) | A date formatted with the General Settings date and time formats |
| [`has_error()`](#has_error-and-error) | Whether a form field has a validation error |
| [`error()`](#has_error-and-error) | A form field's validation error message |
| [`render()`](#render) | A view rendered with a given theme |
| [`admin_accent()`](#admin_accent) | The admin theme's accent color |
| [`site_offline()`](#site_offline) | Whether the site is in maintenance mode |

## app_date()

```php
app_date($date, bool $includeTime = false, bool $includeTimezone = false): string
```

This helper will display a date based on the current settings in the General Settings page for date format and the
application's current timezone. You can pass a string with the date, a DateTime object, or a CodeIgniter\i18n\Time
instance as the first argument.

```php
echo app_date('2021-01-15 15:35:00');
// outputs: 01/15/2021

$date = new DateTime('2021-01-15 15:35:00');
echo app_date($date);

$date = new CodeIgniter\i18n\Time('2021-01-15 15:35:00');
echo app_date($date);
```

You can include the time, formatted as specified in General Settings, by passing `true` as the second argument.

```php
echo app_date($date, true);
// outputs: 01/15/2021 3:35 PM
```

You can further include the timezone by passing `true` as the third argument.

```php
echo app_date($date, true, true);
// outputs: 01/15/2021 3:35 PM CST
```

## has_error() and error()

```php
has_error(string $field): bool
error(string $field): string
```

`has_error()` returns `true` when the field has a validation error. `error()` returns that error's message, or an
empty string when there is none.

Both read the `errors` flash data from the session, so redirect back with the errors under that name:

```php
if (! $this->validate($rules)) {
    return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
}
```

`withInput()` on its own isn't enough. It stores the errors under a different session key, which these helpers don't
read.

Then show the error next to the field in the view:

```php
<input type="text" name="policyUrl" class="form-control" value="<?= old('policyUrl') ?>">
<?php if (has_error('policyUrl')) : ?>
    <p class="text-danger small mb-0"><?= error('policyUrl') ?></p>
<?php endif ?>
```

## render()

```php
render(string $theme, string $view, array $data = [], array $options = []): string
```

Renders `$view` with the theme `$theme`, where there is no controller to do it, such as in a route closure. It loads
the `assets` helper, makes `$theme` the current theme, and passes the `viewMeta` service to the view as `$viewMeta`.
`$options` goes to CodeIgniter's view renderer, as with `view()`.

```php
$routes->get('admin/blog/preview', static fn () => render('Admin', 'App\Modules\Blog\Views\preview', [
    'title' => 'Preview',
]));
```

The theme name must match the theme's folder name exactly, including case. See
[Render without a controller](../building_admin_modules/controllers_and_views.md#render-without-a-controller).

## admin_accent()

```php
admin_accent(): string
```

Returns the `Site.adminAccent` setting as a `#rrggbb` hex string. When the stored value isn't a valid hex color, it
returns the default, `#D9622B`. See [Admin accent color](settings.md#admin-accent-color).

## site_offline()

```php
site_offline(): bool
```

Returns `true` while the site is in [maintenance mode](#maintenance-mode), that is, when the `Site.siteOnline` setting
is empty (`false`, `0`, `'0'` or `null`). Use it to show something only while the site is offline:

```php
<?php if (site_offline()) : ?>
    <div class="alert alert-warning">The site is offline. Only staff can see it.</div>
<?php endif ?>
```

## Maintenance mode

While the site is offline, only superadmins and users with the `site.viewOffline` permission can use it. Everyone else
is sent to `/site-offline`, a page that says the site is temporarily offline.

!!! warning

    The login page is offline too, so a superadmin who isn't logged in can't log in to bring the site back. Stay
    logged in while the site is offline, or see [If you're locked out](#if-youre-locked-out).

### Take the site offline

In the admin, go to **Settings → General** and turn off **Site Online**. While the site is offline, the admin shows a
banner with a link back to that setting.

To do it from code, store an empty value in the setting:

```php
setting('Site.siteOnline', false);
```

### Let others in

Superadmins always get in. To let a group in as well, give it the `site.viewOffline` permission: tick it on the
group's permissions page, or add it to the group in `app/Config/AuthGroups.php`. See
[Permissions](../building_admin_modules/permissions.md).

### Show your own offline page

`/site-offline` shows the view named in `Site::$siteOfflineView`. Point it at your own view in `app/Config/Site.php`:

```php
public $siteOfflineView = 'App\Views\offline';
```

Visiting `/site-offline` while the site is online redirects to the home page.

### Bring the site back online

Turn **Site Online** back on in **Settings → General**, or:

```php
setting('Site.siteOnline', true);
```

### If you're locked out

When no one who can get in is logged in, delete the stored setting from the database. The site then falls back to
`Site::$siteOnline` in `app/Config/Site.php`, which is `true` by default:

```sql
DELETE FROM settings WHERE `key` = 'siteOnline';
```

If you use a table prefix, add it to `settings`.

### How it works

The `online` filter runs before every request except `/site-offline`. It calls `site_offline()`, and when the site is
offline, it redirects anyone who isn't a superadmin and doesn't have `site.viewOffline`. See
[Request flow](../intro/request_flow.md) for where it runs among Bonfire's other filters, and
[Configuration Reference](../intro/configuration.md) for the `Site` config keys.
