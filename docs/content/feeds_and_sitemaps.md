# Feeds and sitemaps

Content can build an RSS feed for a collection and a sitemap of the whole site. Each is a method that returns a
response, so you can serve it from any route of your own. If the default URL suits you, a config setting adds the route
for you instead.

Both list the same entries the [fallback route](routing.md#what-gets-served) serves: published, with a `published_at`
that has passed, and with a URI. Drafts, scheduled entries, entries in the trash and entries in a collection with no
`route` are left out.

## Feeds

`content()->feed('blog')` returns a response with a collection's published entries as an
[RSS 2.0](https://www.rssboard.org/rss-specification) feed. Return it from a route of your own, at any URL:

```php
$routes->get('blog/feed.xml', static fn () => content()->feed('blog'));
```

The response has the content type `application/rss+xml; charset=UTF-8`.

- **The channel** has the collection's `title` as its title and description, and the site's URL as its link.
- **Each item** has the entry's `title`, a `link` and a `guid` that are its URI as a full URL, and a `pubDate` from its
  `published_at`. There's no description, because an entry's fields and body are yours to render.
- **The order** is the collection's `sort`, which is newest first for a [dated](collections.md#filenames) collection.

Titles are escaped, and characters that XML can't hold are dropped, so the feed stays well-formed.

`feed()` includes every published entry, not only the latest. For a large collection, build your own feed with the
[query API](query_api.md) instead.

`feed()` throws `Bonfire\Content\ContentException` if the collection doesn't exist or its config is invalid.

### The built-in feed route

If you don't need a URL of your own, set `$feedRoute` in `app/Config/Content.php`:

```php
public bool $feedRoute = true;
```

Bonfire then serves every collection's feed at `/feed/{collection}`, such as `/feed/blog`, and gives a 404 for a
collection that doesn't exist. It's off by default, and works with `$autoRoute` on or off.

Your routes always win. A route of yours for `feed/blog`, or for the pattern `feed/(:segment)`, takes the request.

## Sitemap

`content()->sitemap()` returns a response with a [sitemap](https://www.sitemaps.org/protocol.html) of every collection's
published entries. Serve it from a route of your own:

```php
$routes->get('sitemap.xml', static fn () => content()->sitemap());
```

The response has the `application/xml` content type. It has one `<loc>` for each entry, which is the entry's URI added to
your `site_url()`, in alphabetical order.

!!! note

    A sitemap file is limited to 50,000 URLs, and `content()->sitemap()` doesn't split a larger site into several files.

### The built-in sitemap route

Instead of writing the route, set `$sitemap` in `app/Config/Content.php`:

```php
public bool $sitemap = true;
```

Bonfire then serves the sitemap at `/sitemap.xml`. It's off by default, and works with `$autoRoute` on or off. Your own
routes win over it, so a route of yours for `sitemap.xml` replaces it.
