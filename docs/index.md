# Bonfire

Bonfire gives new or existing CodeIgniter 4 applications a ready-to-use admin area and the tools to build on it. It does
not aim to be a full CMS, but rather a framework to help you build your own web application or internal tools. These
docs are for the developers doing that building.

## Start here

<div class="grid cards" markdown>

-   __Install Bonfire__

    ---

    Add Bonfire to a CodeIgniter 4 app, set up the database and create your first admin user.

    [Installation](intro/installation.md)

-   __Build your first admin module__

    ---

    Go from an empty folder to a Posts screen with a menu item, a permission, search, filters and the Recycler.

    [Start the tutorial](building_admin_modules/tutorial_first_module.md)

-   __Use Bonfire on your public site__

    ---

    Add assets, view components, alerts, cookie consent and stored settings to the pages your visitors see.

    [Building Sites](building_sites/index.md)

-   __Look up a config key__

    ---

    Every config class Bonfire publishes to `app/Config`, with each key, its default and what it does.

    [Configuration Reference](intro/configuration.md)

-   __Work with an AI agent__

    ---

    Give coding agents Bonfire's conventions, so they write modules instead of plain controllers.

    [Working With AI Agents](intro/ai_agents.md)

-   __See what changed__

    ---

    What changed and when, with breaking changes marked.

    [Change Log](intro/changelog.md)

</div>

## Requirements and quick install

Bonfire needs PHP 8.2 or later and CodeIgniter 4.7 or later. From your project's root, run:

```bash
composer require lonnieezell/bonfire
php spark bf:install
```

The installer asks for your site's URL and database details, then publishes Bonfire's config files and themes into
your app. [Installation](intro/installation.md) covers the remaining steps: creating the database and your first user.

## What's included

### In the admin

- A [dashboard](modules/dashboard.md) each admin can arrange with their own widgets.
- [Users](modules/users.md), and [groups and permissions](building_admin_modules/permissions.md#managing-groups-in-the-admin),
  with login and registration through [Shield](modules/auth.md).
- [General](modules/settings.md) and [email](modules/email.md) settings screens.
- [System info and log viewers](modules/tools.md).
- A [Recycler](building_admin_modules/recycler.md) to restore or purge soft-deleted records.
- A [global search](building_admin_modules/search.md) your modules can add results to.

### For your admin modules

- [Menus](building_admin_modules/menus.md) to add your pages to the sidebar.
- [Filters](building_admin_modules/filters.md) and [row and bulk actions](building_admin_modules/actions.md) for lists.
- [Dashboard widgets](building_admin_modules/dashboard_widgets.md), including stat cards and charts.
- [Resource tabs](building_admin_modules/resource_tabs.md) on the user and group edit pages.
- [Admin view components](building_admin_modules/view_components.md) for consistent pages and forms.

### For your site

- [Themes](intro/published_themes.md) published into your app, and [asset handling](modules/assets.md) with cache-busting.
- [View components](building_sites/components.md) for reusable HTML.
- A [cookie consent](modules/consent.md) framework to help you comply with GDPR.
- [Meta info](modules/meta_info.md) to store extra fields on users or your own entities without changing their tables.
- A [settings library](building_sites/settings.md) that saves config values to the database.

## Getting help

Ask questions in [GitHub Discussions](https://github.com/lonnieezell/Bonfire2/discussions) and report bugs in
[GitHub Issues](https://github.com/lonnieezell/Bonfire2/issues).

## License

Bonfire is licensed under the MIT License - see the
[LICENSE](https://github.com/lonnieezell/Bonfire2/blob/develop/LICENSE) file for details.

## Acknowledgements

Every open-source project depends on its contributors to be a success. The following users have
contributed in one manner or another in making Bonfire:

<a href="https://github.com/lonnieezell/bonfire2/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=lonnieezell/bonfire2" alt="Bonfire contributors" />
</a>

Made with [contrib.rocks](https://contrib.rocks).
