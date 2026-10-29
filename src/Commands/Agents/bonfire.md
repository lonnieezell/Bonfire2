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
| Building a first admin module from scratch, step by step | `building_admin_modules/tutorial_first_module.md` |
| Adding an admin module, menu item or breadcrumb | `building_admin_modules/creating_an_admin_module.md` |
| Looking up menu item keys, collections, custom menus or menu order | `building_admin_modules/menus.md` |
| Writing admin controllers and views, or changing the admin theme | `building_admin_modules/controllers_and_views.md` |
| Building admin pages, settings forms and lists | `building_admin_modules/view_components.md` |
| Adding row or bulk actions to a list | `building_admin_modules/actions.md` |
| Adding filters to a list | `building_admin_modules/filters.md` |
| Adding permissions and granting them to groups | `building_admin_modules/permissions.md` |
| Adding a tab to the user or group edit page | `building_admin_modules/resource_tabs.md` |
| Adding results to the admin search | `building_admin_modules/search.md` |
| Soft-deleting records into the Recycler | `building_admin_modules/recycler.md` |
| Adding a dashboard widget, stat card or chart | `building_admin_modules/dashboard_widgets.md` |
| Changing the default dashboard layout | `modules/dashboard.md` |
| Showing flash alerts | `building_sites/alerts.md` |
| Linking CSS and JS assets | `building_sites/assets.md` |
| Writing your own view components | `building_sites/components.md` |
| Asking visitors for cookie consent | `building_sites/consent.md` |
| Setting page titles, meta tags, scripts and styles | `building_sites/metadata.md` |
| Storing extra data on users or other entities | `building_sites/meta_info.md` |
| Reading and writing settings | `building_sites/settings.md` |
| Formatting dates with app_date() | `building_sites/common_functions.md` |
