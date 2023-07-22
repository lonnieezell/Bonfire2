# Creating an Admin Module

The bulk of your work in the admin area will likely be done creating new modules of code. Modules are groups of code
centered around a single task or resource, like Users, Photos, etc. For the most part, admin modules are like any
other bit of module CodeIgniter code, except for a new `Module.php` file that helps the module integrate
into the admin area. Other than that you will be able to take advantage of solutions that allow your code to integrate
directly into settings pages, be part of the global search results, automatically display and manage filtering of
a resource list, or show up in the Recycler.

## Configuring Module Locations

In order for Bonfire to discover your modules, you need to specify one or more locations that you plan on storing the modules. By default, the system will look within `app/Modules` for directories. As an example, we could create a Blog folder to store our new blog package. You would then need to create the standard directories, `Config`, `Controllers`, `Models` and `Views` at the very least. A `Module.php` file is also expected to live here, which we will look at in more detail a little later. The directory structure would look like:

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

All files within the Blog directory would be expected to live within the `App\Modules\{ModuleName}` namespace, i.e. `App\Modules\Blog\Config\Routes`. You can customize this by editing `Config\Bonfire` and changing the key of the `$appModules` setting within it.

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
