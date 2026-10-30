# Bonfire Modules

Bonfire provides several powerful modules to help you build and manage your web applications
efficiently. Below is an overview of the main modules available in Bonfire.

## Dashboard

The Dashboard is the first page you see when you log into the admin area. It provides a place to put
widgets that offer a quick overview of the site's status or other important information.

[Read more about the Dashboard](dashboard.md)

## Users

The Users module lists the people who can log in, and lets you add, edit, ban and delete them, change their groups and
permissions, and set the rules for registration, passwords and avatars.

[Read more about Users](users.md)

## Groups

The Groups module, under **Settings → Groups**, lists the user groups and sets which permissions each group grants.

## Authentication and Authorization

All authentication and authorization in Bonfire are handled by CodeIgniter's Shield library. This
library provides a flexible and powerful system for managing users, groups, and permissions.

[Read more about Authentication and Authorization](auth.md)

## General Settings

The General settings screen holds the site's name, whether it's online, the admin's accent color, and the timezone and
date formats.

[Read more about General Settings](settings.md)

## Email

The Email settings screen controls how the site sends email: the sender's name and address, and the SMTP, Mail or
Sendmail delivery details.

[Read more about Email Settings](email.md)

## Tools

The Tools module adds System Info, a summary of the server and its writable folders, and a viewer for the site's log
files.

[Read more about Tools](tools.md)

## Recycler

The Recycler, under **Tools**, holds records that were deleted, such as users, so you can restore them or delete them
for good.

[Read more about the Recycler](../building_admin_modules/recycler.md)

## Actions

The Actions module runs the row and bulk actions in admin lists, such as deleting users, and adds a dashboard widget
showing recent action runs.

[Read more about Actions](../building_admin_modules/actions.md)

## Asset Management

Bonfire provides a simple assets system that allows you to serve CSS/JS assets from anywhere, handle
browser caching, and cache-busting with updated assets.

[Read more about Asset Management](assets.md)

## Consent

The Consent module helps you manage collecting consent from users before storing any cookies through
an opt-in system for all non-required cookies.

[Read more about Consent](consent.md)

## Meta Info

Bonfire provides Entity-Attribute-Value style storage for additional data of entities used with your
models. By default it can be used to add meta information to users, and it can be adapted to store
meta information for the entities managed by your own admin modules. While not exactly a module but a set of core traits, it can be tapped into accross your application.

[Read more about Meta Info](meta_info.md)
