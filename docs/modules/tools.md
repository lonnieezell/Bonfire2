# Tools

The Tools module adds two screens under **Tools** in the sidebar: **System Info**, a summary of the server Bonfire runs
on, and **Logs**, a viewer for the site's log files. It also adds three [dashboard](dashboard.md) widgets:
*Log activity*, *Recent errors* and *System health*.

## System Info

Open **System Info** under **Tools**. You need the `admin.settings` permission.

### Server

| Item | What it shows |
| --- | --- |
| PHP Version | The version of PHP running the site. |
| CodeIgniter Version | The version of CodeIgniter installed. |
| SQL Engine | The database driver and the database server's version. |
| Server OS | The server's operating system, release and architecture. |
| Server Load | The server's load average over the last minute, or *Unknown* where the server doesn't report it. |
| Max Upload | The largest file PHP accepts in an upload (`upload_max_filesize`). |
| Max POST | The largest form submission PHP accepts (`post_max_size`). |
| Memory Limit | The most memory one request may use (`memory_limit`). |

### Filesystem

Bonfire writes logs, cache files and uploads to the `/writable` folder. This section shows:

- whether a `.env` file is present. *Missing* is a warning, not an error: a site can be set up without one;
- whether `/writable` and each file and folder in it can be written to, with the size and date of each file.

If anything there is *Not writable*, a warning appears at the top of the section. Ask whoever runs your server to let
the web server write to that folder.

### Share your system details

When you report a bug, the details on this screen help others reproduce it:

1. Click **Copy as text**. The button says *Copied* once the details are on your clipboard.
2. Paste them into your bug report.

**View PHP Info** opens PHP's full configuration report in a new tab. It can hold paths and other details about your
server, so check it before you share it.

## Logs

Open **Logs** under **Tools**. You need the `logs.view` permission to read logs, and `logs.manage` to delete them.

The site writes one log file per day to `writable/logs`. What gets logged is set in `app/Config/Logger.php`.

### The log list

The list shows one row per file, newest first: the date, a bar and tags counting the entries at each level, the
total number of entries, and the file name.

Click **With errors** to show only files that have error, critical, alert or emergency entries, or
**With warnings** for files with warnings. These chips filter the files on the current page.

### Read a log file

Click a date in the list. The file's entries are shown in the order they were logged, each with its level, time and message. Entries
with a stack trace have a **Stack trace** line you can click to expand.

To find an entry:

- choose a level: **All**, **Errors** (error, critical, alert and emergency), **Warnings**, **Info** (info and
  notice) or **Debug**;
- type in **Search messages** to show only entries whose message contains your text.

Both filter the entries on the current page. The arrows at the top right move to the day before or after.

### Download a log file

Open the file and click **Download**. You get the raw `.log` file, for example to attach to a bug report.

### Delete log files

You need the `logs.manage` permission. Deleted log files can't be restored.

- **One file**: open it, click **Delete log file** and confirm.
- **Some files**: tick them in the list, choose **Delete** from the bar at the bottom and confirm.
- **Every file**: click **Delete all log files** above the list and confirm.
