# Building Admin Modules

Any application admin panel will need more than just user management capabilities. Your app might need page, tag, product management. This section deals with extending Bonfire admin are with your own modules and integrating them into the admin panel.

## Creating an Admin Module

Learn how to create new modules of code for the admin area, centered around a single task or resource, like Users, Photos, etc. This guide covers the basics of creating a `Module.php` file, configuring module locations, and adding menu items.

[Read more about Creating an Admin Module](creating_an_admin_module.md)

## View Components

View Components allow you to create custom HTML elements to use within your views. This section lists the components available within the default `Admin` theme and provides examples of their usage.

[Read more about View Components](view_components.md)

## Filters

Filtering allows you to specify which columns can be filtered and then pass your model to a view cell that handles the display of the filters. This ensures a consistent look and functionality in the UI while providing the least amount of work when creating a UI.

[Read more about Filters](filters.md)

## Recycler

The Recycler provides an area where users can browse objects that have been deleted and either restore or purge them. The models for these resources must have soft deletes enabled. This section covers registering a resource, localizing column names, and modifying the Recycler query.

[Read more about the Recycler](recycler.md)

## Resource Tabs

Resource tabs are the tabs displayed when editing a resource, like an individual User or User Group. They make it possible to easily integrate other tabs onto a user without editing the core theme.

[Read more about Resource Tabs](resource_tabs.md)

## Search

Bonfire provides a flexible search system that is highlighted on all areas of the admin area. This section covers integrating your module into the search results, adding filters for the Advanced Search form, and providing search results.

[Read more about Search](search.md)

## Widgets

Widgets are elements that display information on the dashboard. They can be small informational cards or charts. This section covers adding new widgets, configuring them, and displaying them on the dashboard.

[Read more about Widgets](widgets.md)

## Dashboard Cells

Customize the dashboard with the use of view cells. This section covers specifying which cells to display, creating the cell, and displaying it on the dashboard.

[Read more about Dashboard Cells](dashboard_cells.md)