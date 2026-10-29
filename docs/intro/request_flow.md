# How Bonfire Fits Into a Request

Bonfire doesn't replace CodeIgniter's request cycle. It hooks into it at a few points: it registers its modules with
the autoloader, adds some filters, and gives your controllers a theme to render with. Everything else is a plain
CodeIgniter request. This page walks through those points in the order a request meets them, so you know when your
module's code runs and why.

## Before routing: module namespaces

On CodeIgniter's `pre_system` event, before the router runs, Bonfire registers every module folder with the
autoloader: each folder in Bonfire's `src/` (such as `src/Users`), and each folder inside the locations listed in
`Config\Bonfire::$appModules`.

This is what makes a module feel like part of the app. CodeIgniter's auto-discovery looks in every registered folder,
so it finds a module's `Config/Routes.php`, `Config/Events.php` and `Config/Registrar.php` without you listing them
anywhere.

## The filter chain

Bonfire registers its filters through its `Registrar`, so an install picks them up without editing
`app/Config/Filters.php`. They run in this order:

1. **`online`**, on every request except `/site-offline`. When the site is offline, it sends visitors to
   `/site-offline`. Superadmins and users with the `site.viewOffline` permission browse the site as usual.
2. **`session`**, on every URL under the admin area (`ADMIN_AREA`, `admin` by default). Shield's session filter
   sends guests to the login page.
3. **`admin`**, on the same URLs. It sends logged-in users without the `admin.access` permission to the home page.
4. **`bonfire`**, on every URL. It boots Bonfire, as described below.

The order matters. `session` and `admin` come before `bonfire`, so a visitor who may not enter the admin area is
turned away before any module code runs.

After the controller has run, two more filters work on the response: `alerts` adds any queued alerts to the page,
and `consent` adds the cookie consent banner to pages outside the admin area, when consent is turned on and the
visitor hasn't given it yet.

## Booting: module discovery

The `bonfire` filter calls `service('bonfire')->boot()`, once per request. Booting first looks for modules in three
places:

- **Core modules:** the folders in Bonfire's own `src/`.
- **App modules:** the folders inside each location in `Config\Bonfire::$appModules`.
- **Package modules:** the directories listed in `Config\Bonfire::$packageModules`, one per Composer package.

Not every folder is a module. Bonfire only loads the ones that contain a `Module.php`, and makes one instance of each
`Module` class. It caches the list of folders it found in the first two places, which is why a new module folder only
appears after the cache expires or after `php spark cache:clear`.

## `initAdmin()` or `initFront()`

Next, Bonfire calls one method on every module. The URL decides which:

- A path that is `ADMIN_AREA` or starts with `ADMIN_AREA/` is an admin request, and every module gets `initAdmin()`.
- Any other request is a front-end request, and every module gets `initFront()`.

Before an admin boot calls `initAdmin()`, Bonfire creates the admin's shared structures: the `sidebar` menu with its
Content, Settings and Tools collections, the `iconbar` menu, and the dashboard's stats, charts and logs widgets. These
exist only in admin requests, so `initAdmin()` is the place to add menu items and widgets. `initFront()` is for setup
your module needs on public pages.

Because booting happens in a filter, it only happens where filters run. 404 pages, pages served from CodeIgniter's
page cache, and CLI commands don't boot Bonfire on their own. Code that needs the admin set up outside a web request,
such as a queue worker running an [admin action](../building_admin_modules/actions.md), calls `boot(true)`, which
runs `initAdmin()` whatever the URL.

## Rendering: themes

By the time your controller runs, Bonfire has done its setup. The rest is ordinary CodeIgniter routing to your
controller. Bonfire's part comes back when the controller renders a page.

A controller that extends `AdminController` renders with the `Admin` theme, and one that extends Bonfire's
`BaseController` with the `App` theme. `render()` points the view renderer at the theme's folder, so the theme's
layout and its `<x-…>` view components wrap your view. Those theme folders are your app's own copy, not Bonfire's.
[Why Themes Are Published Into Your App](published_themes.md) explains why.

## Related pages

- [Creating an Admin Module](../building_admin_modules/creating_an_admin_module.md): set up module locations and write
  a `Module.php`.
- [Permissions](../building_admin_modules/permissions.md): the `admin.access` and `site.viewOffline` permissions.
- [Admin Controllers, Views and Themes](../building_admin_modules/controllers_and_views.md): render a page inside a
  theme.
