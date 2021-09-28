# Resource Tabs

Resource tabs are the tabs that are displayed when editing a resource, like an individual User or User Group. 
They make it possible for you to easily integrate other tabs onto a user without editing the core theme. 

Adding a new tab is typically done from the `Module.php` for your module. You only need 3 pieces of information
to create a new tab: a title, the URL to send them to, and (optionally) a permission to check against to see 
if they can see the tab or not.

```php
use \Bonfire\Resources\ResourceTab;

public function initAdmin() 
{
    service('resourceTabs')->addTabFor('user', new ResourceTab([
        'title' => 'Permissions',
        'url' => 'users/(id)/permissions',
        'permission' => 'users.edit'
    ]));
}
```

The `title` is what is displayed to the user on the tab itself.

If it is set, `permission` will check the current user against that permission. If they have the permission
then the tab will display, otherwise it will not be rendered.

When setting the URL there are a few things to note: 

1. The `ADMIN_AREA` constant will be prefixed to the URl you provide. 
2. To represent the ID of the resource you want to provide the tab to, use the `(id)` placeholder. This will
    be replaced by the ID of the current user being edited, by analyzing the current URL.

As an example, assuming you were on the current url `/admin/users/123`, the above URL would be 
`/admin/users/123/permissions`.
