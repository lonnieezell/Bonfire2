# Permissions

Bonfire uses [Shield](https://shield.codeigniter.com/) groups and permissions. Your module checks a permission with
`auth()->user()->can('forum.moderate')`, and only users in a group that grants it get through.

Every install publishes its own `app/Config/AuthGroups.php`, so a module should not ask developers to edit that file.
Instead, it declares its permissions, and the groups they go to by default, from its own `Config\Registrar`.

## Declaring permissions

Add a `Config/Registrar.php` to your module with an `AuthGroups()` method. CodeIgniter merges the `permissions` it
returns into `Config\AuthGroups`, including the install's published copy, on every request:

```php
namespace Acme\Forum\Config;

class Registrar
{
    public static function AuthGroups(): array
    {
        return [
            'permissions' => [
                'forum.access'   => 'Can view the forum admin',
                'forum.moderate' => 'Can hide and delete posts',
            ],
        ];
    }
}
```

Settings → Groups lists them with the rest, grouped by the part before the dot.

!!! warning "Prefix every permission with your module's name"
    When two lists have the same key, the registrar's value wins. A permission named `admin.access` would replace
    the install's own. Name yours `forum.*`, `shop.*` and so on.

CodeIgniter only looks for `Config/Registrar.php` at the root of a namespace it knows about. A module in a Composer
package is found as long as `Config\Modules::$discoverInComposer` is `true`, its default. A module in
`app/Modules/Forum` is not, because its namespace root is `app/`. Add the module's namespace to `app/Config/Autoload.php`
so CodeIgniter finds its registrar:

```php
public $psr4 = [
    APP_NAMESPACE      => APPPATH,
    'App\Modules\Forum' => APPPATH . 'Modules/Forum',
];
```

## Default grants

A module can also say which groups should get its permissions. Return them as `moduleGrants`, keyed by your module's
name and then by group:

```php
public static function AuthGroups(): array
{
    return [
        'permissions' => [
            // ...
        ],
        'moduleGrants' => [
            'forum' => [
                'superadmin' => ['forum.*'],
                'admin'      => ['forum.access', 'forum.moderate'],
            ],
        ],
    ];
}
```

Grants are not added to the groups on every request. Once a group is saved in Settings → Groups, Shield keeps the
whole permission matrix in the database, and it would ignore them anyway. Instead, the grants are applied by:

```
php spark bonfire:permissions
```

The command adds each grant once and remembers it. If an admin later takes a permission away from a group, running the
command again does not give it back. A grant for a group the install doesn't have is skipped, and the command says so.

When the command adds a grant, it saves the whole matrix to the database, just as saving a group does. From then on,
changes to `$matrix` in `app/Config/AuthGroups.php` are ignored.

`bf:install` runs the command for you. After you add a module or update one that declares new grants, run it yourself.
