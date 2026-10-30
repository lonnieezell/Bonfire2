# ADR 0005: No upgrade path from Bonfire 1

- **Status:** Accepted
- **Date:** 2026-10-30

## Context

Issue #707 asked for a guide to moving an app from Bonfire 1 to Bonfire 2.

Bonfire 1 is a CodeIgniter 3 application. Bonfire 2 is a Composer package for
CodeIgniter 4 (`codeigniter4/framework` ^4.7, PHP ^8.2). Nothing carries over
between them:

- **Framework.** CodeIgniter 4 is a rewrite of CodeIgniter 3, with namespaces,
  a new directory layout, and new controllers, models, routing and config.
- **Auth.** Bonfire 2 uses CodeIgniter Shield for users, groups and
  permissions, with its own tables.
- **Modules.** A Bonfire 2 module is a folder with a `Module.php` that extends
  `Bonfire\Core\BaseModule` and hooks into the admin from `initAdmin()`.
- **Themes.** Bonfire 2's admin UI is built from View Components on
  Bootstrap 5, Alpine.js and htmx, and installs publish their own copy of the
  theme.

Most of an upgrade guide would really be a CodeIgniter 3 → 4 guide, and
CodeIgniter already publishes one ("Upgrading from 3.x to 4.x" in its user
guide). The rest would map Bonfire 1 features to Bonfire 2 equivalents that
don't line up one-to-one, and we'd have to maintain and test it against an
application that is no longer developed.

## Decision

**Bonfire 2 doesn't support or document upgrading from Bonfire 1.** It ships
no compatibility layer, no migration commands and no data importers.

Moving a Bonfire 1 app to Bonfire 2 is a rewrite. Start a new CodeIgniter 4
app, install Bonfire 2, and port your own code using CodeIgniter's upgrade
guide.

**User accounts don't carry over.** Bonfire 1 users have to be imported into
Shield's tables by a script you write, or invited to reset their passwords.
Bonfire won't provide that script.

## Consequences

- No code or docs exist to keep Bonfire 1 working on Bonfire 2. Nothing has to
  stay compatible with it.
- The docs don't get a "Coming from Bonfire 1" page. Requests for upgrade help
  or tooling are closed as out of scope with a link to this ADR.
- This fits `docs/intro/ai_agents.md`, which already warns that agents mix in
  patterns from Bonfire 1 and older CodeIgniter versions.
- Teams on Bonfire 1 face a full port. We accept that cost; a Bonfire 1
  upgrade path is not on the roadmap.
