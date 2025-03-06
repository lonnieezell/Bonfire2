# Building Sites

While Bonfire focuses on providing a powerful backend area for your sites, a number of the provided tools can 
be used within your front-end sites with minimal work. This includes some helper functions for working with dates, 
a user consent framework for helping you comply with some of the global privacy laws, like GDPR, new tools to build
your views with, and more.

Much like any CodeIgniter application, your code will typically live in the `/app` folder, utilizing the standard
`Controllers`, `Models`, `Views`, and other folders for your code. You can, of course, located modules anywhere you
wish as long as the autoloader can find it. None of this changes using Bonfire. All of Bonfire's code is kept 
in the `vendor` folder, where it will not conflict with your application's code. There are new config files to 
keep an eye out for.

There is also an example frontpage with a full-blown App theme [bonfire2-frontpage-theme](https://github.com/dgvirtual/bonfire2-frontpage-theme)
for you to look at.

## Alerts

Bonfire uses [Tatter\Alerts](https://github.com/tattersoftware/codeigniter4-alerts) to provide support for simple alerts. This section covers how to use alerts and flash messages.

[Read more about Alerts](alerts.md)

## Assets

Bonfire provides a simple assets system that allows you to serve CSS/JS assets from anywhere, handle browser caching, and cache-busting with updated assets.

[Read more about Assets](assets.md)

## View Components

View Components allow you to create custom HTML elements to use within your views. This section covers creating and using components.

[Read more about View Components](components.md)

## Consent

The Consent module helps you manage collecting consent from users before storing any cookies through an opt-in system for all non-required cookies.

[Read more about Consent](consent.md)

## View Metadata

Bonfire provides a `Metadata` service that simplifies working with the meta information, scripts, and styles in your application.

[Read more about View Metadata](metadata.md)

## Settings

Bonfire provides a simple interface that you can use in place of calling `config()` to allow you to read and store config values in the database.

[Read more about Settings](settings.md)

## Meta Info

Bonfire provides Entity-Attribute-Value style storage for additional data of entities used with your
models. By default it can be used to add meta information to the entities managed by users model or your own admin modules.

[Read more about Meta Info](meta_info.md)

## Common Functions

Bonfire provides a few helper functions that are always available for your use, such as `app_date` for formatting dates.

[Read more about Common Functions](common_functions.md)
