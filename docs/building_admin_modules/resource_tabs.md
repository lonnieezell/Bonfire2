# Resource Tabs

Resource tabs are the tabs shown when editing a resource: a single User or User Group. They let your module add its
own tab to those pages without editing Bonfire's views.

Two resources accept tabs:

| Resource | Page                                | Shown to users with |
|----------|-------------------------------------|---------------------|
| `user`   | `admin/users/{id}`                  | access to the page  |
| `group`  | `admin/settings/groups/{alias}`     | `groups.edit`       |

## Registering a tab

Add the tab from the `initAdmin()` method of your module's `Module.php`. A tab needs a title, the URL it links to and,
optionally, a permission:

```php
use Bonfire\Resources\ResourceTab;

public function initAdmin()
{
    service('resourceTabs')->addTabFor('user', new ResourceTab([
        'title'      => 'Notes',
        'url'        => 'users/(id)/notes',
        'permission' => 'users.edit',
    ]));
}
```

- `title` is the text on the tab.
- `url` is relative to the admin area: `ADMIN_AREA` is put in front of it. Write `(id)` where the ID of the resource
  being edited goes. It is read from the current URL, so on `/admin/users/123` the tab above links to
  `/admin/users/123/notes`. For groups, the ID is the group's alias, such as `admin`.
- `permission`, if set, hides the tab from users who don't have that permission.

The tab is shown as the current one when the page's URL matches its link.

## Building the tab's page

The tab only links to a page; your module provides that page. Add a route for it in your module's `Config/Routes.php`:

```php
$routes->group(ADMIN_AREA, ['namespace' => '\App\Modules\Notes\Controllers'], static function ($routes) {
    $routes->get('users/(:num)/notes', 'NotesController::index/$1');
});
```

Then a controller that loads the user and renders a view:

```php
namespace App\Modules\Notes\Controllers;

use Bonfire\Core\AdminController;
use Bonfire\Users\Models\UserModel;

class NotesController extends AdminController
{
    public function index(int $userId)
    {
        if (! auth()->user()->can('users.edit')) {
            return redirect()->to(ADMIN_AREA)->with('error', lang('Bonfire.notAuthorized'));
        }

        $user = model(UserModel::class)->find($userId);
        if ($user === null) {
            return redirect()->back()->with('error', lang('Bonfire.resourceNotFound', [lang('Users.userGenitive')]));
        }

        return $this->render('App\Modules\Notes\Views\notes', ['user' => $user]);
    }
}
```

Check the permission in the controller too: the tab's `permission` only hides the link, it doesn't protect the page.

In the view, include the user's header and tab bar so your page looks like the other tabs. Pass `tab` a name that none
of Bonfire's tabs use, so only your tab is marked as current:

```php
<?php $this->extend('master') ?>

<?php $this->section('main') ?>
<?= $this->include('Bonfire\Users\Views\_user_head') ?>

<?= view('Bonfire\Users\Views\_tabs', ['tab' => 'notes', 'user' => $user]) ?>

<x-admin-box title="Notes">
    ...
</x-admin-box>
<?php $this->endSection() ?>
```

For a group tab, include `Bonfire\Groups\Views\_tabs` instead. It needs `title` (shown in the page header), `group`
(the group's alias) and `tab`.
