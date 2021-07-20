# Dynamic Configuration

Bonfire provides a dynamic configuration system that integrates almost seamlessly with CodeIgniter's `config` classes
and helper, and allows values in many of the Config classes to be persisted to the database. This allows you 
to setup the application as desired when it first ships, but to easily allow admin UI to set new values, without
any change to your code.

## Configuring

A new file, `app/Config/Config.php` contains the basic settings to make this system work. 

To enable or disable this capability, edit the `persistConfig` value. This is enabled by default. 

```
public $persistConfig = true;
```

You can change the database table used to persist the data to with the `configTable` setting. If you change
this you will also need to adjust the table name in the provided migration file. The default value is `ci_config`.

## Using it

There is only one change that you need to know when using this system, but it's a big one. You must access config
class variables through the `->get()` method. This ensures that all values stored in the database are retrieved 
and filled into the appropriate class variables. It only takes a single attribute, the property name to retrieve. 

```
$driver = config('Cache')->get('handler');
```

For classes that do not support dynamic configuration, you can get the public property as normal.

This method is available to any config file that supports dynamic configuration. To confirm if the class supports it, 
check and see if the configuration file uses the `HydrateConfig` trait. You can use this trait on any of your own
configuration files to automatically enable to feature for that class.

## Saving New Values

In order to set the new values, all you need to do is update the value on the config class itself. When the 
`post_system` event is triggered, the system grabs all classes that were cached in the Factories class and compares
the current values with the class' default values. It collects them all together and saves them in one or two queries,
depending on the number of config classes accessed during that call. 

```
$cacheConfig = config('Cache');
$cacheConfig->handler = 'redis';
```

Due to the way it works, this does mean that you must have used the config class with the `config()` helper, or 
called the Factories class directly. Any classes that were simply instantiated manually will not have their values saved.

