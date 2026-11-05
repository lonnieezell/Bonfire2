# Scaffolding

`php spark content:scaffold <collection>` writes the views for a collection's pages into your app, as a place to start.
They show the fields of the collection's [blueprints](blueprints.md), and follow the recommended approach: the index view
uses the [pagination recipe](query_api.md#listing-a-collection), and the show view puts the entry's [SEO](seo.md) values in
the `<head>`. Once written, the files are yours. The command doesn't track them, and running it again doesn't update them.

```console
php spark content:scaffold blog
```

This writes two views:

- `app/Views/blog/index.php` lists the collection's published entries a page at a time, with each one's title as a link
  to its page, the first `text` or `textarea` field under it, and pager links.
- `app/Views/blog/show.php` shows one entry: its title, each field of the blueprints as a label and a value, and the
  body. It calls `content_seo()`, unless [`$autoSeo`](seo.md#doing-it-automatically) is on and does it already.

Both extend the App theme's `master` layout and fill its `main` section. A collection with several blueprints gets the
fields of all of them, so an entry shows a blank value for a field its own blueprint doesn't have. A `toggle` shows Yes or
No, a `terms` field its term slugs, and an `entries` field links to the entries it names that are published. Change any
of it to suit your design.

## Hooking them up

The command doesn't change your collection's config or your routes, so it ends by printing what to add. For the blog:

1. Set the show view as the collection's [template](routing.md#templates) in `content/collections/blog.yaml`:

    ```yaml
    template: blog/show
    ```

2. Add the index route to `app/Config/Routes.php`:

    ```php
    $routes->get('blog', static function (): string {
        $query = content()->collection('blog')->published();

        return render('App', 'blog/index', [
            'entries' => $query->paginate(10),
            'pager'   => $query->pager(),
        ]);
    });
    ```

The entries need URIs for the links to work, so the collection needs a [`route`](routing.md#serving-a-collection). If it has
none, the command says so first. Serving entries at their URIs also needs [`$autoRoute`](routing.md#turning-it-on) on.

## With a controller

Pass `--controller` to have the collection [hand off to a controller](routing.md#handing-off-to-a-controller) instead:

```console
php spark content:scaffold blog --controller
```

This writes the same two views and `app/Controllers/Blog.php`, whose `index()` lists the entries and whose `show()` calls
`content_seo()` and renders the show view. The class is named after the collection's handle, so `blog-posts` gets
`BlogPosts`. A handle that starts with a digit can't be a class name, and the command stops with an error.

The steps it prints are then:

```yaml
controller: App\Controllers\Blog::show
```

```php
$routes->get('blog', '\App\Controllers\Blog::index');
```

## Existing files

The command never overwrites a file. Any of the files that already exists is skipped and named in the output, and the
others are still written. To get a fresh copy of one, delete or rename yours and run the command again.

A collection that doesn't exist, or whose config or blueprints are invalid, is an error, and nothing is written.
