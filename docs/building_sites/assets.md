# Including Assets

 As elaborated in [Assets Management](../modules/assets.md), Bonfire provides a simple assets system that was created to serve CSS/JS assets from anywhere and to easily handle browser caching/cache-busting with updated assets.

## Linking to assets

Within your views you can link to assets anywhere within your project with the `asset()` or `asset_link()` helper function.
`asset_link()` takes two arguments. The first is the path to the file to load. The first segment of this path must be one of the defined
`$folders` in the configuration file `Assets.php`. The rest of the path would be based on the actual file structure within that
location. For the `App` theme, a folder has been defined called `app`.

The second argument is the type of asset being loaded. Currently, it only supports `css` and `js` files.

```php
<?= asset_link('app/css/admin.css', 'css') ?>
<?= asset_link('app/js/admin.js', 'js') ?>
```

The `asset()` function is similar, except it only returns the URL only.

```html
<link rel="stylesheet" href="<?= asset('admin/css/admin.css', 'css') ?>" />
<script src="<?= asset('admin/js/admin.js', 'js') ?>"></script>
```

## Configuration

Configuration of assets is detailed in the [Assets Management](../modules/assets.md) page of **Modules** section.
