# ADR 0007: Data export and erasure are admin-only

- **Status:** Accepted
- **Date:** 2026-10-30

## Context

The GDPR epic (#746) adds export (Art. 15 and 20) and erasure (Art. 17) of a
user's personal data. Those requests could be run by admins on the user's
behalf, by the users themselves from the front end, or both.

Bonfire's own UI is the admin area. Its front end is limited to the Auth
screens; per ADR 0002 the developer owns the public site. Modules register
their hooks from `initAdmin()`, and `Bonfire::initModules()` runs either
`initAdmin()` or `initFront()` for a request, never both.

GDPR gives controllers a month to answer a request and requires them to verify
who is asking. It doesn't require self-service.

## Decision

**Admins run export and erasure.** Export is a download button on the user's
edit screen, behind `users.export`. Erasure is the `EraseUsers` Action on the
users list, behind `users.erase`. `superadmin` gets both; `admin` gets export
only, because erasure can't be undone.

**Bonfire ships no self-service pages.** `service('personalData')` with
`export()` and `erase()` is public, so a site that wants "Download my data" or
"Delete my account" builds it in its own front end. The docs show how.

**Providers register from `initAdmin()`,** like the Recycler, Actions and
Widgets.

## Consequences

- A person verifies each request before data leaves the site or is erased.
- Every erasure is audited in `action_runs`; every export is logged as a notice.
- Sites that want self-service write a small amount of code, and must load the
  providers themselves on front-end requests, since `initAdmin()` hasn't run.
  The guide (#752) covers this.
- `PersonalDataProvider` and `service('personalData')` become public API and
  part of the breaking-change surface.
