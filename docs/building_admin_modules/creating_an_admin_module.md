# Creating an Admin Module

The bulk of your work in the admin area will likely be done creating new modules of code. Modules are groups of code
centered around a single task or resource, like Users, Photos, etc. For the most part, admin modules are like any
other bit of module CodeIgniter code, except for a new `Module.php` file that helps the module integrate
into the admin area. Other than that you will be able to take advantage of solutions that allow your code to integrate
directly into settings pages, be part of the global search results, automatically display and manage filtering of
a resource list, or show up in the Recycler.

A good module to look at when creating your own is the Bonfire's own Users module (`src/Users`). You can also look at an
example module [Pages](https://github.com/dgvirtual/bonfire2-pages-module), which is an implementation of a module of 
Bonfire 2 admin panel for administration of pages on a website.

## Configuring Module Locations

In order for Bonfire to discover your modules, you need to specify one or more locations that you plan on storing the modules. No location is set by default: the `app/Modules` entry in `Config\Bonfire` ships commented out, so uncomment it (or add your own, see below) before Bonfire will find your modules. As an example, we could create a Blog folder to store our new blog package. You would then need to create the standard directories, `Config`, `Controllers`, `Models` and `Views` at the very least. A `Module.php` file is also expected to live here, which we will look at in more detail a little later. The directory structure would look like:

```
app/
    Modules/
        Blog/
            Config/
            Controllers/
            Models/
            Views/
            Module.php
```

All files within the Blog directory would be expected to live within the `App\Modules\{ModuleName}` namespace, i.e. `App\Modules\Blog\Config\Routes`. To enable discovery, uncomment the entry in the `$appModules` setting of `Config\Bonfire`. You can customize the namespace by changing its key.

```php
public $appModules = [
    'App\Modules' => APPPATH .'Modules',
];
```

For example, if I would prefer the modules to simply live within my company's namespace, `Acme`, I could edit the key to reflect that:

```php
public $appModules = [
    'Acme' => APPPATH .'Modules',
];
```

If you have multiple locations you need to keep modules, you can add multiple entries here and they will all be automatically discovered and registered within Bonfire.

## Modules in Composer packages

`$appModules` treats every folder in a location as a module, so it doesn't fit a Composer package that is a single
module. List such a module in `$packageModules` instead. Each entry maps the module's own namespace to the
directory that holds its `Module.php`:

```php
public array $packageModules = [
    'Acme\Blog' => ROOTPATH . 'vendor/acme/blog/src',
];
```

Composer already autoloads the package's classes, so Bonfire only needs to find its `Module.php`. An entry whose
directory or `Module.php` is missing is skipped.

A package can register itself, so the app using it changes nothing. Add a `Config\Registrar` class to the package
with a `Bonfire()` method, and CodeIgniter merges what it returns into `Config\Bonfire`, including an app's
published copy:

```php
namespace Acme\Blog\Config;

class Registrar
{
    public static function Bonfire(): array
    {
        return [
            'packageModules' => ['Acme\Blog' => realpath(__DIR__ . '/..')],
        ];
    }
}
```

CodeIgniter only finds registrars in Composer packages while `Config\Modules::$discoverInComposer` is `true`, its
default.

## The Module File

The Module file handles initialization functions when the admin is loaded. It allows the module to create new
menus, insert itself into the existing sidebar menus, etc. The `initAdmin` method is where all of the setup
for displaying and admin page should be done.

```php
namespace Acme\Blog;

use Bonfire\Core\BaseModule;
use Bonfire\Menus\MenuItem;

class Module extends BaseModule
{
    public function initAdmin()
    {
        //
    }
}
```

### Adding a Menu Item

The main side navigation in the admin consists of three primary menu collections:

- a main `Content` collection where you would place the main page for managing a resource (like Users)
- the `Settings` collection where your module's settings pages should live.
- the `Tools` collection which holds miscellaneous things that are helpful, but not needed on a daily basis.

Menu items are added to the sidebar using the `menus` service. Below are two examples taken from the core
Users module. They share common setup which will be discussed after the example:

```php
// Settings menu for sidebar
$sidebar = service('menus');
$item    = new MenuItem([
    'title'           => 'Users',
    'namedRoute'      => 'user-settings',
    'fontAwesomeIcon' => 'fas fa-user',
    'permission'      => 'users.settings',
]);
$sidebar->menu('sidebar')->collection('settings')->addItem($item);

// Content Menu for sidebar
$item = new MenuItem([
    'title'           => 'Users',
    'namedRoute'      => 'user-list',
    'fontAwesomeIcon' => 'fas fa-users',
    'permission'      => 'users.list',
]);
$sidebar->menu('sidebar')->collection('content')->addItem($item);
```

`MenuItems` contain the information about a single menu item. When you create a new one you have the following
settings available to you:

- `title` is the string that will be displayed to the user in the Menu
- `namedRoute` is the route that will be called when the link is clicked. You must use a
  [named route](https://codeigniter.com/user_guide/incoming/routing.html#using-named-routes) which should be defined
  within your module's `Config/Routes` file.
- `fontAwesomeIcon` is used to specify the [FontAwesome icon](https://fontawesome.com/) that will be displayed in the sidebar.
- `permission` is used to specify a permission string that is required for a user to see that link. If they do not have
  the proper permissions the link will not be displayed within the menu. If this item should be available to everyone
  leave it blank.

One the MenuItem has been created, you add it to the appropriate menu/collection.

Every menu item the user may see also shows up in the ⌘K / Ctrl+K command palette, and the header's breadcrumb names
the active item (and its collection, when that collection is collapsible).

### Naming a page in the breadcrumb

A page below a menu item, like a single record's edit form, can add itself to the breadcrumb with a `crumb` view
section. The breadcrumb then reads "active menu item › crumb". When the page's URL doesn't sit under any menu item,
name the parent too with `crumbParent`:

```php
<?php $this->extend('master') ?>

<?php $this->section('crumbParent') ?><?= lang('Tools.logsModTitle') ?><?php $this->endSection() ?>
<?php $this->section('crumb') ?><?= esc($logFilePretty) ?><?php $this->endSection() ?>
```
