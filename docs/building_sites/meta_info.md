# Add a Custom Field to User Profiles

This guide stores an extra piece of information on users, a website URL, without changing the `users` table.
For the full set of field options and methods, or to add meta to your own entities, see the
[Meta Info](../modules/meta_info.md) reference.

## 1. Define the field

Add the field to `$metaFields` in `app/Config/Users.php`. The outer key is the fieldset legend on the user form:

```php
public $metaFields = [
    'Profile' => [
        'website_url' => ['label' => 'Website', 'type' => 'text', 'validation' => 'permit_empty|valid_url_strict'],
    ],
];
```

The field now appears on the Create/Edit User form in the admin area, and its value is validated and saved with the user.

## 2. Read the value

```php
$user = model('UserModel')->find(5);

$website = $user->meta('website_url'); // null if not set
```

## 3. Write the value from your own forms

`saveMeta()` saves immediately; there is no need to save the user afterwards:

```php
$user->saveMeta('website_url', $this->request->getPost('website_url'));
```

To save several fields at once, use `syncMeta()`. Fields passed with an empty value are deleted, and keys not defined in `$metaFields` are ignored:

```php
$user->syncMeta([
    'website_url' => 'https://example.com',
]);
```
