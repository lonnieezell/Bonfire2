# Bonfire Application Structure

Bonfire is an add-on to CodeIgniter 4, packaged as a set of modules and libraries that can be used together or independently. That means that all of its code lives within the `vendor` folder, leaving your `app` folder clean and uncluttered with boilerplate code. But there are a few files and folders that you should be aware of.

**Config files**

Once installed, Bonfire will have copied several configuration files into your `app/Config` folder. These files are used to configure the various modules and libraries that Bonfire provides. You can modify these files to change the behavior of Bonfire. The following files are copied over:

- `Assets` - Used to define how the Assets library should behave.
- `Auth`, `AuthGroups`, `AuthTokens` - These files are part of the [Shield authentication library](https://shield.codeigniter.com/) and are used to define your initial user groups, permissions, and how the authentication system should behave.
- `Bonfire` - This allows you to customize some views used by Bonfire, as well as define any additional modules it should know about and load, and allows you to modify the order of the default admin menu items.
- `Site` - Allows you to define whether the site is online or in maintenance mode, the site name, and more.
- `Themes` - Determines which themes are available to the system, and some view component settings.
- `Consent` - Defines how the Cookie Consent library should behave.
- `Dashboard` - Allows you to define the widgets that should be displayed on the dashboard.
- `Recycler` - Defines how the Recycler library should behave.
- `Users` - Contains avatar settings, custom user fields, and more.

**/themes**

A new folder is created in the project root that holds the view files that define a theme for Bonfire. Themes provide the basic structure of the web pages, including all of the information that surrounds the actual content. By default, it ships with three themes, `admin`, `app`, and `auth`.

- The `admin` theme is what is used for the Bonfire admin area.
- The `app` theme is extremely simple and is meant mostly as a semi-functional skeleton that you can
  leverage as a starting point for your app.
- The `auth` theme is more a set of partials that are used for all of the authentication-related screens.
  You can specify which theme/layout it should extend.
