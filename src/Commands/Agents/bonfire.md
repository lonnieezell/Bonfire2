## Bonfire 2 ({version}), read before changing admin, auth or theme code

Where things live
- vendor/lonnieezell/bonfire/: package code and docs. Read-only, never edit.
- app/Config/: published Bonfire, Auth, Site, Themes, Users… configs. Edit these.
- themes/ (project root): published Admin, App and Auth themes. Yours to edit.
- app/Modules/<Name>/: your admin modules. Bonfire only finds them once the folder
  is listed in Config\Bonfire::$appModules (commented out by default).

Rules
1. An admin feature is a module: Config/, Controllers/, Models/, Views/ and a
   Module.php extending Bonfire\Core\BaseModule that registers menus and
   widgets in initAdmin(). Model it on vendor/lonnieezell/bonfire/src/Users.
2. Admin controllers extend Bonfire\Core\AdminController.
3. Build admin views from the <x-…> view components in themes/Admin/Components
   (page-head, admin-box, button, tabs, save-bar…) before writing raw Bootstrap.
   The admin UI is Bootstrap 5 + Alpine.js + htmx; don't add other JS frameworks.
4. Gate access with Shield permissions: auth()->user()->can('module.action');
   a module declares its permissions and default grants in its own
   Config/Registrar.php, not in app/Config/AuthGroups.php.
5. Read and write settings with setting('Class.key'), not new config constants
   or tables.
6. Stats widgets take lazy values: StatsItem::addValue() / addValueByFreeQuery().

Docs: vendor/lonnieezell/bonfire/docs/ (matches the installed version)
| Task | Read |
|---|---|
| Finding where config, themes and modules live | `intro/app_structure.md` |
| Looking up a config key, its default and what it does | `intro/configuration.md` |
| Understanding how filters, module discovery and themes fit into a request | `intro/request_flow.md` |
| Updating Bonfire | `intro/installation.md#updating-bonfire` |
| Handling theme changes after updating Bonfire | `intro/published_themes.md` |
| Building a first admin module from scratch, step by step | `building_admin_modules/tutorial_first_module.md` |
| Generating a CRUD admin module for an existing model | `building_admin_modules/generating_a_module.md` |
| Setting up module locations, `Module.php` or a page breadcrumb | `building_admin_modules/creating_an_admin_module.md` |
| Looking up menu item keys, collections, custom menus or menu order | `building_admin_modules/menus.md` |
| Writing admin controllers and views, or changing the admin theme | `building_admin_modules/controllers_and_views.md` |
| Building admin pages, settings forms and lists | `building_admin_modules/view_components.md` |
| Adding row or bulk actions to a list | `building_admin_modules/actions.md` |
| Adding filters to a list | `building_admin_modules/filters.md` |
| Overriding Bonfire's text, adding a locale or translating a module | `building_admin_modules/localization.md` |
| Adding permissions and granting them to groups | `building_admin_modules/permissions.md` |
| Changing login, registration or redirect defaults, or customizing the auth pages | `modules/auth.md` |
| Adding a tab to the user or group edit page | `building_admin_modules/resource_tabs.md` |
| Adding results to the admin search | `building_admin_modules/search.md` |
| Soft-deleting records into the Recycler | `building_admin_modules/recycler.md` |
| Making a module's personal data exportable, erasable or pruned | `building_admin_modules/personal_data.md` |
| Adding a dashboard widget, stat card or chart | `building_admin_modules/dashboard_widgets.md` |
| Writing feature tests for an admin module | `building_admin_modules/testing.md` |
| Changing the default dashboard layout | `modules/dashboard.md` |
| Previewing a Mailable in the admin, or making one previewable | `modules/mail_previews.md` |
| Reading the log of sent and failed mail, or its retention | `modules/mail_outbox.md` |
| Showing flash alerts | `building_sites/alerts.md` |
| Linking CSS and JS assets | `building_sites/assets.md` |
| Configuring asset folders, versions and cache-busting | `modules/assets.md` |
| Writing your own view components | `building_sites/components.md` |
| Asking visitors for cookie consent | `building_sites/consent.md` |
| Defining consent types, customizing the consent form, or consent config | `modules/consent.md` |
| Handling a GDPR data export or erasure request, or building self-service for it | `building_sites/data_requests.md` |
| Looking up the personal data service, retention config or the prune command | `modules/privacy.md` |
| Setting page titles, meta tags, scripts and styles | `building_sites/metadata.md` |
| Storing extra data on users or other entities | `building_sites/meta_info.md` |
| Defining meta fields, or adding meta info to your own entities | `modules/meta_info.md` |
| Reading and writing settings | `building_sites/settings.md` |
| Formatting dates, form errors and other global helpers | `building_sites/common_functions.md` |
| Taking the site offline for maintenance | `building_sites/common_functions.md#maintenance-mode` |
| Storing pages and posts as Markdown files, and how Content's file-first index works | `content/index.md` |
| Setting up a first collection, blueprint, entry and template, step by step | `content/getting_started.md` |
| Defining a collection, its entries or its filenames | `content/collections.md` |
| Keeping pages in a tree with nested URIs | `content/structure.md` |
| Adding tags or categories to entries | `content/taxonomies.md` |
| Reading site-wide values such as the site name | `content/globals.md` |
| Building a site menu from entries and links | `content/navigation.md` |
| Writing a blueprint for an entry's admin form | `content/blueprints.md` |
| Choosing a field type, or overriding a field's input | `content/field_types.md` |
| Writing a custom field type | `content/custom_field_types.md` |
| Content permissions, editing, previews or the trash in the admin | `content/admin.md` |
| Serving entries at their URIs, with a template or a controller | `content/routing.md` |
| Querying, listing, paginating or searching entries in a controller or view | `content/query_api.md` |
| Putting an entry's title, description and Open Graph tags in the head | `content/seo.md` |
| Serving an RSS feed or a sitemap of content | `content/feeds_and_sitemaps.md` |
| Generating starting views or a controller for a collection | `content/scaffolding.md` |
| Reindexing content, and what to run on deploy | `content/the_index.md` |
| Looking up Content's config properties and spark commands | `content/reference.md` |
| Running Content on several servers, or with git on production | `content/constraints.md` |
