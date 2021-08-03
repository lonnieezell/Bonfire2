# Settings

Bonfire provides a simple interface that you can use in place of calling `config()` to allow you to read and store
config values in the database. If the value has not been updated and saved in the database then the original value
from the config file will be used.

This allows you to save your application's default state as values in config files, all stored in version control,
and still allows your users to override those settings once the site is live. 

## Setup

A migration is provided that will create the required `settings` table. No other setup is needed. 

## Usage

To retrieve a config value use the `settings()` helper method that is always loaded. 

```
// The same as config('App')->siteName;
$siteName = setting('App', 'siteName');
// or
$siteName = setting()->get('App', 'siteName');
```

In this case we used the short class name, `App`, which the `config()` method automatically locates within the 
`app/Config` directory. If it was from a module, it would be found there. Either way, the fully qualified name
is automatically detected by the Settings class to keep values separated from config files that may share the 
same name but different namespaces. 

To save a value, call the `set()` method on the settings class, providing the class name, the key, and the value.
Note that boolean `true`/`false` will be converted to strings `:true` and `:false` when stored in the database, but
will be converted back into a boolean when retrieved. Arrays and objects are serialized when saved, and unserialized
when retrieved. 

```
setting()->set('App', 'siteName', 'My Great Site');
```
