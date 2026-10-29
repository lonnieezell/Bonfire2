# Add CSS and JS to Your Site Theme

This guide adds your own stylesheet and script to the public site's `App` theme, with cache-busting handled for you.
For every helper argument and config setting, see the [Asset Management](../modules/assets.md) reference.

## 1. Put the files in the theme

Save your files inside the `App` theme folder, for example:

```
themes/App/css/site.css
themes/App/js/site.js
```

## 2. Link them from the layout

The `app` folder alias points at the `App` theme, so start each path with `app/`. Add the links to
`themes/App/master.php`, next to the existing `asset_link()` calls:

```php
<?= asset_link('app/css/site.css', 'css') ?>
<?= asset_link('app/js/site.js', 'js', 'defer') ?>
```

Bonfire adds a cache-busting string to each URL, so browsers fetch the new file whenever it changes.

If you need only the URL, for example to write the tag yourself, use `asset()`:

```html
<link rel="stylesheet" href="<?= asset('app/css/site.css', 'css') ?>" />
```

## 3. Optional: switch to version-based cache-busting

By default the cache-busting string comes from each file's modified time, which costs a file read per link.
On busy sites, set a version instead in `app/Config/Assets.php` and bump it with each deploy:

```php
public $bustingType = 'version';

public $versions = [
    'css' => '1.1',
    'js'  => '1.1',
];
```

In the `development` and `testing` environments Bonfire uses the current time instead, so nothing is cached while you work.
