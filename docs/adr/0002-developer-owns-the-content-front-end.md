# ADR 0002: The developer owns the content front end

- **Status:** Proposed
- **Date:** 2026-10-27

## Context

The Content epic (#618) adds Statamic-style, file-first content management to
Bonfire as a core module (`src/Content/`): collections, entries, pages,
taxonomies and navigation, with an opt-in fallback route that serves entries
through templates (#610).

Every step towards a full CMS (Craft, Statamic, ExpressionEngine) lets Bonfire
own more of the public site: its URLs, its markup, what lands in the `<head>`,
how responses are cached. Bonfire's philosophy says the opposite. A tool should
provide "a solid foundation, and then get out of the way", and Bonfire's code
stays in `vendor` so the app is still a plain CodeIgniter app. ADR 0001 also
names the developer as Bonfire's buyer: their clients use the admin, but the
developer builds and owns the site.

As planned, #610 lets a collection hand a request only to a template. A
developer who needs logic between "entry found" and "page rendered" (extra
queries, an authorization check, a JSON response) has to drop content routing
for that collection and redo the URI lookup, the published check and the 404
themselves.

## Decision

**The front end belongs to the developer.** Content is a set of building
blocks the developer can use, override or switch off. It is never a layer that
takes the public site over.

**The query API stands alone.** `content()`, `entry()` and `navigation()` work
with auto-routing off, in any controller or view, with no dependency on themes
or templates. A site can use Content as an in-process headless store and never
enable routing.

**Routing is opt-in at every level.** `Config\Content::$autoRoute` is false by
default. App routes always win over content routes. A collection without a
`route` is never served by Bonfire.

**A collection hands off to a template or to a controller.** A collection
declares either `template` or `controller` (e.g.
`App\Controllers\Blog::show`), not both. With `controller`, Bonfire resolves the
URI, applies the published check and the 404, then calls the developer's method
with the resolved entry. What it returns is the response. The lookup is also
public (`content()->findByUri()`), so a hand-written route gets the same rules.

**Nothing is added to a response silently.** Features that change the output,
such as SEO tags from entry fields or page caching, are opt-in or can be turned
off. Each is also available as a plain helper or service the developer calls
from their own code.

**Generated code belongs to the app.** Scaffolded templates are written into the
app once, and from then on the developer owns them. Bonfire does not ship
templates that render public pages at runtime.

**Raw data is always available.** Entries expose their fields and raw Markdown
body as well as rendered HTML, so a developer can render content their own way.

## Consequences

- #610 grows: the `controller` hand-off and a public `findByUri()` join its
  scope.
- Collection index pages (e.g. `/blog`) default to app routes that use the query
  API. Any declared index route is a convenience on top, not the only way.
- Front-end follow-ups (SEO, feeds, sitemap, caching, preview, scaffolding) are
  each judged against this ADR: a feature that can only be had by giving
  Bonfire the response does not ship in that form.
- Bonfire will not offer a turnkey site with no code. Non-developers who want
  one need a theme or starter kit built on these blocks.
- A controller hand-off is a public contract: its method signature and the
  entry object it receives become part of the breaking-change surface.
