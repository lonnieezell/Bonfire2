# Bonfire Application Structure

Bonfire is based on CodeIgniter 4.2 and uses the default structure of a CodeIgniter application to 
ensure it is simple enough for the developer to get used to, if they have prior CodeIgniter experience. 
In addition to the standard folder structure, the following two new folders exist: 

**bonfire**

This forms the guts of Bonfire. All new functionality, libraries, modules, etc that may ship with 
Bonfire will live here. This also forms the base `Bonfire` namespace. 

**themes**

Holds the view files that define a theme for Bonfire. Themes provide the basic structure of the web 
pages, including all of the information that surrounds the actual content. By default, it ships with
three themes, `admin`, `app`, and `auth`. 

- The `admin` theme is what is used for the Bonfire admin area.
- The `app` theme is extremely simple and is meant mostly as a semi-functional  skeleton that you can 
leverage as a starting point for your app.
- The `auth` theme is more a set of partials that are used for all of the authentication-related screens.
    You can specify which theme/layout it should extend. 

## Admin URL

By default, the admin area can be found by navigating to `/admin`. This will bring up the admin dashboard.
You can change the base path this is found at by changing the `ADMIN_AREA` constant found in `app/Config/Constants.php`:

```
/*
 | --------------------------------------------------------------------
 | App Namespace
 | --------------------------------------------------------------------
 |
 | Defines the base path of the URL where Bonfire's admin area can be
 | found. By default, this is 'admin', which means that the admin area
 | would be found at http://localhost:8080/admin
 */
defined('ADMIN_AREA') || define('ADMIN_AREA', 'admin');
```
