# General Settings

The General settings screen holds the site's name, whether it's online, the admin's accent color, and how dates and
times are shown. Open **General** under **Settings** in the sidebar. You need the `admin.settings` permission.

Change what you need, then click **Save Settings**.

## Take the site offline

To work on the site without visitors seeing it:

1. Turn off **Site Online**.
2. Click **Save Settings**.

While the site is offline, visitors are sent to an offline page. Members of the Super Admin group, and users with
the `site.viewOffline` permission, can still use the whole site. Turn **Site Online** back on to open the site again.

A developer can change the offline page with `Site::$siteOfflineView`, see the
[Configuration Reference](../intro/configuration.md#site).

## Change the accent color

The accent color is used for buttons, links and active items across the admin. Click one of the four preset swatches,
or pick any color from the color picker. The admin previews the color as you pick; click **Save Settings** to keep
it.

## Settings

### General

| Setting | What it does |
| --- | --- |
| Site Name | The site's name. It appears in the admin, and the rest of the site can use it. Required. |
| Site Online | When off, only Super Admins and users with `site.viewOffline` can reach the site. |

### Appearance

| Setting | What it does |
| --- | --- |
| Accent Color | The admin's highlight color, as a six-digit hex color like `#2F6FEB`. |

### Date and Time

| Setting | What it does |
| --- | --- |
| Timezone | The site's timezone. Pick a region first, such as *America* or *Europe*, then a city. *UTC* needs no city. |
| Date Format | How dates are shown. Each choice shows today's date in that format: `mm/dd/yyyy`, `dd/mm/yyyy`, `dd-mm-yyyy`, `yyyy-mm-dd`, `mmm dd, yyyy` or `dd.mm.yyyy`. |
| Time Format | 12-hour (`3:45 PM`) or 24-hour (`15:45`). |

The admin uses the date and time formats wherever it shows a date, such as the logs and a user's recent logins.

## Items per page

How many rows admin lists show per page isn't set here. A developer sets it with `Site::$perPage`, 15 by default,
or with `setting('Site.perPage', 25)`. See the [Configuration Reference](../intro/configuration.md#site).
