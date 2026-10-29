# Authentication and Authorization

All of the authentication and authorization is handled by CodeIgniter's [Shield](https://shield.codeigniter.com) library. This library provides a flexible and powerful system for managing users, groups, and permissions. Please refer to the [Shield documentation](https://shield.codeigniter.com) for more information on how to use it.

Bonfire ships its own `Auth` config, `Bonfire\Auth\Config\Auth`, which extends Shield's. This page lists the defaults
that decide how login and registration behave, and the components the auth pages are built from. For every other key,
see the [Configuration Reference](../intro/configuration.md#auth).

## Bonfire's Defaults

| Key | Default | Effect | Change it in |
| --- | --- | --- | --- |
| `$allowRegistration` | `true` | Visitors can create their own account at `/register`, and the login page links to it. When `false`, the register page sends visitors back with an error and only an admin can add users. | **Settings → Users** or code |
| `$allowMagicLinkLogins` | `true` | Users who forgot their password can ask for a one-time login link by email at `/login/magic-link`. The login page links to it. When `false`, the link is hidden and the magic link pages refuse requests. | Code |
| `$magicLinkLifetime` | `HOUR` | How long, in seconds, a magic link works. | Code |
| `$actions['register']` | `null` | Nothing happens after registration; the new user is logged in. See [Actions](#actions). | **Settings → Users** or code |
| `$actions['login']` | `null` | Nothing happens after login. See [Actions](#actions). | **Settings → Users** or code |
| `$redirects` | See [Redirects](#redirects) | Where users go after login, logout, registration and denied access. | Code |

### Redirects

| Key | Default | When it's used |
| --- | --- | --- |
| `login` | `'/'` | After a successful login. |
| `logout` | `'login'` | After logout. |
| `register` | `'/'` | After registration, and after email activation. |
| `force_reset` | `'/'` | When a user's account is flagged for a forced password reset. |
| `permission_denied` | `'/'` | When a user lacks a permission a route filter requires. |
| `group_denied` | `'/'` | When a user isn't in a group a route filter requires. |

Each value can be an absolute URL (`https://example.com`), a named route, or a URI path (`account`).

Users with the `admin.access` permission ignore `login`: after login they always go to the admin area. Everyone else
goes back to the page they were on when they were asked to log in, or to `login` if there wasn't one. To change this,
override `loginRedirect()` in your `app/Config/Auth.php`.

### Actions

An action is an extra step Shield runs after login or registration, before the user gets in. Both are off by default.
Bonfire has its own version of each of Shield's two email actions. They render their pages with the `Auth` theme, so use
them instead of Shield's.

| Key | Class | What the user sees | Settings → Users switch |
| --- | --- | --- | --- |
| `register` | `Bonfire\Auth\Actions\EmailActivator` | After registering, they get a code by email and must enter it to activate their account. | Force email verification |
| `login` | `Bonfire\Auth\Actions\Email2FA` | After entering their password, they get a code by email and must enter it to finish logging in. | Force 2FA after login |

Both actions send email, so set up [Email](email.md) first.

The switches recognize only Bonfire's classes. If you set Shield's classes, or your own, in code, the switch shows as off,
and saving the screen replaces your class with `null`.

## Changing a Default

You can change a default in three places. A value stored in the database wins over the config file.

### On the Settings → Users screen

Admins with the `users.settings` permission can change registration, the two actions, remember-me and the password
rules under **Settings → Users**. The screen saves each value with `setting()`, so it is stored in the database.

### In `app/Config/Auth.php`

The installer publishes `app/Config/Auth.php`, a copy of Bonfire's `Auth` config with every key in it. Edit a value
there to change the default for every environment. For example, to turn off magic links and send users to an
`account` page after login and registration:

```php
public bool $allowMagicLinkLogins = false;

public array $redirects = [
    'register'          => 'account',
    'login'             => 'account',
    'logout'            => 'login',
    'force_reset'       => '/',
    'permission_denied' => '/',
    'group_denied'      => '/',
];
```

Keep every key of `$redirects`, even the ones you don't change. Bonfire reads each one, so a missing key causes an
error.

### With `setting()`

To change a value from code at runtime, such as in a migration or a seeder, store it with `setting()`:

```php
setting('Auth.allowMagicLinkLogins', false);
```

!!! warning

    Once a key is stored in the database, Bonfire reads it from there and ignores `app/Config/Auth.php`. This happens
    the first time an admin saves **Settings → Users**, which stores `allowRegistration` and `actions`. Changing those
    keys in the config file afterwards has no effect. Remove the stored value to go back to the config file:

    ```php
    setting()->forget('Auth.allowRegistration');
    ```

See [Settings](../building_sites/settings.md) for how stored settings work.

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
    'action_email_2fa'            => '\Bonfire\Views\Auth\email_2fa_show',
    'action_email_2fa_verify'     => '\Bonfire\Views\Auth\email_2fa_verify',
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
namespace App\Controllers\Auth;

use Bonfire\Auth\Controllers\LoginController as BonfireLoginController;

class LoginController extends BonfireLoginController
{
    // ... your custom methods here
}
```

!!! info Note

    You should always extend the Bonfire controller, not the Shield controller. This will ensure that the theme system is used properly, as Bonfire's auth controllers extend the view methods to use the theme system.

## Auth Theme Components

The `Auth` theme has two [view components](../building_sites/components.md) of its own. Bonfire's login, register,
email 2FA, email activation and magic link views are built from them. The installer publishes them to
`themes/Auth/Components/`, so you can edit them there to change every auth page at once.

### `<x-auth-card>`

A Bootstrap card for an auth form. It is slightly transparent, so the page background shows through, and it is
5 columns wide on large screens and 7 on medium ones. It has no attributes; put the card's content in its slot.

```html
<x-auth-card>
    <div class="card-body">
        <h5 class="card-title mb-5">Log in</h5>
        <!-- form -->
    </div>
</x-auth-card>
```

### `<x-unsplash>`

A full-screen photo behind the page content. It has no attributes; put the page content in its slot. It loads a random
photo from [Picsum](https://picsum.photos/) and shows an "Image by Picsum" credit in the bottom-right
corner. Despite the component's name, the photo doesn't come from Unsplash.

Because the photo comes from an external site, every visitor's browser requests it from `picsum.photos`. To use your own
image, or a plain background, edit `themes/Auth/Components/unsplash.php`.

### Using them together

Bonfire's auth views put the card inside the background, in a centered container:

```html
<x-unsplash>
    <div class="container d-flex justify-content-center p-5">
        <x-auth-card>
            <div class="card-body">
                <!-- form -->
            </div>
        </x-auth-card>
    </div>
</x-unsplash>
```
