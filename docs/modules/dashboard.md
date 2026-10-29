# The Dashboard

The Dashboard is the first page you see when you log into the admin area. It holds widgets that give a quick overview
of the site's status, or other information that is important to you.

Every admin can arrange their own dashboard without changing anyone else's. Until they do, they see the
[site default](#the-site-default). To write a widget of your own, see
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md).

## Customizing your dashboard

Click **Customize** to enter edit mode. Nothing is stored until you click **Save layout**; **Cancel** puts the
dashboard back the way it was. Every change can be undone and redone with the arrows in the edit bar, or Ctrl+Z
(⌘Z) and Shift+Ctrl+Z, and the message after a removal or deletion has its own **Undo**.

In edit mode you can:

- drag a widget to reorder it, also into another section;
- change a widget's [settings](#widget-settings) from its sliders button, when it has any;
- remove a widget with its trash button;
- reset to the site default with the clock button. Saving the site default as your layout forgets yours, so you
  follow the site default again when a site admin changes it.

### Sections

A layout is a list of sections, each with an optional heading. From a section's ⋯ menu you can add a widget to it,
move it up or down, insert a section below it, or delete it along with its widgets. Sections left without widgets
are dropped on save, and a layout holds at most 20 sections.

### Sizes

Size a widget from the tools above it, or drag its bottom-right corner. The sizes are ¼, ⅓, ½, ⅔ and Full width
(3, 4, 6, 8 or 12 of 12 columns), within the range that widget allows. Quick Links is always full width.

### The widget library

**Add widget** opens the library. It lists every widget available to you, grouped by the module that adds it, and
marks the ones already on your dashboard. Click **Add**, or drag one straight onto a section. An added widget shows a
placeholder until you save.

### Keyboard and screen readers

Every edit also works from the keyboard. With a widget focused:

| Key | Does |
| --- | --- |
| Arrow keys | Move it. Up and Down, and Left and Right past either end, move it to the section before or after. |
| Shift + Left / Right | Resize it. |
| Delete | Remove it. |

Screen readers hear each change announced.

## Widget settings

A widget's sliders button opens its settings in the panel on the right, with the widget outlined on the dashboard.
The settings are your own: they change that widget on your dashboard only. **Apply** keeps them as part of your
edit, so undo, **Cancel** and **Save layout** cover them like any other change, and the widget shows *Changes apply on
save* until you save. A widget you just added can be set up before you save, too.

Until you change a setting, it uses the widget's default. Once you change it, yours is kept. **Reset to defaults**
fills the panel with the widget's defaults again. Widgets without settings, such as Quick Links, have no sliders
button.

| Widget | Settings |
| --- | --- |
| Stat cards | Show the "View Detail" link |
| Line chart | Show the title, Show the legend, Legend position, Enable animation, Line tension |
| Bar, doughnut, pie and polar area charts | Show the title, Show the legend, Legend position, Enable animation, Color scheme |
| Log activity | Days to show, Hide Debug entries |
| Users by group | Sort by most users first or by group name |
| Newest users | Users to show (1–10) |
| Recent failed logins | Attempts to show (1–20) |
| Recently active | Active within (5 minutes, 1 hour, 24 hours), Users to show (1–10) |
| Action runs | Runs to show (1–20), Days to show (1–30), Include completed runs |
| Recent errors | Entries to show (1–20), Show errors only or errors and warnings |
| System health | Warn when server load is above (0.5–100) |

**Save as site default** carries each widget's settings into the site default.

## Built-in widgets

A widget's permission decides who may use it. Admins who lack it don't see it in their library or on their
dashboard, its data is never fetched for them, and it is dropped from any layout they save. A widget that becomes
available later shows up in the library of admins who have customized, never in their layout.

