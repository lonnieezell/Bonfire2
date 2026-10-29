# Configuration Reference

`php spark bf:install` publishes a copy of each of these config classes to `app/Config/`, where you edit them. This
page lists every key, its default and what it does.

## Config files or the admin

Keys marked **Admin** can also be changed in the admin under **Settings**. Bonfire reads them with `setting()`, which
returns the value saved in the database before the value in the config file. Once an admin has saved one, editing the
file has no effect until the stored value is removed with `setting()->forget()`. See [Settings](../building_sites/settings.md).

## Bonfire

`app/Config/Bonfire.php` tells Bonfire where to find modules and how to order the admin menu.

| Key | Default | Effect |
| --- | --- | --- |
| `$views` | `['filter_list' => 'Bonfire\Views\_filter_list']` | Views Bonfire renders that you can replace. `filter_list` is the filter panel of admin lists (see [Filters](../building_admin_modules/filters.md)). |
| `$appModules` | `[]` | Folders of app modules, as namespace ⇒ path. Bonfire loads every folder in each path as a module. See [Configuring Module Locations](../building_admin_modules/creating_an_admin_module.md#configuring-module-locations). |
| `$packageModules` | `[]` | Single modules in Composer packages, as namespace ⇒ the folder that holds `Module.php`. See [Modules in Composer packages](../building_admin_modules/creating_an_admin_module.md#modules-in-composer-packages). |
| `$menuWeights` | `[]` | Admin menu order, as named route ⇒ weight. Larger weights appear later. An item that isn't listed keeps the weight its module gives it. Orders the Users menu, app module menus and the items inside Tools and Settings, but not Tools and Settings themselves. |
| `$breakingChangeStateFile` | `null` | Where `spark notify:breaking-changes` records the last breaking change it reported. `null` means `writable/bonfire-breaking-changes.json`. See [Updating Bonfire](installation.md#updating-bonfire). |

An example of `$menuWeights`:

```php
public $menuWeights = [
    'user-list'   => 1,
    'custom-list' => 2,
    'recycler'    => 1,
    'sys-info'    => 2,
];
```

## Site

`app/Config/Site.php` holds site-wide values.

| Key | Default | Effect |
| --- | --- | --- |
| `$perPage` | `15` | Items per page in admin lists such as Users, the Recycler and the logs. |
| `$siteName` | `'Bonfire'` | The site name, shown in the admin and used as the default page title. **Admin** |
| `$siteOnline` | `true` | When `false`, visitors are redirected to `/site-offline`. Only superadmins and groups with the `site.viewOffline` permission can still use the site. **Admin** |
| `$siteOfflineView` | `'Bonfire\Views\site_offline'` | The view `/site-offline` shows while the site is offline. |
| `$adminAccent` | `'#D9622B'` | The admin theme's highlight color, as a `#rrggbb` hex string. See [Admin accent color](../building_sites/settings.md#admin-accent-color). **Admin** |

## Themes

`app/Config/Themes.php` says where themes and view components live. It isn't a `BaseConfig`, so `setting()` doesn't
apply to it.

| Key | Default | Effect |
| --- | --- | --- |
| `$collections` | `[__DIR__ . '/../../themes/']` | Folders that hold themes. Each theme is a direct child folder of a collection. |
| `$componentsLookupPaths` | `[APPPATH . 'Views/Components/']` | Fallback folders for a view component that the current theme doesn't define, checked in order. Each path must end with `/`. Use it for a component library shared by several themes. |

## Users

`app/Config/Users.php` covers avatars, the user form and extra user fields.

| Key | Default | Effect |
| --- | --- | --- |
| `$useGravatar` | `false` | Use gravatar.com for users who haven't uploaded an avatar. **Admin** |
| `$gravatarDefault` | `'blank'` | The Gravatar image for emails with no Gravatar: `mp`, `identicon`, `monsterid`, `wavatar`, `retro`, `robohash` or `blank`. **Admin** |
| `$avatarNameBasis` | `'name'` | What the letters on a generated avatar come from: `'name'` (initials, or the username when there's no first name) or `'username'`. **Admin** |
| `$avatarPalette` | 11 hex colors | Background colors for generated avatars. Each user always gets the same one. |
| `$avatarDirectory` | `'uploads/avatars'` | Where uploaded avatars are saved and linked from, relative to `FCPATH`. |
| `$avatarResize` | `false` | Shrink uploaded avatars that are larger than `$avatarSize`. **Admin** |
| `$avatarSize` | `140` | The largest width or height in pixels of a resized avatar. **Admin** |
| `$avatarResizeFloor` | `32` | The smallest `$avatarSize` allowed. |
| `$validation` | rules for `id`, `email`, `username`, `first_name`, `last_name`, `groups.*` | Validation rules for saving a user in the admin. Rules for the fields in `$metaFields` are added to them. |
| `$metaFields` | one example field, `blog` | Extra fields on a user's profile, grouped by fieldset legend. See [Defining Meta Fields](../modules/meta_info.md#defining-meta-fields). |
| `$includeMetaFieldsInSearch` | `['blog']` | Names of `$metaFields` fields that the admin search looks in. Older published configs spell it `$includeMetaFieldsInSearech`, which still works. |

## Consent

`app/Config/Consent.php` controls the cookie consent banner. The [Consent](../modules/consent.md#configuration) page
describes each key in more detail. All keys except the three views can be changed under **Settings → Consent**.

| Key | Default | Effect |
| --- | --- | --- |
| `$requireConsent` | `false` | Ask front-end visitors to opt in to each consent type. When `false`, no banner shows and the consent helper always returns `true`. **Admin** |
| `$consentLength` | `180` | Days before a visitor is asked again. **Admin** |
| `$consentForm` | `'Bonfire\Consent\Views\consent_form'` | The view of the consent banner. |
| `$consentFormStyles` | `'Bonfire\Consent\Views\consent_styles'` | The view with the banner's CSS, added to each page. |
| `$consentFormScripts` | `'Bonfire\Consent\Views\consent_scripts'` | The view with the banner's JavaScript, added to each page. |
| `$policyUrl` | `'privacy'` | The URL of your cookie policy, full or relative. **Admin** |
| `$policyTitle` | `'Cookie Policy'` | The link text of the cookie policy link, which replaces `{policy_url}` in `$consentMessage`. **Admin** |
| `$consentMessage` | a short cookie notice | The message at the top of the banner. **Admin** |
| `$consents` | `required`, `performance`, `targeting` | The consent types visitors accept or reject, keyed by alias, each with a `name` and `desc`. **Admin** |

## Dashboard

`app/Config/Dashboard.php` sets the dashboard everyone sees until they customize it.

| Key | Default | Effect |
| --- | --- | --- |
| `$layout` | quick links, a Users section, a Monitoring section | The site default layout. See [Setting it in config](../modules/dashboard.md#setting-it-in-config). A default saved from the dashboard replaces it. |
| `$cells` | Bonfire's stats, logs and charts cells | Legacy view cells, shown in order only when `$layout` is `null`. See [Legacy: view cells](../building_admin_modules/dashboard_widgets.md#legacy-view-cells). |

## Recycler

`app/Config/Recycler.php` sets up the Recycler. See [The Recycler](../building_admin_modules/recycler.md).

| Key | Default | Effect |
| --- | --- | --- |
| `$defaultResource` | `'users'` | The alias of the resource the Recycler opens on. |
| `$resources` | `[]` | Resources registered by the app, keyed by alias, each with a `label`, `model` and `columns`. An entry with a module's alias replaces how that module's resource is shown. |

## Assets

`app/Config/Assets.php` controls asset URLs and cache busting. The [Asset Management](../modules/assets.md#configuration)
page describes each key in more detail.

| Key | Default | Effect |
| --- | --- | --- |
| `$bustingType` | `'file'` | How the cache-busting string is made: `'file'` uses the file's modified time, `'version'` uses `$versions` in production and the current time elsewhere. |
| `$separator` | `'~~'` | The URL-safe characters put between the file name and the cache-busting string. Must not occur in your asset file names. |
| `$versions` | `['css' => '1.0', 'js' => '1.0']` | Versions used when `$bustingType` is `'version'`. |
| `$folders` | `app`, `admin`, `auth` themes and `other` ⇒ `VENDORPATH` | Folders that can serve assets, keyed by the first segment of the asset URL. |

## Alerts

`app/Config/Alerts.php` extends the config of [Tatter\Alerts](https://github.com/tattersoftware/codeigniter4-alerts),
which has the remaining keys. See [Alerts](../building_sites/alerts.md).

| Key | Default | Effect |
| --- | --- | --- |
| `$template` | `'Bonfire\Views\_alerts.php'` | The view that renders alerts. |
| `$classes` | Bootstrap types, log levels, `messages` and `errors` | Session keys that show as alerts, each mapped to the Bootstrap alert class it uses. |

## Auth, AuthGroups and AuthToken

These three files configure [Shield](https://shield.codeigniter.com), which handles login, groups and permissions.
Shield's documentation covers their keys. This section lists only what Bonfire adds or changes.

### Auth

| Key | Bonfire's default | Effect |
| --- | --- | --- |
| `$views` | Bonfire's views | Bonfire's views replace Shield's for login, register, email 2FA, email activation and magic links. `layout` is the layout of the `auth` theme they extend, `master`. `email_layout` is Bonfire's addition: the layout the magic link email extends. See [Views](../modules/auth.md#views). |
| `$actions` | `['register' => null, 'login' => null]` | Set them to Bonfire's `Bonfire\Auth\Actions\EmailActivator` and `Bonfire\Auth\Actions\Email2FA`, which use Bonfire's views. **Admin** |
| `$sessionConfig` | `'field' => 'logged_in'` | Bonfire stores the logged-in user under the `logged_in` session key, where Shield uses `user`. `allowRemembering` and `rememberLength` are **Admin**. |
| `$personalFields` | `['first_name', 'last_name']` | User fields that a password may not resemble. |
| `$userProvider` | `Bonfire\Users\Models\UserModel` | Bonfire's user model. |

`$allowRegistration`, `$minimumPasswordLength` and `$passwordValidators` can also be changed under
**Settings → Users**.

Bonfire also changes `loginRedirect()`: users with the `admin.access` permission go to the admin area after login,
whatever `$redirects['login']` says.

### AuthGroups

| Key | Bonfire's default | Effect |
| --- | --- | --- |
| `$groups`, `$permissions`, `$matrix` | Bonfire's groups and permissions | See [Bonfire's groups and permissions](../building_admin_modules/permissions.md#bonfires-groups-and-permissions). Once an admin edits a group's permissions, the matrix is stored in the database and `$matrix` is ignored. |
| `$defaultGroup` | `'user'` | The group new users join. **Admin** |
| `$moduleGrants` | `[]` | Bonfire's addition: permissions modules grant to groups by default. See [Default grants](../building_admin_modules/permissions.md#default-grants). |

### AuthToken

Bonfire changes nothing; every key is Shield's.
