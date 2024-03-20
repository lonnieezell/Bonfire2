# Authentication and Authorization

All of the authentication and authorization is handled by CodeIgniter's [Shield](https://shield.codeigniter.com) library. This library provides a flexible and powerful system for managing users, groups, and permissions. Please refer to the [Shield documentation](https://shield.codeigniter.com) for more information on how to use it.

## Extending the Authentication System

While none of the functionality has been overridden, the views and controllers have been extended to integrate with Bonfire's theme system. This allows you to easily customize the look and feel of the authentication system to match the rest of your application.

### Views

The authentication pages are styled via the `auth` theme. Much of the look and feel can be modified by modifying the theme itself. If you need to modify the forms or other elements, you can specify the view that should be used in the `Auth` configuration file and then create your own views.

```php
public array $views = [
    'layout'                      => 'master',
    'email_layout'                => '\Bonfire\Views\email',
    'login'                       => '\Bonfire\Views\Auth\login',
    'register'                    => '\Bonfire\Views\Auth\register',
    'forgotPassword'              => '\CodeIgniter\Shield\Views\forgot_password',
    'resetPassword'               => '\CodeIgniter\Shield\Views\reset_password',
    'action_email_2fa'            => '\CodeIgniter\Shield\Views\email_2fa_show',
    'action_email_2fa_verify'     => '\CodeIgniter\Shield\Views\email_2fa_verify',
    'action_email_2fa_email'      => '\CodeIgniter\Shield\Views\Email\email_2fa_email',
    'action_email_activate_show'  => '\Bonfire\Views\Auth\email_activate_show',
    'action_email_activate_email' => '\CodeIgniter\Shield\Views\Email\email_activate_email',
    'magic-link-login'            => '\Bonfire\Views\Auth\magic_link_form',
    'magic-link-message'          => '\Bonfire\Views\Auth\magic_link_message',
    'magic-link-email'            => '\Bonfire\Views\Auth\magic_link_email',
];
```

You can change the main layout that is used for the authentication pages by changing the `layout` value. You can change the layout used for emails by changing the `email_layout` value.

### Controllers

When you need to modify the behavior of the authentication system, you should create a new controller and extend the existing controller. You should then override only the methods that you need to modify. If possible, you should do your changes and call the parent method to ensure that any security fixes that are discovered later are not lost to your application due to your overrides.

```php
<?php

namespace App\Controllers\Auth;

use Bonfire\Auth\Controllers\LoginController as BonfireLoginController;

class LoginController extends BonfireLoginController
{
    // ... your custom methods here
}
```

!!! info Note

    You should always extend the Bonfire controller, not the Shield controller. This will ensure that the theme system is used properly, as Bonfire's auth controllers extend the view methods to use the theme system.
