# SEO

An entry can give you the values for a page's `<title>`, description, canonical link and Open Graph tags. You decide
whether they reach the page: call `content_seo()` yourself, or turn on `$autoSeo` for pages Bonfire renders from a template.
Bonfire adds nothing to a page that you haven't asked for.

## Getting the values

`$entry->seo()` returns an array:

```php
$seo = $entry->seo();

$seo['title'];       // 'Hello world'
$seo['description']; // 'The first paragraph of the body, as plain text.'
$seo['canonical'];   // 'https://example.com/blog/hello-world', or null
$seo['og'];          // ['og:title' => ..., 'og:description' => ..., 'og:type' => 'website', 'og:url' => ...]
                     // with no entry for a value that is empty
```

The values are plain text, so escape them with `esc()` when you print them yourself.

- **Title.** The field the blueprint maps to `title`, or else the entry's `title`.
- **Description.** The field the blueprint maps to `description`, or else the first paragraph of the body with its Markdown
  and HTML removed and its line breaks collapsed. A paragraph with no text, such as one that holds only an image, is
  skipped. Without any it is an empty string, and `content_seo()` leaves the description tags out.
- **Canonical URL.** The entry's `uri` on your site's `baseURL`. It is `null`, and `og:url` is left out, for an entry whose
  collection has no [route](routing.md#serving-a-collection). In a [structured](structure.md) collection the URI comes from
  the tree, and an entry that isn't in it has none.
- **Open Graph.** `og:title` and `og:description` repeat the title and description, `og:type` is `website`, and `og:url` is
  the canonical URL.

A mapped field that is missing or blank falls back to the default, so an entry can leave a field empty.

## Mapping fields in a blueprint

Add a `seo` key to the [blueprint](blueprints.md) that names the field for each value:

```yaml
title: Post
seo:
  title: seo_title
  description: summary
fields:
  - handle: title
    type: text
  - handle: seo_title
    type: text
    label: Title for search engines
  - handle: summary
    type: textarea
  - handle: body
    type: markdown
```

`seo` can have `title` and `description`, and each must be the handle of a field in the same blueprint. Anything else
makes the blueprint invalid, and the entry form won't open until you fix it. An entry whose blueprint is missing or broken
at the time of the request uses the defaults, and the reason is logged.

## Putting the values in the head

`content_seo($entry)` adds the values to the `viewMeta` service, which sets the title, and adds the description and Open
Graph `meta` tags and the canonical `link`. It escapes each value. Call it in your controller, or in a hand-written route
that uses [`findByUri()`](routing.md#finding-an-entry-by-uri):

```php
public function show(Entry $entry)
{
    content_seo($entry);

    return render('App', 'blog/show', ['entry' => $entry]);
}
```

Your layout has to print what the service holds. This is the `<head>` of `themes/App/master.php`:

```php
<?= $viewMeta->render('meta') ?>
<?= $viewMeta->render('title') ?>
<?= $viewMeta->render('link') ?>
```

The `link` line was added to the App theme's layout along with `content_seo()`. If you keep your own copy of the layout, add
it, or the canonical link won't appear. See [View metadata](../building_sites/metadata.md).

Call `content_seo()` once per page. A second call adds the tags again, and so does calling it for a page that `$autoSeo`
already handled. To change the title, set it after the call with `service('viewMeta')->setTitle()`, and know that
`og:title` keeps the value the entry gave.

## Doing it automatically

Set `$autoSeo` in `app/Config/Content.php`:

```php
public bool $autoSeo = true;
```

Now a page that the [fallback route](routing.md#templates) renders from a collection's `template` gets the tags with no
code of yours.
`$autoSeo` is off by default.

A collection that hands off to a `controller` is never changed by `$autoSeo`. What the controller returns is the response,
so call `content_seo()` there if you want the tags.
