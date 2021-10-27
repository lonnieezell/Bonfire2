# Common functions

Bonfire provides a few helper functions that are always available for your use.

**app_date($date)**

This helper will display a date based on the current settings in the General Settings page for date format and the 
application's current timezone. You can pass a string with the date, a DateTime object, or a CodeIgniter\i18n\Time 
instance as the first argument.

```php
echo app_date('2021-01-15 15:35:00');
// outputs: 01/15/2021

$date = new DateTime('2021-01-15 15:35:00');
echo app_date($date);

$date = new CodeIgniter\i18n\Time('2021-01-15 15:35:00');
echo app_date($date);
```

You can include the time, formatted as specified in General Settings, by passing `true` as the second argument.

```php
echo app_date($date, true);
// outputs: 01/15/2021 3:35 PM
```

You can further include the timezone by passing `true` as the third argument.

```php
echo app_date($date, true, true);
// outputs: 01/15/2021 3:35 PM CST
```

