# Permissions

Bonfire uses [Shield](https://shield.codeigniter.com/) groups and permissions. A permission, like `users.edit`, names
one thing a user may do. A group, like `admin`, works like a role. The permission matrix says which permissions each
group grants. Your module checks a permission with `auth()->user()->can('forum.moderate')`, and only users in a group
that grants it, or users given it directly, get through.

Every install publishes its own `app/Config/AuthGroups.php`, so a module should not ask developers to edit that file.
Instead, it declares its permissions, and the groups they go to by default, from its own `Config\Registrar`.

## Bonfire's groups and permissions

### Groups

`$groups` in `app/Config/AuthGroups.php` defines five groups:

| Group | Title | For |
|---|---|---|
| `superadmin` | Super Admin | Complete control of the site. |
| `admin` | Admin | Day to day administrators of the site. |
| `developer` | Developer | Site programmers. |
| `user` | User | General users of the site. Often customers. |
| `beta` | Beta User | Has access to beta-level features. |

New users join `$defaultGroup`, which is `user`. The `user` group grants no permissions, so it can't enter the admin
area.

### Permissions and the default matrix

`$permissions` lists every permission Bonfire uses, and `$matrix` grants them to groups. Shield refuses to give a user
a permission that isn't in `$permissions`, and the admin screens only list those. The table shows the default matrix. The `user` group is left out because it
has none.

| Permission | Lets a user | superadmin | admin | developer | beta |
|---|---|:-:|:-:|:-:|:-:|
| `admin.access` | Enter the admin area. Login also sends these users to the admin. | ✓ | ✓ | ✓ | |
| `admin.settings` | Open the General, Email and System Info settings | ✓ | | ✓ | |
| `groups.settings` | List groups and edit their details | ✓ | ✓ | ✓ | |
| `groups.edit` | Change a group's permissions | ✓ | | | |
| `users.list` | List users and see the user dashboard widgets | ✓ | ✓ | ✓ | |
| `users.view` | See the Users menu item and open a user's Permissions tab | ✓ | | | |
| `users.create` | Create users | ✓ | ✓ | ✓ | |
| `users.edit` | Edit users, their groups and permissions, and ban them | ✓ | ✓ | ✓ | |
| `users.delete` | Delete users | ✓ | ✓ | | |
| `users.manage-admins` | Manage admins (see [Managing groups in the admin](#managing-groups-in-the-admin)) | ✓ | | | |
| `users.settings` | Open the Users settings | ✓ | ✓ | ✓ | |
| `beta.access` | Nothing in Bonfire checks it. Use it for your own beta features. | ✓ | ✓ | ✓ | ✓ |
| `site.viewOffline` | Browse the site while it's offline | ✓ | | ✓ | |
| `widgets.settings` | Save and reset the site's default dashboard | ✓ | ✓ | ✓ | |
| `consent.settings` | Open the Consent settings | ✓ | ✓ | ✓ | |
| `recycler.view` | Open the Recycler | ✓ | | ✓ | |
| `logs.view` | Read the logs and see the log widgets | ✓ | ✓ | ✓ | |
| `logs.manage` | Delete logs | ✓ | | ✓ | |
| `me.edit` | Edit their own profile | ✓ | ✓ | ✓ | |
| `me.security` | Change their own password | ✓ | ✓ | ✓ | |

The matrix grants `superadmin` wildcards such as `users.*`, so it also gets any new permission that starts with one of
those prefixes.

## Checking a permission

`can()` returns `true` when any of the user's groups grants the permission, or the user was given it directly. Pass
more than one permission to check whether the user has any of them.

In a controller, check before doing the work. Bonfire's own controllers send the user back with an error:

```php
public function index()
{
    if (! auth()->user()->can('forum.access')) {
        return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
    }

    // ...
}
```

In a view, hide the controls a user can't use:

```php
<?php if (auth()->user()->can('forum.moderate')) : ?>
    <a href="<?= site_url(ADMIN_AREA . '/forum/hidden') ?>" class="btn btn-outline-secondary">Hidden posts</a>
<?php endif ?>
```

To show a menu item only to users with a permission, give it a `permission` key. See
[Creating an admin module](creating_an_admin_module.md#adding-a-menu-item).

```php
$item = new MenuItem([
    'title'           => 'Forum',
    'namedRoute'      => 'forum-list',
    'fontAwesomeIcon' => 'fas fa-comments',
    'permission'      => 'forum.access',
]);
```

!!! warning "A menu permission only hides the link"
    Anyone who can enter the admin area can still type the page's URL. Check the permission in the controller too.

Every route under the admin area also needs `admin.access`. Bonfire's `admin` filter checks it before your controller
runs and sends other users to the home page.

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

## Managing groups in the admin

Settings → Groups lists every group with its alias, how many users are in it and how many permissions it grants. It
needs `groups.settings`. Click a group to edit its title and description.

The group's Permissions tab has a switch for each permission, grouped by prefix. It needs `groups.edit`. Saving it
stores the whole matrix in the database, so from then on changes to `$matrix` in `app/Config/AuthGroups.php` are
ignored.

To give a single user extra permissions, open the user and use their Permissions tab. The user keeps everything
their groups grant as well.

`users.manage-admins` guards the admins themselves. A user without it can still edit users, but can't:

- add a user to the `admin` or `superadmin` group, or take one out
- give a user a `users.*` permission they don't already have
- ban an admin, or delete a user who has `users.manage-admins`

### Adding a group

The admin has no screen for adding a group. Add it to `$groups` in `app/Config/AuthGroups.php`, and add the
permissions it should start with to `$matrix`:

```php
public array $groups = [
    // ...
    'editor' => [
        'title'       => 'Editor',
        'description' => 'Writes and publishes content.',
    ],
];

public array $matrix = [
    // ...
    'editor' => [
        'admin.access',
        'me.*',
    ],
];
```

Saving a group's details in Settings → Groups stores the whole list of groups in the database, just as saving its
permissions stores the matrix. After that, Bonfire reads the groups from the database and ignores `$groups`. On an
install where that has happened, add the group with `setting()` instead, for example from a migration:

```php
$groups = setting('AuthGroups.groups');

$groups['editor'] = [
    'title'       => 'Editor',
    'description' => 'Writes and publishes content.',
];

setting('AuthGroups.groups', $groups);
```

Then grant its permissions on the group's Permissions tab.
