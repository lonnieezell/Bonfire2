# Why Themes Are Published Into Your App

Most of Bonfire stays in `vendor/` and changes when you run `composer update`. Themes are the exception. The installer
gives your app its own copy, and from then on that copy is yours. This page explains why Bonfire works this way, what
that gives you, and what it means when you update Bonfire.

## What happens at install

`php spark bf:install` copies Bonfire's `Admin`, `App` and `Auth` themes into a `themes/` folder in your project root.
It also publishes `app/Config/Themes.php`, whose `$collections` setting points at that folder.

From then on, your app renders only from `themes/`. The themes in `vendor/lonnieezell/bonfire/themes/` are still
there, but nothing reads them.

## What you get

A theme is the part of the admin you are most likely to want to change: the page layout, the header, the sidebar, the
`<x-…>` view components, the CSS and the JavaScript. Because the files are in your app, you change them by editing
them, the same way you'd edit any other view. There's no fork of the package to maintain and no override layer to
learn.

Your edits are also safe. `composer update` replaces everything in `vendor/`, and it never touches `themes/`. This
fits the rest of Bonfire's [philosophy](philosophy.md): give you a solid foundation, then get out of your way.

## What it costs

Your copy doesn't move when Bonfire does. It stays as it was when you published it, and it doesn't pick up the theme
fixes and features in later releases.

Usually that's fine, but sometimes Bonfire's PHP code and its theme change together. A view may start using a new
component, or a page may need a new CSS rule or a new section in `master.php`. An old copy of the theme doesn't have
these, so the page breaks or looks wrong. In October 2026, for example, the Admin theme was redesigned, and Bonfire's
admin views started using components that older copies didn't have.

So for Bonfire, a change to a theme file is a breaking change, even when your own PHP code works as before.

## How Bonfire tells you

Bonfire can't update your copy for you. It doesn't know which files you've changed, or how to merge your edits with
its own. It tells you when a release needs you to look:

- The [changelog](changelog.md) marks those releases with `(breaking change)` after the date, and names the files
  that changed.
- After `composer update`, `php spark notify:breaking-changes` reads the changelog of the version you now have, and
  warns you once about any new breaking change since your last update.

The warning is a pointer to the changelog. Bonfire changes nothing on its own.

## What "re-publish" means

When the changelog says to re-publish a theme or copy a file, there is no command for it. You bring the new files
from `vendor/lonnieezell/bonfire/themes/` into `themes/` and keep your own edits. If you never edited the theme, you
can copy the whole folder over. If you did, you merge the two by hand.

How much work that is depends on how much of Bonfire's theme you've changed. If you keep your changes in a theme of
your own, such as `themes/MyAdmin` for your own controllers, the `Admin` copy stays close to Bonfire's and is easy to
replace.

[When Bonfire updates](../building_admin_modules/controllers_and_views.md#when-bonfire-updates) shows how to compare
your copy with Bonfire's.

## Related pages

- [Admin Controllers, Views and Themes](../building_admin_modules/controllers_and_views.md#override-a-theme-view):
  override a theme view, or keep your own theme alongside Bonfire's.
- [Updating Bonfire](installation.md#updating-bonfire): the breaking-change warning and where Bonfire stores it.
- [View Components](../building_admin_modules/view_components.md): the components the Admin theme defines.
- [How Bonfire Fits Into a Request](request_flow.md): where themes come in during a request.
