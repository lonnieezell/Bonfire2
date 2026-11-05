# Change Log

This holds the change history for Bonfire as we lead up to a 1.0 release. It's not exhaustive, but should give you a good idea of the changes that have been made and how it might impact you.

**IMPORTANT!** *Breaking changes* are marked with words `breaking change` in parentheses right after the date.

## 5 November 2026 (breaking change)

**The Recycler handles non-integer keys.** So that it can list resources with UUIDs, `CustomRecyclerRestore::recyclerRestore()`
and `CustomRecyclerPurge::recyclerPurge()` now take `int|string $id`, as do `RecyclableResource::restore()`, `purge()` and
`RecycleController::restore()` and `purge()`. **A model of yours that implements either interface with `int $id` is a PHP
fatal error when the class loads**, so change its signature to `int|string $id`. The key arrives from the URL as a string,
and the routes now take `(:segment)`. See [The Recycler](../building_admin_modules/recycler.md#overriding-the-restore).

**Recycler restore and purge are POST only.** They changed data on a GET request, so a forged link or image could make a
logged-in user restore or purge a record. `recycler-restore` and `recycler-purge` are now POST routes that always check the
CSRF token, even if your app doesn't turn the filter on globally, and the list uses forms. **If you link to these URLs, or
have your own copy of `listResource.php`, change the links to forms with `csrf_field()`.** A GET to either URL now gives a
404. See [The Recycler](../building_admin_modules/recycler.md#restoring-and-purging).

## 5 November 2026

**Content: draft preview.** An entry's form has a **Preview** button for users with `content.edit`. It gives a signed link,
good for 24 hours, that shows the entry through the collection's `template` or `controller` whatever its status, to anyone
who has the link and with no login, so a client can review a draft. The response is `noindex` and never cached, and a
collection with neither a template nor a controller has no preview. Links are signed with the app's encryption key. See
[Previewing a draft](../content/admin.md#previewing-a-draft).

**Content: page caching.** A collection with `cache: 3600` in its config has its routed pages served to guests from the cache
for that many seconds. It's off unless a collection sets it. Only `GET` requests with no query string from guests that the
fallback route serves with a 200 are cached. Saving, trashing, restoring or purging an entry, moving pages in the
structure, saving a term or menu, and any reindex (including `content:reindex`) clear all of Content's cached pages.
See [Caching](../content/routing.md#caching).

**Content: globals.** Keep site-wide values in `content/globals/<handle>.yaml`, describe them with a blueprint at
`resources/blueprints/globals/<handle>.yaml`, and read them anywhere with `globals('site')->field('name', 'default')`. The
value is cast by the blueprint's field type, and a missing one gives the default. Globals are read from the file, with no
index or migration, work with auto-routing off, and have no routes. See [Globals](../content/globals.md).

**Content: collection feeds.** `content()->feed('blog')` returns a response with a collection's published entries as an
RSS 2.0 feed, to return from a route of your own. An optional `/feed/{collection}` route is behind `$feedRoute` in
`app/Config/Content.php`, off by default, and your routes win over it. See [Feeds](../content/routing.md#feeds).

**Content: collection index pages.** [Listing a collection](../content/query_api.md#listing-a-collection) shows a route
and view of your own that list a collection's published entries a page at a time, with pager links from
`$query->pager()->links()`. Bonfire adds no index route, and `paginate()` still returns a plain list.

**Content: structure editor save bar.** Moving a page on the Edit structure screen now shows the save bar. Before, the bar
stayed hidden, so with JavaScript on there was no way to save a new order. Discard opens the page again, which puts the
pages back.

**Content: admin search.** The admin search box now finds entries. It matches the term in an entry's title or Markdown
body, skips trashed entries, and shows nothing to users without `content.access`. Results show the title, collection and
status, and link to the edit form for users with `content.edit`. See [Editing entries in the admin](../content/admin.md#search).

**Documentation in the admin.** Outside production, the sidebar has a **Documentation** group that shows this guide for
the version you have installed, with its structure read from `mkdocs.yml`. Anyone with `admin.access` can read it, and
production has no menu and answers with a 404. See [Documentation](../modules/documentation.md).

**Wider documentation nav.** On wider screens the page list beside the documentation grows to fit most titles, and a title
that still wraps no longer runs into the next one. Pages also read more easily, with larger body text, smaller headings
and shorter lines.

**Check your Debug Toolbar config.** If your app's `app/Config/Toolbar.php` has no `$disableOnHeaders` property, htmx
requests such as the Content preview fail after the first one in development. See
[If htmx stops working in development](installation.md#if-htmx-stops-working-in-development).

**Content: delete to trash.** Users with `content.delete` can delete an entry from the list or its form, which moves its
file to `content/.trash/<collection>/` and takes it off the site. **Content entries** in the Recycler restores or purges
it, with `recycler.view`. Deleting a page takes the pages below it, after a confirmation that says how many, and restoring
brings them back in their exact place in the tree. A trashed entry keeps its slug and URI, and an `entries` link to it
survives a save and works again after a restore. `content:reindex` goes by where the file is, so a trashed entry stays
trashed after `--fresh`. You probably want `content/.trash/` in your `.gitignore`, and a fresh clone followed by a reindex
then loses that machine's Recycler contents. See [Deleting and restoring](../content/admin.md#deleting-and-restoring).

**Content: navigation menus.** Add a config at `content/navigation/<handle>.yaml` and users with `content.structure` get
an editor under **Content** in the sidebar, to build a menu from entries and typed-in links. The items live in
`content/trees/navigation/<handle>.yaml`. `navigation('main')` returns them as a tree with `title`, `url`, `active` and
`children`, and leaves out an item, and those below it, when its entry isn't live. An item that links to an entry takes
its URL from the entry, so renaming a slug changes the menu by itself. The App theme has a sample partial at
`themes/App/_navigation.php`, which a theme you already published needs to copy. See
[Navigation menus](../content/navigation.md).

**The Recycler's resource chooser works again.** Picking another resource in the dropdown on Tools → Recycler did
nothing. It now swaps the list in place with htmx and starts on the first page of the chosen resource.

**Lithuanian translations completed.** `Content.php` and `Docs.php` now have Lithuanian files, and the remaining keys
were added to `Bonfire.php`, `Email.php`, `Tools.php`, `Users.php` and `Widgets.php`: the dashboard editor, Mail
Previews and Outbox, the mailer list, the log viewer and system health, and the users and chart widget settings.

## 4 November 2026

**Content: getting started and constraints.** [Getting started](../content/getting_started.md) walks you from a fresh
install to a published blog post. [Content](../content/index.md) now explains the file-first model and when to run
`php spark content:reindex`, which belongs in your deploy. [Constraints and known gaps](../content/constraints.md) lists
what Content doesn't do yet: it needs the content directory on one server or shared storage, it doesn't redirect old
URIs, it drops YAML comments when the admin saves, it doesn't use git, and it has no per-collection permissions.

**Content: pages and structures.** Give a collection `structure: true` (and optionally `max_depth`) and its entries
form a tree, kept in `content/trees/collections/<handle>.yaml`. The reindex gives each entry a `parent_id`, a `sort` and
a URI built from its parents, such as `/about/team`, which the front-end route serves. Renaming a page or moving it
updates the URIs below it. There are **no redirects** for the old URIs in this version. Users with `content.structure`
get an **Edit structure** screen with drag and drop, which refuses a tree deeper than `max_depth` or one that changed
on disk since it was opened. New entries go last at the root, and entries the tree file doesn't name are indexed there
too. See [Pages and structures](../content/structure.md).

**Content: taxonomies and terms.** Tags, categories and other taxonomies are now files. A taxonomy has a config at
`content/taxonomies/<handle>.yaml`, and each term is a YAML file in `content/taxonomies/<handle>/`. A collection opts in
with `taxonomies: [tags]`, and keeps an entry's terms as slugs in its front matter (`tags: [php]`). A new `terms` blueprint
field type tags entries from the admin. `php spark content:reindex` now fills the `content_terms` and
`content_entry_terms` tables from the files, and `content()->collection('blog')->whereTerm('tags', 'php')`
finds the published entries with a term. `content()->terms('tags')` lists the terms with their number of published entries. Terms
have a list, a form to create them and a form to edit them in the admin, which need the `content.structure` permission,
and with `$autoRoute` on a taxonomy can serve a page for each term from a `route` and a `template`. Nothing changes for a
site that has no taxonomies. See [Taxonomies](../content/taxonomies.md).

**Content: more field types, and your own.** Blueprints can use `select` (one option, or several with
`multiple: true`), `integer`, `datetime` and `entries`, which links to entries of the collections you name. Read the
links with `$entry->entries('related')`, which skips a target that has since been deleted or trashed, or look entries up
with `content()->findMany($ids)`. Register a field type of your own in `$fieldTypes` in `app/Config/Content.php`. See
[Field types](../content/field_types.md) and [Custom field types](../content/custom_field_types.md).

**Content: edit entries in the admin.** **Content** in the sidebar has an item for each collection, with a list of its
entries and a form to create and edit them. The form is built from the collection's blueprint in
`resources/blueprints/collections/<collection>/<blueprint>.yaml`, with `text`, `textarea`, `markdown`, `slug`, `toggle`
and `date` fields. Saving checks the input, writes the entry's Markdown file in one step and updates the index. It
refuses to save if the file changed on disk since the form was opened. Comments in an entry's front matter don't survive
a save. Publishing, and setting `published_at`, need their own permission. Nothing changes for a site that has no
collections. See [Editing entries](../content/admin.md), [Blueprints](../content/blueprints.md) and
[Field types](../content/field_types.md).

There are five new permissions: `content.access`, `content.edit`, `content.publish`, `content.delete` and
`content.structure`. The last two are held back for later and nothing uses them yet. If you published your own
`AuthGroups`, add them there, or **run `php spark bonfire:permissions`**, which declares them and grants `content.*` to
`superadmin` and `admin`. Until you do, Content stays hidden from those groups.

## 3 November 2026 (breaking change)

**`php spark bf:install` no longer overwrites theme files you already have.** Running it again used to copy Bonfire's
themes over `themes/`, discarding your edits to `Admin`, `App` and `Auth`. It now copies only the files that are missing
and says how many it left alone. To replace your files with Bonfire's anyway, run `php spark bf:install -f`; that also
discards your changes, so commit them first. If you counted on a second run to refresh the themes after an update, add
`-f` or [merge the files by hand](published_themes.md#what-re-publish-means).

**`php spark bf:install` says when it could not make a change.** It edits `.env`, `app/Config/*.php` and `composer.json`
by looking for the text the framework ships. When that text is not there, for example after a CodeIgniter update, the
installer used to skip the edit and still report success. It now names each file it could not update, what it looked
for, and exits with an error. Config files it cannot copy correctly are not written. The SQLite3 option for
`app/Config/Database.php` was one of these edits that did nothing on CodeIgniter 4.7. Running the installer again no
longer adds `app.indexPage` or the SQLite3 `.env` lines a second time, keeps an existing encryption key, and keeps the
final newline in `.env`.

## 3 November 2026

**Asset URLs are built and read by one class.** `asset()` and the `/assets/...` route now share `AssetLocator`, so the
URL format can't drift between them. The URLs themselves are unchanged. `asset()` now marks a path in an unknown folder
as a missing asset instead of raising a PHP warning, the route refuses `..` segments, and `asset_link()` escapes the
URL and any attributes you pass it.

**Content: serve entries on your front end.** Set `$autoRoute = true` in `app/Config/Content.php`, give a collection a
`route`, and a `template` or a `controller`, and run `php spark content:reindex`. Your routes always win, and only
published entries are served. `content()->findByUri()` does the same lookup for routes of your own, and
`$entry->content()` renders the Markdown. Nothing changes unless you turn this on. See
[Routing](../content/routing.md).

**Content: read file-based content from your code.** Keep entries as Markdown with YAML front matter under `content/`,
index them with `php spark content:reindex`, and query them with
`content()->collection('blog')->published()->paginate()`. Run `php spark migrate --all` to create the index tables.
Nothing changes until you do this: there's no admin screen or routing yet. See [Content](../content/index.md).

**One Mailable file that can't be loaded no longer breaks Mail Previews.** A parse error or a missing parent class in a
file in a Mails folder used to give a 500 on Tools → Mail Previews and on every preview and send. The list now shows that
Mailable with its error, its preview page shows the same error, and the others work as before.

**Mail Previews' Send to me no longer claims a discarded or logged mail was sent.** Sending through the `null` mailer now
says the message was discarded, and through `log` that it was written to the log, the same as the test send in
Settings → Email. A failover chain is judged by its first mailer.

## 2 November 2026 (breaking change)

**Mail Previews' Send to me drops more recipient headers.** `Resent-*` headers and `Disposition-Notification-To` are
removed from the preview, so sendmail can't deliver it, or a read receipt, to anyone but you. Reply-To is kept.

**Mail Previews' Send to me refuses a looping failover chain and hides secrets in errors.** A chain that loops now shows an
error and sends nothing, where before it crashed PHP. A failed send shows the error with the mailer's known secrets, and
those of its failover children, removed, the same as the test send in Settings → Email.

**Branded Markdown activation and two-factor emails.** New installs send them with your site name and accent color, and
a plain text part. This doesn't change your emails when you update: your published `app/Config/Auth.php` keeps its
HTML views until you add `action_email_activate_markdown` and `action_email_2fa_markdown` to `$views`. An app that never
published `Auth.php` does switch on update. The magic link email stays as it is. See
[Your email templates](../modules/email.md#your-email-templates).

**Settings → Email no longer edits mail transports.** The protocol, SMTP and sendmail fields are gone: mail is configured
in `app/Config/Mailer.php` and `.env`. The page now lists every mailer with its transport and settings, marks the
default, links failover chains to their mailers, and shows secrets only as **Set** or **Empty**. It warns while the
legacy bridge is active, while old transport rows are still in the settings table, and when the default mailer discards
mail or isn't defined. You can still edit the From name and address, and a new **Test send** mails a fixed message to an
address you type, through a mailer you choose. Installs on the bridge keep sending as before, but change these settings
in `.env` or `app/Config/Email.php`, or move to `Mailer.php` with `php spark bonfire:email-env`. See
[Email Settings](../modules/email.md).

**Tools → Mail Previews** shows your Mailables in the admin, in every environment: the HTML, plain text and raw MIME of
each one, and a **Send to me** button that mails it to your own address through a mailer you choose. It needs the new
`email.preview` permission. You publish your own `AuthGroups`, so **run `php spark bonfire:permissions`**, which grants
`email.*` to `superadmin` and `email.preview` to `admin`. Until you do, the screen stays hidden. See
[Mail Previews](../modules/mail_previews.md).

**Tools → Mail Outbox** logs every email your site sends and every send that fails: the addresses, subject, status,
error, attachment names and the Mailable that built it, never the message body, because magic link and password reset
emails carry live login tokens. Filter it by status, mailer, recipient and date. **Settings → Email** has a new **Days
to keep** (`Email.logRetentionDays`, 30 to start with); `0` turns the log off. `php spark email:prune` deletes older
rows, and about one send in a hundred prunes too. The Outbox needs the new `email.log` permission, granted to
`superadmin` only. **Run `php spark migrate --all`** to create the `email_log` table, and **`php spark
bonfire:permissions`** to grant the permission. Until you do, the screen stays hidden, and a missing table never stops
an email from sending. Every row is logged under the default mailer, because Postal doesn't tell listeners which named
mailer sent a message. See [Mail Outbox](../modules/mail_outbox.md).

Two new dashboard stat cards, **Mail sent, last 7 days** and **Mail failed, last 7 days**, come from the Outbox. The failed
card turns red while its count is above zero and links to the Outbox filtered to failures. They need `email.log` and are
hidden while **Days to keep** is `0`. A stat card can now turn red while its value is above zero with the new
`highlightNonZero` key, and take its value from a closure with `addValueUsing()`. See [Mail Outbox](../modules/mail_outbox.md#on-the-dashboard).

Mail keeps working after this update, and you don't have to do anything. Without an `app/Config/Mailer.php`, Bonfire
now makes a deprecated `legacy` mailer the default. It sends through the transport your `setting('Email.*')` values
describe, read at send time, so a Mailable no longer lands in Postal's `null` mailer, which discards it.

The breaking part: if your app sends Postal Mailables and has no `app/Config/Mailer.php`, those messages used to be
discarded and now really go out, through PHP's `mail()` if nothing is configured. To keep discarding them, for example
in development, publish a `Mailer.php` with `$default = 'null'`. Once that file exists, Bonfire steps aside completely.

The bridge logs a deprecation notice and is removed in 2.0. See
[The legacy mail bridge](../modules/email.md#the-legacy-mail-bridge) for how to leave it, and
[ADR 0008](https://github.com/lonnieezell/Bonfire2/blob/develop/docs/adr/0008-zero-touch-postal-upgrade.md) for why.

`php spark bonfire:email-env` helps you move mail transport settings out of the database. It prints `.env` lines and an
`app/Config/Mailer.php` snippet for your install, and labels where each value comes from. `--purge` then deletes the
transport settings from the settings table, including any plain text SMTP password, and refuses to run until the app
has its own Mailer config. See [Moving mail settings into code](../modules/email.md#moving-mail-settings-into-code).

Bonfire now sends its mail through Postal, which it requires. Mail keeps working: you don't have to change anything for
your activation, two-factor and magic link emails to go out. But the SMTP fields have left Settings → Email, so
run `php spark bonfire:email-env` to move that config into code.

The activation and two-factor emails are now the Postal Mailables `Bonfire\Auth\Mails\ActivationMail` and
`TwoFactorMail`. They still render the views named in `Auth.views`, so customised templates keep working. The activation
email is now always sent as HTML; before, it followed `Email.mailType` and came out as HTML source with the default
`text`. The magic link still goes through Shield.

The From name and address in Settings → Email are now the site-wide default: a Mailable without a From gets them, and
one with its own From keeps it. See
[How Bonfire sends mail](../modules/email.md#how-bonfire-sends-mail).

## 30 October 2026

The users list has an **Erase personal data** action, for one user or many. It runs every registered personal data
provider in one transaction: the user's name is replaced with `deleted-user-{id}`, their avatar, meta, sign-in
identities and remember tokens are deleted, their consent records are removed, and the account is deactivated,
soft-deleted and marked with a new `erased_at` column. The row stays so content that points at it keeps working; login
rows stay for security audits, without the email address. Erased users don't appear in the Recycler and can't be
restored or purged. It needs the new `users.erase` permission, which only `superadmin` has (through `users.*`). See
[ADR 0006](https://github.com/lonnieezell/Bonfire2/blob/develop/docs/adr/0006-erasure-anonymises-users.md).

Actions that run in the request, not only queued ones, are now recorded in `action_runs`, so every run has an audit
trail. With **Include completed runs** on, the Action runs dashboard widget lists them too.

A user's edit screen has an **Export data** button that downloads everything the site holds about them as
`user-{id}-data.json`: their account, meta, avatar, sign-in identities (without password or token hashes), login
history and consent records. Each export is logged as a notice. It needs the new `users.export` permission, which
`superadmin` has through `users.*`; run `php spark bonfire:permissions` to grant it to `admin`. Modules add their own
data to the export by implementing `Bonfire\Privacy\PersonalDataProvider` and registering it with
`service('personalData')->register()` from `initAdmin()`.

Three consent banner fixes. An absolute cookie policy URL (`https://...`) is now used as-is; before, it was run
through `site_url()`. The consent cookie now lasts `Consent.consentLength` days instead of a fixed 180, and is set
for the whole site. Closing the banner with its X now rejects all non-essential cookies, so it stops reappearing on
every page. If you published your own `consentFormScripts` view, copy the change from `consent_scripts.php`.

Items added straight to a menu with `Menu::addItem()`, outside any collection, now show in the sidebar, the command
palette and the breadcrumb. Before, the Admin theme skipped them. If you published the Admin theme, copy the change
from `Components/sidebar.php` and `_palette.php`.

The admin's settings screens (General, Users, Email, Consent, User Groups), System Info, the consent banner, the
site offline page and dashboard stat titles now take their text from language files, so they can be translated. New
keys have Italian, Lithuanian and Russian translations, and Spanish ones for `Bonfire.php` and `Settings.php`. These
are machine-made, so corrections from native speakers are welcome.

In Settings → Users, the "Remember for" option labelled "25 hours" kept users logged in for 24 hours. It now reads
"24 hours".

Logging in with **Remember me** no longer fails with a `TypeError` from `Time::addSeconds()`. Settings → Users saved
the remember length as the string the select posted, and Shield passes it straight to `Time::addSeconds()`, which
requires an int. The page now saves an int, the select shows the saved length as selected, and a migration casts
values saved by older versions: `php spark migrate --all`. A saved `0` (the "How long to remember..." placeholder)
becomes the 30-day default.

`php spark make:crud`, which never ran, is now `php spark make:module`. It writes a working admin module for an
existing model: a list with search, a create and edit form, row and bulk delete, permissions and a feature test. See
[Generating a Module](../building_admin_modules/generating_a_module.md).

New admin guides cover the shipped screens: [Users](../modules/users.md) (including Settings → Users),
[General Settings](../modules/settings.md), [Email Settings](../modules/email.md) and [Tools](../modules/tools.md)
(System Info and Logs). [Bonfire Modules](../modules/index.md) now lists every shipped module.

A new page, [Menus](../building_admin_modules/menus.md), lists every `MenuItem` key, the collection and menu methods,
custom menus, and reordering with `$menuWeights`.

A menu item's `altText` now shows as its link's tooltip (`title`) in the sidebar, the settings nav and the dashboard
quick links. Before, the Admin theme ignored it, and `MenuItem::altText()` threw a `TypeError` on an item without
one; it now returns `null`. If you published the Admin theme, copy the change from `Components/sidebar.php` and
`Components/settings-layout.php` into your copy.

A menu item or collection given an `iconUrl` now shows its icon. The `<img>` tag used `href` instead of `src`.

The [Authentication](../modules/auth.md) page now lists Bonfire's defaults for registration, magic links, redirects
and the email activation and 2FA actions, and shows where to change each one. It also documents the `Auth` theme's
`<x-auth-card>` and `<x-unsplash>` components.

[Common functions](../building_sites/common_functions.md) now covers every helper in `src/Common.php`: `has_error()`,
`error()`, `render()`, `admin_accent()` and `site_offline()`, alongside `app_date()`. A new Maintenance mode section
explains how to take the site offline, who can still get in, and how to get back in if you're locked out.

[View Components](../building_admin_modules/view_components.md) now lists the attributes of every component and has a
Pager templates section covering `bonfire_full`, `bonfire_simple`, `bonfire_list_hx` and the rest.

The docs map that `php spark bf:agents` writes into `AGENTS.md` now also points agents to the Auth, Consent, Meta Info
and Asset Management references, the request flow, published themes and updating Bonfire. Re-run the command to pick
it up.

The `bonfire_simple` pager template no longer prints a stray `?>` above its links.

A new page, [Testing Your Admin Module](../building_admin_modules/testing.md), shows how to write feature tests for a
module in your app: a base test case that registers Bonfire's modules, giving test users your module's permissions,
and testing access checks and htmx requests.

Bonfire now registers each module under its real namespace, like `Bonfire\Users` or `App\Modules\Blog`, instead of
`Bonfire\{Users}`, so `php spark migrate -n "Bonfire\Actions"` finds a module's migrations. `php spark migrate --all`
is unaffected. Rows already in your `migrations` table keep the old `Bonfire\{…}` namespace, so on an existing install
keep using `--all`: `-n` filters history by namespace and would try to run those migrations again.

## 30 October 2026 (breaking change)

Bonfire now deletes personal data it no longer needs on a schedule. A new Privacy config sets how many days to keep
it (`null` keeps it forever): login attempts and access token logins for 90 days, and consent records for 365 days once
a newer answer replaced them or their consent expired. `php spark bf:privacy:prune` does the work, and modules prune
their own data by implementing `Bonfire\Privacy\PrunesPersonalData`. System Info warns when pruning hasn't run for
over a week.

Bonfire now requires `codeigniter4/tasks`, and `codeigniter4/queue` moved from `suggest` to `require`. To schedule
the prune on an existing install:

1. Run `php spark tasks:publish` if you have no `app/Config/Tasks.php`, and remove the example tasks it contains.
2. Add this line to its `init()` method:

    ```php
    \Bonfire\Bonfire::schedule($schedule);
    ```

3. Run `php spark tasks:run` every minute from cron:

    ```
    * * * * * cd /path/to/your/site && php spark tasks:run >> /dev/null 2>&1
    ```

New installs get steps 1 and 2 from `php spark bf:install`.

The consent form can now be reopened, so visitors can change or withdraw consent: put `consent_settings_link()` in
your layout's footer. Saving posts to a new `consent-save` route, which sets the `bf_consent` cookie server-side,
appends a row to a new `consent_records` table (no IP address or user agent) and reloads the page, so `has_consent()`
checks apply at once. Closing the form with its X keeps what the visitor accepted before and rejects the rest, so on
a first visit it still rejects everything non-essential. Visitors who answered before are asked again when you add a consent, and any consent can be
marked `'locked' => true` (the default `required` consent is). The default text is translated. See
[Consent](../modules/consent.md).

Two things to do before you run `php spark migrate --all`:

- **Text edited in Settings → Consent:** the message, policy title and consents are no longer editable there, and the
  `ForgetConsentText` migration deletes their stored values so the config and language files take effect. Copy any
  text you changed into `app/Config/Consent.php` first, which `php spark bf:install` published. Language keys
  (`File.key`) are translated; anything else, like the English text already in your published config, is shown as-is.
  Add `'locked' => true` to its `required` consent, since the form no longer treats that key specially.
- **Custom `$consentForm` view:** the form is now a `<form>` that posts to `url_to('consent-save')`, with checkboxes
  named `consents[<alias>]` and submit buttons named `choice`. See
  [Customizing the Consent Form](../modules/consent.md#customizing-the-consent-form) for the full contract, and update
  your view to match. A custom `$consentFormScripts` view should drop its cookie handling; see `consent_scripts.php`.

A new `Settings.php` language file (`src/Settings/Language/`) holds the General Settings text. CodeIgniter merges
language files of the same name, so if one of your modules has a `Settings.php` language file, its keys now merge with
Bonfire's and a matching key changes Bonfire's text. Rename your file to avoid that.

## 29 October 2026

Four config keys now do what they say. `Users::$avatarResizeFloor` sets the smallest allowed avatar size,
`Users::$avatarDirectory` also sets where avatar links point, and `Users::$avatarNameBasis = 'username'` takes an
avatar's letters from the username, not the email. `Users::$includeMetaFieldsInSearech` is now spelled
`$includeMetaFieldsInSearch`; published configs that still use the old spelling keep working. The unused
`Themes::$haveComponents` is gone.

`POST admin/settings/groups`, without a group alias, now returns 404. It used to throw an `ArgumentCountError`.

A new page, [Configuration Reference](configuration.md), lists every key in the published config files with its
default and what it does, including keys that weren't documented before.

The Users sidebar item now needs `users.list`, the permission the users list page checks, not `users.view`. The
`admin` and `developer` groups now see it.

The Users, Email and System Info settings pages now check permissions in the controller, not just in the sidebar.
Users without `users.settings` (Users) or `admin.settings` (Email, System Info and PHP Info) are sent back to the
admin dashboard, even if they type the URL.

A new page, [Admin Controllers, Views and Themes](../building_admin_modules/controllers_and_views.md), covers
`AdminController`, the `render()` helper, the `master` layout's sections, and overriding the published Admin theme.

A module's `initFront()` now runs on public requests. Bonfire boots once per web request from a new `bonfire` filter,
which runs on every route after the `session` and `admin` filters, instead of from the `admin` filter. Bonfire now
caches the modules it finds in `Config\Bonfire::$appModules` for a minute; run `php spark cache:clear` to pick up a
new module folder straight away. See
[The Module File](../building_admin_modules/creating_an_admin_module.md#the-module-file).

A new tutorial, [Build Your First Admin Module](../building_admin_modules/tutorial_first_module.md), walks you
from an empty folder to a working list screen with a permission, search, filters, global search and the Recycler.
 
A module can now add its own permissions, and grant them to groups by default, from its `Config\Registrar` instead of
asking you to edit `app/Config/AuthGroups.php`. The new `php spark bonfire:permissions` command applies the grants.
New installs run it for you; **after updating, run `php spark bonfire:permissions` yourself**, and again whenever you
add a module. See [Permissions](../building_admin_modules/permissions.md).

A Composer package can now add a Bonfire module by listing it in the new `Config\Bonfire::$packageModules`, from
its own `Config\Registrar` so the app using it changes nothing. See
[Modules in Composer packages](../building_admin_modules/creating_an_admin_module.md#modules-in-composer-packages).

The email settings page now saves the SMTP port and timeout as integers. Sending SMTP mail no longer fails with a
`fsockopen()` `TypeError`, and port 465 turns on implicit SSL again. A custom port must now be a positive number.
Thanks to Alistair Wearring for the report and fix.

[Resource tabs](../building_admin_modules/resource_tabs.md) added with `service('resourceTabs')->addTabFor()` are now
shown as the current tab on their own page. On group pages, `(id)` in a tab's URL is now replaced with the group's
alias instead of being left in the link. The Resource Tabs page now shows how to build the page a tab links to.

`ComponentRenderer::render(null)` now returns an empty string instead of throwing a `TypeError`. Text like `<x- />`
is left alone instead of failing the page with "View not found for component". Paired components with one- or
two-letter names (`<x-em>…</x-em>`) now render, and a closing tag that doesn't match its opening tag's name is left
alone rather than rendering a shorter-named component. A `>` inside a quoted attribute of a paired component
(`<x-foo title="a>b">…</x-foo>`) no longer cuts the attribute short.

A new `php spark bf:agents` command writes a guide to Bonfire's conventions for AI coding agents into your project's
`AGENTS.md`: where things live, the rules for admin modules, and which docs page to read for each task. Your own text
in that file is kept, and running it again only replaces Bonfire's section. Add it to the `post-update-cmd` scripts of
your `composer.json` to keep the guide in step with the installed version. See
[Working With AI Agents](ai_agents.md).

A **Recycler** dashboard widget (`recycler:summary`) shows how many deleted records each Recycler resource holds,
leaving out those with none, and links each to its list in the Recycler. Resources other modules register with
`service('recycler')` appear in it without extra setup. It needs the `recycler.view` permission; add it from
**Add widget** on the dashboard. The existing "Users in Recycler" stat stays, since saved layouts use it.

A **System health** dashboard widget (`tools:systemHealth`) lists problems with the install: each file or folder in
`writable/` that can't be written to, a missing `.env` file, and server load above a limit you set (default 4). When
there are none it says so, and it links to the System Info page. It needs the `admin.settings` permission; add it from
**Add widget** on the dashboard. The checks moved into `Bonfire\Tools\Libraries\SystemHealth`, which System Info
now uses too.

An **Action runs** dashboard widget (`actions:runs`) lists your own queued, running and recently failed bulk action
runs, newest first, each linking to its progress. Settings choose how many to show, how many days of ended runs to
include and whether completed runs appear. It needs no permission; add it from **Add widget** on the dashboard. Opening
a run's progress link (`action-run`) directly now shows it inside the admin page rather than as a bare fragment.
`ActionRunModel` now returns `created_at` and `updated_at` as `CodeIgniter\I18n\Time` rather than strings.

A **Recently active** dashboard widget (`users:recentlyActive`) lists the users active within the last 5 minutes, hour
or 24 hours, most recent first, each linking to their account. It needs the `users.list` permission; add it from
**Add widget** on the dashboard.

A **Recent failed logins** dashboard widget (`users:failedLogins`) lists the newest unsuccessful login attempts across
all accounts, with the identifier, IP address and date. Attempts by a known user's email link to their Security tab for
admins who may open it. It needs the `users.view` permission; add it from **Add widget** on the dashboard.

## 29 October 2026 (breaking change)

An action run's lifecycle now lives in `service('actionRuns')` (`Bonfire\Actions\ActionRuns`): `dispatch()` runs an
action in the request or queues it, `process()` runs a queued run, and `progress()` and `toast()` look one up for the
admin who started it. `ActionRunModel` now reads and writes `status` as a `Bonfire\Actions\RunStatus` enum in place of
the strings `queued`, `running`, `done` and `failed`, so code that writes a status string to it must pass the enum.
The Admin theme's `<x-action-run>` now calls `service('actionRuns')->toast()` rather than `ActionRunModel`; installs
that published the Admin theme should copy the new `themes/Admin/Components/action-run.php`.

Picking an accent color swatch under **Settings → General → Appearance** now shows the save bar, so the new color can
be saved. Before, only the color picker itself did.

## 28 October 2026

`spark notify:breaking-changes` used to keep its marker in a `last-update.txt` file inside the package folder, which
`composer update` deleted together with the previous version, so it warned on every update. It now remembers the
breaking change it last reported in `writable/bonfire-breaking-changes.json`: expect one more warning after this
update, and none for changes you have already been shown.

The check also notices a note added to an existing `(breaking change)` heading, not only a new heading, and a fresh
install no longer warns about changes that came before it. If `writable/` is recreated on every deploy, move the
marker with `Config\Bonfire::$breakingChangeStateFile`. See
[Updating Bonfire](installation.md#updating-bonfire).

The dashboard's widget library now lists widgets by the module that adds them, in name order, in place of by type.
A widget's `group()` gives its module's name; the type keys `quickLinks`, `stats`, `charts` and `custom` still work,
each under its own heading. A `StatsItem` or `ChartsItem` takes `'module' => '…'` (see
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md)). With the library open, the page now widens by
its width instead of narrowing the dashboard, where the screen has room.

The sidebar now shows **Settings** as a single link to the first settings page you may open, since the settings
pages list one another in their own nav. A menu collection opts into this with `setPageNav()`. Installs that
published the Admin theme keep the old expanding Settings menu until they copy the new
`themes/Admin/Components/sidebar.php`.

Each admin can now change a dashboard widget's settings for their own dashboard: in edit mode, its sliders button
opens them in a panel, and they are saved with the layout (see
[The Dashboard](../modules/dashboard.md#widget-settings)). Stat cards, charts and Log
activity have settings; until an admin changes one, it follows the site-wide value in Settings → Widgets. Widgets
declare their settings with `fields()`, a list of `Bonfire\Dashboard\WidgetField`, and get their values in
`render(WidgetValues $values)`; `DashboardWidget::settingsUrl()` is gone (see
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md#settings)).

The Log activity widget now shows a column for each of the last days up to today, with a thin line for days without
a log file. Its **Days to show** setting (formerly *Log files to show*) keeps its saved value, and now defaults to 7.

Dashboard widgets can need a permission: pass `'permission'` to a `StatsItem` or `ChartsItem`, and admins who lack it
don't see it in the widget library or on their dashboard, nor is its data fetched for them. The built-in Users widgets
need `users.list` and the Groups widgets `groups.settings`. Admins with `widgets.settings` can **Save as site
default** from the dashboard's edit bar, and **Reset site default** in Settings → Widgets (see
[The Dashboard](../modules/dashboard.md#the-site-default)). The Settings → Widgets
switches are now labelled as choosing which widgets are *available*. Quick Links is now a native widget,
`Bonfire\Dashboard\Widgets\QuickLinksWidget`, registered by the Dashboard module under its old id, and no longer in
the default `Dashboard.cells`; installs that still list `Bonfire\Dashboard\DashboardCells::quickLinks` show it once,
in place. `DashboardCells::quickLinks()` is deprecated. Building widgets is documented in
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md).

## 28 October 2026 (breaking change)

`StatsItem::addValue()`, `addValueByFreeQuery()` and `ChartsItem::addDataset()` now store their query and run it
when the item's value or data is first read, which the dashboard does only for the widgets it shows. They keep
their signatures. `service('dashboardContext')`, `Bonfire\Widgets\DashboardContext`, `LayoutResolver::includes()`
and `CellWidget::aliasOf()` are gone: a value you build by hand belongs in a widget's `render()` (see
[Dashboard Widgets](../building_admin_modules/dashboard_widgets.md)), which the dashboard calls only for a widget
that is placed and permitted. A view that still calls the `WidgetCells::stats` or `::charts` cell directly now runs
the query of every item it lists. Tests that injected a `DashboardContext` mock no longer need one.

Settings → Widgets is gone. Permissions decide who may see a widget, and each admin's settings for a placed widget
decide how it looks, so the page's switches and defaults repeated them. Every widget is now offered to anyone who holds
its `permission()`: a widget switched off there is offered again. A placed widget follows its fields' coded
`default` until the admin changes a setting, so a site-wide default saved there, such as a chart's color scheme, no
longer applies. Run `php spark migrate --all`: a migration forgets those stored values. Delete any published copy
of the removed Config classes below (such as `app/Config/Stats.php`), which extends a class that is gone. This
replaces what the notes below say about Settings → Widgets. **Reset site default** has moved into the dashboard's edit
bar, next to **Save as site default**, for admins with `widgets.settings` (see
[The Dashboard](../modules/dashboard.md#the-site-default)). Code that used the removed
API: `WidgetField`'s factories no longer take `key:` (a key passed by position now lands in `description`), and
`WidgetField::inherited()` is gone (read `default`); `Bonfire\Widgets\WidgetOptions` and the
`Bonfire\Widgets\Config\Stats`, `LineChart`, `BarChart`, `DoughnutChart`, `PieChart`, `PolarAreaChart` and
`LogActivity` classes are gone; `ItemSettings` keeps only `widgetId()`; and `ItemWidget::available()` is gone. The
`widgets-settings` and `widgets-reset-layout` routes are gone; the reset is now `dashboard-site-layout-reset`.

The five *User classification by group* charts (line, bar, doughnut, pie and polar area) are gone: **Users by group**
shows the same numbers. Saved dashboards that placed one lose it: add Users by group from the widget library. The
*User Groups* stat card stays. `ChartsItem` is unchanged, so modules can still add charts of their own.

Log activity is now a dashboard widget of its own, `Bonfire\Tools\Widgets\LogActivity`, like Recent errors. It
keeps its id `logs:logActivity`, so saved layouts keep it in place, and its days and Debug defaults in Settings →
Widgets keep their saved values. It no longer has a switch under *Available widgets*: every admin with `logs.view`
can add it, even where it was switched off. It is no longer an item of `service('widgets')->widget('logs')`, so the
`Bonfire\Widgets\Cells\WidgetCells::logs` cell no longer shows it. Code that calls it directly: `days()` and
`levels()` now take the options as arguments, `days(int $count, bool $hideDebug)` and `levels(bool $hideDebug)`;
`render()` takes a `WidgetValues`; and `setTitle()`, `withOptions()`, `module()` and `settings()` are gone.

List rows are now selectable whenever the admin may run any of the list's bulk actions, not only when they may
delete: a module's bulk action shows the checkboxes on its own. A row's checkbox is disabled when no bulk action
`allows()` that record. `ActionList::selectable()` answers both, and `ActionList::showing()` hands a page's records to
`<x-row-actions>`, which no longer loads its record with one query per row once the view calling it does so. Installs that published the Admin theme
should copy the new `themes/Admin/Components/row-actions.php`, `themes/Admin/Search/users.php` and
`themes/Admin/_table_head.php`; `_table_head.php`
now shows the select-all checkbox only when `$showSelectAll` is true, rather than whenever it is set and the admin
has `users.delete`.

Each widget's `fields()` is now the only place its settings are declared: the Settings → Widgets tabs are built from
the fields that have a `key`, one tab per settings class, and saved through them. Values you saved there keep
working. The properties of `Bonfire\Widgets\Config\Stats`, `LineChart`, `BarChart`, `DoughnutChart`, `PieChart`,
`PolarAreaChart` and `LogActivity` are gone, so a default overridden in a published copy of those classes or in
`.env` no longer applies: set it in Settings → Widgets instead. The line chart's subtitle and custom border
settings, which had no form, are no longer read. `WidgetOptions` keeps only `all()`, `forAlias()`, `save()` and
`reset()`.

**Users by group** is now a bar list: each group, a bar scaled to the largest one, and its number of users, with a
setting to sort by count or by name. It is a new widget, `users:byGroup`, in place of the *Users by Group* stat card
(`stats:usersExpTable693`), which is gone. Saved dashboards that placed the stat card lose it: re-add Users by group
from the widget library. Two new widgets: **Newest users** (the latest users to join, linking to each; needs
`users.list`) and **Recent errors** (the newest errors, or errors and warnings, across the log files, linking to each
log file; needs `logs.view`). The default dashboard now has Users by group and Newest users in its **Users** section, in place of the
Users in Recycler stat, and Log activity and Recent errors side by side in **Monitoring** (see
[The Dashboard](../modules/dashboard.md#setting-it-in-config)). Installs that published the Admin theme need
the new `.bf-list-card`, `.bf-bars` and `.bf-rows` rules of `themes/Admin/css/admin.css`.

Lists have a shared system for row and bulk actions (see [Row and Bulk Actions](../building_admin_modules/actions.md)):
an action is one class, registered on a named list, run through `POST admin/actions/{list}/{action}` with permission,
CSRF and per-record checks, confirmation (with or without JavaScript), and an optional queue through
`codeigniter4/queue`. Run `php spark migrate --all` for its new `action_runs` table. The Users and Logs deletes use it,
which removes routes: `GET admin/users/{id}/delete` and `POST admin/users/delete-batch` are gone, as is
`UserController::delete()` and `deleteBatch()`. Links or forms that used them must post to
`admin/actions/users/delete-users` instead, with the IDs in `selects[]`. The Users and Logs list checkboxes are now
named `selects[]` with the ID as their value. Every inline `confirm()` is replaced by `<x-confirm-dialog>`, which also
handles `hx-confirm`. Installs that published their own copy of the Admin theme must re-publish it: `master.php` gains
`<x-confirm-dialog />` and `<x-action-run />`, and the lists use the new `<x-row-actions>` and `<x-bulk-actions>`.

`<x-switch>` gained an `offValue` attribute: the value its hidden input posts when the switch is off (`0` by default).
An empty `offValue` posts nothing, which Bonfire's own views now use for array names like `permissions[]`. Installs
that published their own copy of the Admin theme must re-publish it; an older copy posts `0` for every switch that is
off in those lists. The user edit screens (details, permissions and security) use the new admin layout.

The settings pages share a new `<x-settings-layout>` component (the Settings head and a sub-nav of the settings
pages). An older published copy of the Admin theme doesn't have it, so re-publish the theme or those pages fail to
render. Turning **Site Online** off now saves `0`; the offline check treats any empty value as offline.

The Logs, log view, Recycler and System Info pages use the new admin layout, with styles an older published copy of
the Admin theme doesn't have, so re-publish the theme. A log file can now be downloaded from its page.

A new **Log activity** dashboard widget charts the entries of the newest log files per level (see
[The Dashboard](../modules/dashboard.md#built-in-widgets)). Turn it on in Settings → Widgets; its own tab sets
how many files it shows and whether Debug entries are left out. It is shown by a new
`Bonfire\Widgets\Cells\WidgetCells::logs` cell: installs with their own `Config\Dashboard` must add that cell to
`$cells` to see it. Its styles are in the Admin theme, so re-publish the theme.

Each admin can customize their own dashboard (see [The Dashboard](../modules/dashboard.md#customizing-your-dashboard)):
**Customize** lets them drag widgets into a new order, group them into titled sections, size them (¼ / ⅓ / ½ / ⅔ /
Full, from their tools or by dragging a corner), remove them and add others from a widget library, all of it also from
the keyboard, with undo and redo. **Save layout** stores the layout as theirs alone; saving the default layout, after
resetting to it, forgets theirs. Every stat card, chart, Log activity and custom cell in `Dashboard.cells` is now
one widget, and modules can add their own by implementing `Bonfire\Dashboard\DashboardWidget` and calling
`service('dashboard')->register()` in `initAdmin()` (see [Dashboard Widgets](../building_admin_modules/dashboard_widgets.md#writing-a-widget)).
The switches in Settings → Widgets now decide which widgets are *available* in the palette, and are on unless a site
admin switches them off; their stored keys are unchanged. The default dashboard is the new `Dashboard.layout` config
property: Quick Links, a Users section (Users by Group and Users in Recycler) and a Monitoring section (Log activity).
Stats, charts and Log activity no longer appear on it by being switched on, and each admin adds the ones they want
from the widget library. Installs with their own `Config\Dashboard` and no `$layout` property get the plain cells of
`Dashboard.cells` (Quick Links and your own cells) instead. A stats or chart item now fetches its data only
when it is on the viewer's dashboard, so `service('dashboardContext')->shows()` answers "is it in the viewer's
layout". The dashboard needs the new `admin/js/dashboard.js` and new `admin.css` rules
from the Admin theme, so installs that published their own copy of the Admin theme must re-publish it; with an older
copy the dashboard shows its widgets stacked in one column and Customize does nothing.

Also fixed: a custom SMTP port is saved and shown again, and a user's name, profile fields and avatar initials are
escaped wherever the admin shows them. The Recycler's Restore button no longer asks for confirmation; Purge asks
first.

## 27 October 2026 (breaking change)

The Admin theme is being redesigned. Installs that published their own copy of the Admin theme must re-publish it:
Bonfire's admin views now use new View Components (`<x-setting-row>`, `<x-switch>`, `<x-segmented>`, `<x-chip>`,
`<x-status>`, `<x-empty-state>`, `<x-selection-bar>`, `<x-tabs>`, `<x-save-bar>`) that an older copy doesn't have,
and the shell (`master.php`, `_header.php`, `Components/sidebar.php`) has a new layout. `<x-page-head>` and
`<x-admin-box>` gained optional attributes and keep working as before without them; `<x-button>` no longer adds
`btn-lg`. Escape component attribute values with `esc()`: the renderer now decodes HTML entities in attribute
values before handing them to the component, so a custom component that prints an attribute must escape it. See
[View Components](../building_admin_modules/view_components.md).

Dashboard stats widgets are now neutral cards, so a `StatsItem`'s `bgColor` no longer has a visible effect.

The admin shell has a collapsible sidebar (remembered per viewer), a breadcrumb, a dark-mode toggle and a ⌘K / Ctrl+K
command palette that jumps to any admin page or runs a search. Admin pages below a menu item can name themselves in the
breadcrumb with a `crumb` view section (and `crumbParent`, when the page isn't under the active menu item).

The admin theme has an accent color. Change it under **Settings → General → Appearance**, or set it from code
with `setting('Site.adminAccent', '#2F6FEB')`. It must be a `#rrggbb` hex color. Buttons, links, focus rings and checked
inputs follow it. The admin also gains a dark mode that follows the operating system until a viewer picks one, and it
now uses the Geist font. Installs that published their own copy of the Admin theme need to re-publish it to get the
new tokens; until then the accent setting saves but has no visible effect.

## 11 October 2026 (breaking change)

Bonfire no longer adds a `users` rule group to `Config\Validation`. Nothing in Bonfire used it, and because
`Config\Validation` does not declare `$users`, it raised a "Creation of dynamic property" deprecation under PHP 8.2+.
The rules for the user form live in `Bonfire\Users\Config\Users::$validation`. If your app calls
`$this->validate('users')`, add the rules you need to your own `app/Config/Validation.php` as `public array $users`. If
you already declare `$users` there, Bonfire no longer merges its `email`, `username`, `first_name` and `last_name` rules
into it, so add any of those you rely on.

Fixes: Bonfire found no themes under CodeIgniter 4.7.5, so every admin and auth page failed with "`Admin` is not a
valid theme folder". `Theme::available()` now lists theme folders itself instead of using `get_dir_file_info()`, which
no longer returns folders.

## 8 October 2026 (breaking change)

The Recycler no longer guesses at what a model can do by checking `method_exists()`. A model that customizes the
Recycler must now implement the matching interface from `Bonfire\Recycler\Interfaces`: `CustomRecyclerQuery`
(`setupRecycler(): Model`), `CustomRecyclerRestore` (`recyclerRestore(int $id): bool`) and `CustomRecyclerPurge`
(`recyclerPurge(int $id): bool`). Methods with those names on a model that does not implement the interface are no
longer called. Without a custom hook, the Recycler now uses the model's own primary key and soft delete field instead of
assuming `id` and `deleted_at`, and refuses to restore or purge a record that is not in the Recycler.

Modules now register their recyclable resources with `service('recycler')->register()` from `initAdmin()`, instead of
editing `Config\Recycler::$resources`. The Users module registers `users` this way, so `$resources` is empty by default;
entries you already have in your own `Config\Recycler` keep working and take precedence over a module's. If you publish
the Recycler `listResource` view, it now receives `RecyclableResource` objects as `$resources` and `$currentResource`
(use `->label()`, `->columns()`, `->localizedColumns()`, `->alias()`) and no longer receives `$currentAlias`.

The logs in the Tools area are now read and deleted through `Bonfire\Tools\Libraries\LogStore`, which only accepts
file names like `log-2024-01-31`. `Logs::getAdjacentLogFiles()` moved to `LogStore::neighbours()` and
`Logs::paginateLogs()` takes the page as its third argument instead of reading `$_GET`. If you publish the logs list
view, each log now has a `name` (without extension) instead of a `filename`.

Fixes: viewing and deleting logs now requires the `logs.view` and `logs.manage` permissions; before, any user with
`admin.access` could read and delete the logs by calling the URLs directly. "Delete all" now removes only the log
files. The levels count in the logs list counts entries instead of every occurrence of a level's name in a message.

View Components now render self-closing (`<x-foo />`) and paired (`<x-foo>…</x-foo>`) tags through the same code, so the
two forms no longer differ by accident. Two behaviours change:

- A self-closing tag now passes its attributes to a component class, as the docs always said. Before, `<x-foo attr="bar" />`
  lost `attr` whenever a `FooComponent` class existed, so the class' `$this->data` was empty. If your class worked around
  that, it now receives the attributes.
- A tag whose view can't be found now always throws a `RuntimeException` (`View not found for component: foo`). Before,
  a paired tag with a missing view (or any error while rendering one) was swallowed and the raw `<x-foo>` tag stayed in
  the page. A regex failure while rendering components also throws now, instead of returning a number as the page.

What a component view sees (the attributes as variables, `$slot`), the lookup order (theme first, then
`componentsLookupPaths`) and the `Components/<name>.php` + `<Name>Component.php` convention are unchanged.

## 8 October 2026 (breaking change)

Everything about whether a dashboard widget is enabled, and what its display options are, now has one owner in
`Bonfire\Widgets`: `ItemSettings` (the enabled flag of an item), `DashboardContext` (are we on the dashboard, and does
this item show) and `WidgetOptions` (the options of the Stats widget and of each chart type). The settings keys stored
in your database are unchanged.

If you publish your own copy of the dashboard cell views (`Cells/stats.php`, `Cells/charts.php`, `Cells/scripts.php`)
or the widgets `settings.php` view, note that they no longer receive `$manager` from `Manager::manager()`, which has
been replaced by `Manager::items()`. Ask each item for `$item->settings()->enabled()` instead.

Widget items now also report an item's settings through `settings()`, which is part of the `Item` interface. If you
wrote your own item class implementing `Bonfire\Widgets\Interfaces\Item`, add `settings(): ItemSettings` to it. The
protected `$dashboardRoute` property of `StatsItem` and `ChartsItem` is gone. `StatsItem::addValue()`, `addValueByFreeQuery()` and `ChartsItem::addDataset()` still
only run their query on the dashboard when the item is enabled, and the check can now be swapped in tests through
`service('dashboardContext')`.

Items in a second collection of a widget now show up on the widgets settings page and on the dashboard.

## 8 October 2026

The rules for who may edit, ban, delete or assign groups and permissions to whom in the Users admin now live in one
place (`Bonfire\Users\Libraries\UserAccess`), and the views and the controller both use it. If you have published
your own copy of the Users views `form.php`, `permissions.php` or `_table.php`, they now receive an `$access` variable
(a `UserAccess` instance) instead of working out these rules themselves. Avatar storage moved to `AvatarStorage`.

Fixes: purging several users at once now removes the avatar and meta info of every purged user, not only the first.
A user with `users.edit` but without `me.edit` can no longer ban themselves.

## 18 March 2025 (breaking change)

The way widgets are enabled has changed in the database, so if you had widgets enabled before, they will
all be disabled. To clear the database of the orphaned data about enabled widgets run this in your DB:

```sql
DELETE FROM `settings`
WHERE `class` = 'Bonfire\Widgets\Config\Stats' AND `key` LIKE 'Stats_%';
DELETE FROM `settings`
WHERE `class` = 'Bonfire\Widgets\Config\Stats' AND `key` LIKE 'Charts_%';
```

## 26 February 2025 (breaking change)

Finished implementation of **<x-button\>** component in the Admin theme. Users need to update the
Admin theme (updated the component definition).

## 19 January 2025

Resource Meta Info (if you have configured such) can now be included in the Admin area search (see docs page
[Search](../building_admin_modules/search.md) for details).

## 18 January 2025 (breaking change)

Possibility to have Second Factor Authentication is added to Bonfire2, implementing Codeigniter Shield
feature.

To use the feature on existing installs you will first need to update the userspace Config file `app/Config/Auth.php`,
in particular – the property `$views`, two keys relating to 2FA to look like this:

```php
    'action_email_2fa'        => '\Bonfire\Views\Auth\email_2fa_show',
    'action_email_2fa_verify' => '\Bonfire\Views\Auth\email_2fa_verify',
```

Failing to do that and enabling 2FA in the admin area will lock the users out of your website.

To fix it, after modifying the config file, issue this command on your database to clean up references 
to wrong classes:

```sql
DELETE FROM settings WHERE class="Config\Auth" AND key="actions";
```

## 16 January 2025

Bonfire can henceforth warn you about breaking changes (like need to update Admin/Auth themes, change config files, etc).

To get such notifications when updating Bonfire, you can add a command to your
composer.json scripts section:

```json
    "scripts": {
        "post-update-cmd": [
            "php spark notify:breaking-changes"
        ]
    }
```

New Bonfire install will automatically include this command.

## 18 March 2024 (breaking change)

**<x-button\>** and **<x-button-container\>** view [components](https://github.com/lonnieezell/Bonfire2/blob/develop/docs/building_admin_modules/view_components.md) implemented.

When updating, copy files `themes/Admin/Components/button-container.php` and `themes/Admin/Components/button.php` into the corresponding folder of your project's Admin theme. See [#434](https://github.com/lonnieezell/Bonfire2/pull/434) for details about the change.
