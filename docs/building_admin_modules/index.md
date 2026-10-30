# Building Admin Modules

Any application admin panel will need more than just user management capabilities. Your app might need page, tag, product management. This section deals with extending Bonfire admin are with your own modules and integrating them into the admin panel.

## Tutorial: Build Your First Admin Module

New to Bonfire? Start here. This tutorial walks you from an empty folder to a working Posts screen, with a sidebar item, a permission, a list with search and filters, global search results and the Recycler, checking each step in the browser.

[Start the tutorial](tutorial_first_module.md)

## Module Structure

Learn how modules are laid out and found, centered around a single task or resource, like Users, Photos, etc. This guide covers configuring module locations, shipping a module in a Composer package, the `Module.php` file, and naming a page in the breadcrumb.

[Read more about Module Structure](creating_an_admin_module.md)

## Menus

The admin sidebar is a menu, and your module adds its pages to it. This section lists every menu class, key and method, and covers custom menus and ordering items with `$menuWeights`.

[Read more about Menus](menus.md)

## Controllers, Views and Themes

Your module's pages are CodeIgniter controllers and views rendered inside the Admin theme. This section covers rendering an admin page, laying out its view, and overriding the theme's files so your changes survive Bonfire updates.

[Read more about Admin Controllers, Views and Themes](controllers_and_views.md)

## Permissions

Bonfire uses Shield groups and permissions to decide who may use each part of the admin. This section covers checking a permission, declaring your module's permissions, granting them to groups by default, and managing groups in the admin.

[Read more about Permissions](permissions.md)

## View Components

View Components allow you to create custom HTML elements to use within your views. This section lists the components available within the default `Admin` theme and provides examples of their usage.

[Read more about View Components](view_components.md)

## Filters

Filtering allows you to specify which columns can be filtered and then pass your model to a view cell that handles the display of the filters. This ensures a consistent look and functionality in the UI while providing the least amount of work when creating a UI.

[Read more about Filters](filters.md)

## Row and Bulk Actions

Actions are what an admin can do to one record from its row's menu, or to every checked record in a list. This section covers writing and registering an action, showing it in a list, asking for confirmation, and running large batches in the background.

[Read more about Row and Bulk Actions](actions.md)

## Search

Bonfire provides a flexible search system that is highlighted on all areas of the admin area. This section covers integrating your module into the search results, adding filters for the Advanced Search form, and providing search results.

[Read more about Search](search.md)

## Recycler

The Recycler provides an area where users can browse objects that have been deleted and either restore or purge them. The models for these resources must have soft deletes enabled. This section covers registering a resource, localizing column names, and changing how a model is listed, restored and purged.

[Read more about the Recycler](recycler.md)

## Resource Tabs

Resource tabs are the tabs displayed when editing a resource, like an individual User or User Group. They make it possible to easily integrate other tabs onto a user without editing the core theme.

[Read more about Resource Tabs](resource_tabs.md)

## Dashboard Widgets

Widgets are what admins place on their dashboard. This section covers writing a widget with its own settings, adding stat cards and charts, and the older view cells.

[Read more about Dashboard Widgets](dashboard_widgets.md)

## Localization

Bonfire's admin text comes from language files. This section covers changing Bonfire's wording, adding a locale, translating your own module, and checking your translations.

[Read more about Localization](localization.md)

## Testing

Feature tests request your module's pages as a logged-in user and check the response. This section covers a base test case for your app, giving test users your module's permissions, and testing the access checks and htmx requests.

[Read more about Testing Your Admin Module](testing.md)
