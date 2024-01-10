# Asset Management

Bonfire provides a simple assets system that was created to solve two main challenges:

1. We should be able to serve CSS/JS assets from anywhere. While this is primarily for use by themes, it could serve many purposes.
2. How to easily handle browser caching/cache-busting with updated assets.

And try to do this in the simplest way possible.

For the examples, we will link to assets for the Admin theme.

## Linking to assets

Within your views you can link to assets anywhere within your project with the `asset()` or `asset_link()` helper function.
`asset_link()` takes two arguments. The first is the path to the file to load. The first segment of this path must be one of the defined
`$folders` in the configuration file. The rest of the path would be based on the actual file structure within that
location. For the `Admin` theme, a folder has been defined called `admin`. Both the `Auth` and `App` themes have
similar folders already defined.  

The second argument is the type of asset being loaded. This tells it how the resulting link should be formed.
Currently, it only supports `css` and `js` files.

```php
<?= asset_link('admin/css/admin.css', 'css') ?>
<?= asset_link('admin/js/admin.js', 'js') ?>
```

The `asset()` function is similar, except it only returns the URL. This is helpful as the function also inserts
a string within the filename that helps browsers cache the file, but that will change when the file is updated
so the browser knows to grab a fresh copy of the file.

```html
<link rel="stylesheet" href="<?= asset('admin/css/admin.css', 'css') ?>" />
<script src="<?= asset('admin/js/admin.js', 'js') ?>"></script>
```

## Configuration

The `Config\Assets` class has a handful of settings to customize the experience.

### $bustingType

When a link is generated it includes a string within the filename for cache-busting reasons. This would look something
like: `https://localhost:8080/admin/css/admin~~213264216523.css`. The config setting, `$bustingType` defines how this
string is derived. It has two possible values, either `file` or `version`.

The `file` setting will examine the requested file and use the file modified date/time as the basis, and convert it
to a Unix timestamp. This is the easiest value to use as it is automatically updates itself based on the file. However,
it does force an extra file read, and the overhead needed to get the information from the file. On many small to medium
sites this is likely just fine. However, large sites, or sites that require every ounce of performance, may want to
use the `version` method.

The `version` method requires the developer to set a new version in the configuration file whenever new code is ready
to deploy to staging or production environments. See the next setting for details. To make this easier during development
the current timestamp is used in testing/development environments ensuring that no caching will happen. In other
environments it inserts the version number that was specified.

### $separator

The `$separator` setting allows the app to detect the part of the asset file name that was 
added for cache-busting purposes. It can be a single web-safe non-reserved character or 
a combination of such characters (characters that are allowed in a URI, but do not have 
a reserved purpose) that DOES NOT OCCUR in your asset file names (like `~`, `-` 
or `_` or any combination of ASCII letters and numbers). Separator will be inserted 
before the file version/timestamp, in between the file name and file extension.

### $versions

The `$versions` setting allows you to define the version number for `css` and `js` separately. This value is used
as the cache-busting string when the `version` busting type is used.

```php
public $versions = [
    'css' => '1.0',
    'js' => '1.0',
];
```

### $folders

This values is where you define the folders that are looked in linking to an asset. Folders for all themes and
the vendor folder are setup by default.

```php
public $folders = [
    'app' => ROOTPATH.'themes/app',
    'admin' => ROOTPATH.'themes/Admin',
    'auth' => ROOTPATH.'themes/Auth',
    'other' => ROOTPATH.'vendor',
];
```

## Route Conflict Warning

To make this all work, a route is specified at `/assets/(:any)` to capture the request. The assets system will not
work if you override this route in your own application. I think for most sites this should not be a problem.
