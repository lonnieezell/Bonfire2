# View Components

View Components (or just Components) allow you to create custom HTML elements to use within your views. The main 
description of their use and creating new ones is described within the `Building Sites` section of the guide. This
page lists the components that are available within the default `Admin` theme. 

## Available Components

**<x-admin-box\>**

This wraps the content sections within the admin area. In the default theme it is the white box with rounded 
corners that you see everywhere. 

**<x-filter-link\>**

This is used by the Filters system to show the link that display or hides the filters in a resource list page. 

**<x-filter-list\>**

This is the box that wraps the list of filters in a resource list page. 

**<x-module-title\>**

This is used within the admin page headers to display the main page title. 

**<x-button\>**

This is used within the admin forms to add pre-styled buttons (configurable via attributes `color` (any pre-defined Bootstrap 5 color, like `primary`, `secondary`, `success`, etc., defaulting to `primary`) and `type` (defaults to `submit`, but can also be `reset`, `button`, etc.).

**<x-button-container\>**

Wrapper for form button area enforcing uniform margins.

**<x-page-head\>**

This wraps the page header on admin pages. 

**<x-sidebar\>**

This is the main view that renders the sidebar and all links within it.
