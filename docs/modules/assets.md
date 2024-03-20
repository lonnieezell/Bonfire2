# Asset Management

Bonfire provides a simple assets system that was created to solve two main challenges:

1. We should be able to serve CSS/JS assets from anywhere. While this is primarily for use by themes, it could serve many purposes.
2. How to easily handle browser caching/cache-busting with updated assets.

And try to do this in the simplest way possible.

For the examples, we will link to assets for the Admin theme.

!!! warning Route Conflict Warning

    To make this all work, a route is specified at `/assets/(:any)` to capture the request. The assets system will not work if you override this route in your own application. For most sites this should not be a problem.

## Loading the Helpers

As long as you use Bonfire's custom View system, this helper will be automatically loaded. Otherwise, you will need to load it manually.

```php
helper('assets');
```

## Defining Asset Locations

The first step is to define the locations where assets can be located. This is done in the `app/Config/Assets.php` file. The `$folders` array defines an alias for each folder that we can reference later, and the actual path to the folder.

```php
public $folders = [
    'app' => ROOTPATH.'themes/app',
    'admin' => ROOTPATH.'themes/Admin',
    'auth' => ROOTPATH.'themes/Auth',
    'other' => ROOTPATH.'vendor',
];
```

## Linking to assets

Within your views you can link to assets anywhere within your project with the `asset()` or `asset_link()` helper functions.

### asset_link()

`asset_link()` takes two arguments. The first is the path to the file to load. The first segment of this path must be one of the defined folders. The rest of the path would be based on the actual file structure within that location.

```php
{$folder alias}/path/to/file.{css|js}
```

For the `Admin` theme, a folder has been defined called `admin`. Both the `Auth` and `App` themes have similar folders already defined. Assuming there was a file at `themes/Admin/css/admin.css`, you would build the link to it like this:

```php
'admin/css/admin.css'
```

The second argument is the type of asset being loaded. This tells it how the resulting link should be formed.
Currently, it only supports `css` and `js` files.

```php
<?= asset_link('admin/css/admin.css', 'css') ?>
<?= asset_link('admin/js/admin.js', 'js') ?>
```

### asset()

The `asset()` function is similar, except it only returns the URL and allows you to include it in your own HTML tags manually if needed.

```html
<link rel="stylesheet" href="<?= asset('admin/css/admin.css', 'css') ?>" />
<script src="<?= asset('admin/js/admin.js', 'js') ?>"></script>
```

## Configuration

The `Config\Assets` class has a handful of settings to customize the experience.

### $bustingType

When a link is generated it includes a string within the filename for cache-busting reasons. This would look something like: `https://localhost:8080/admin/css/admin~~213264216523.css`. The config setting, `$bustingType` defines how this string is derived. It has two possible values, either `file` or `version`.

The `file` setting will examine the requested file and use the file modified date/time as the basis, and convert it to a Unix timestamp. This is the easiest value to use as it is automatically updates itself based on the file.However, it does force an extra file read, and the overhead needed to get the information from the file. On many small to medium sites this is likely just fine. However, large sites, or sites that require every ounce of performance, may want to use the `version` method.

The `version` method requires the developer to set a new version in the configuration file whenever new code is ready to deploy to staging or production environments. See the next setting for details. To make this easier during development the current timestamp is used in testing/development environments ensuring that no caching will happen. In other environments it inserts the version number that was specified.

### $separator

The `$separator` setting allows the app to detect the part of the asset file name that was added for cache-busting purposes. It can be a single web-safe non-reserved character or a combination of such characters (characters that are allowed in a URI, but do not have a reserved purpose) that DOES NOT OCCUR in your asset file names (like `~`, `-` or `_` or any combination of ASCII letters and numbers). Separator will be inserted before the file version/timestamp, in between the file name and file extension.

### $versions

The `$versions` setting allows you to define the version number for `css` and `js` separately. This value is used as the cache-busting string when the `version` busting type is used.

```php
public $versions = [
    'css' => '1.0',
    'js' => '1.0',
];
```

### $folders

This values is where you define the folders that are looked in linking to an asset. Folders for all themes and the vendor folder are setup by default.

```php
public $folders = [
    'app' => ROOTPATH.'themes/app',
    'admin' => ROOTPATH.'themes/Admin',
    'auth' => ROOTPATH.'themes/Auth',
    'other' => ROOTPATH.'vendor',
];
```