| Widget | Shows | Permission | Default layout |
| --- | --- | --- | --- |
| Quick Links | A greeting, and a link to each item of the sidebar's `Content` section you may see. | none | Yes |
| Users by group | Each group, a bar scaled to the largest one, and its number of users. | `users.list` | Yes, in **Users** |
| Newest users | The latest users to join, linking to each. | `users.list` | Yes, in **Users** |
| Recently active | The users active within the last 5 minutes, hour or 24 hours, most recent first, with how long ago, linking to each. Users who have never been active don't appear. | `users.list` | No |
| Users in Recycler | A stat card counting deleted users, linking to them in the Recycler. | `users.list` | No |
| Recent failed logins | The newest unsuccessful login attempts across all accounts: who tried, from which IP address, and when. An attempt whose email belongs to a user links to that user's Security tab, for admins who may open it. | `users.view` | No |
| User Groups | A stat card counting the groups, linking to the group settings. | `groups.settings` | No |
| Log activity | One stacked bar per day up to today, split into Error, Warning, Info and Debug entries, with a thin line for days without a log file. CRITICAL, ALERT and EMERGENCY count as Error, NOTICE as Info. Each bar links to that log file, and the headline counts the errors across the days shown. | `logs.view` | Yes, in **Monitoring** |
| Recent errors | The newest errors, or errors and warnings, across the log files, linking to each log file. | `logs.view` | Yes, in **Monitoring** |
| System health | What is wrong with the install, if anything: each file or folder in `writable/` that can't be written to, a missing `.env` file, and a 1-minute server load above **Warn when server load is above** (default 4). With no problems it says so in one line. Links to the System Info page. | `admin.settings` | No |
| Recycler | Each resource in the Recycler that has deleted records, with how many, linking to its list. When the Recycler is empty it says so in one line. | `recycler.view` | No |
| Action runs | Your own [queued bulk action runs](../building_admin_modules/actions.md) that need attention: queued and running runs, plus runs that failed in the last **Days to show**. Turn on **Include completed runs** to list finished runs too. Each row links to the run's progress page. | none: admins only see the runs they started | No |

Widgets marked *No* are added from the widget library.

## The site default

Admins who have not saved their own layout see the site default. The dashboard picks the first of these that exists:

1. the admin's own saved layout;
2. the site default saved with **Save as site default**;
3. the `$layout` of the Dashboard config file, or, when that is `null`, the cells of `$cells` in order (see
   [Legacy: view cells](../building_admin_modules/dashboard_widgets.md#legacy-view-cells)).

### Saving it from the dashboard

Admins with the `widgets.settings` permission get **Save as site default** in the edit bar. It stores the layout as
shown as the site default, which every admin without their own layout then sees, and forgets the saver's own layout so
they follow it too. Other admins who customized keep theirs. Widgets the saver may not see aren't in their editor, so
they aren't in the saved default either.

**Reset site default**, next to it in the edit bar, forgets the stored site default, so it goes back to the configured
`$layout`. It asks first. Admins' own layouts are kept.

### Setting it in config

Out of the box, the site default is the `$layout` property of the Dashboard config file:

- the quick links;
- a **Users** section with Users by group and Newest users, each half the width;
- a **Monitoring** section with Log activity and Recent errors, each half the width.

```php
public ?array $layout = [
    'version'  => 1,
    'sections' => [
        ['title' => '', 'widgets' => [
            ['id' => 'cell:Bonfire\Dashboard\DashboardCells::quickLinks', 'size' => 'full'],
        ]],
        ['title' => 'Users', 'widgets' => [
            ['id' => 'users:byGroup', 'size' => 'half'],
            ['id' => 'users:newest', 'size' => 'half'],
        ]],
        ['title' => 'Monitoring', 'widgets' => [
            ['id' => 'logs:logActivity', 'size' => 'half'],
            ['id' => 'tools:recentErrors', 'size' => 'half'],
        ]],
    ],
];
```

Change it to give every admin without their own layout, while no site default is saved, a different one. Each entry
is a widget's id and its size (`quarter`, `third`, `half`, `two-thirds` or `full`). Widgets the viewer may not see are
left out, and so is a section left empty.
